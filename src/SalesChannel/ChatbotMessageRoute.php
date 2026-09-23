<?php declare(strict_types=1);

namespace ShopwareChatbotConnectorPlugin\SalesChannel;

use ShopwareChatbotConnectorPlugin\Service\ChatbotConfigService;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Routing\StoreApiRouteScope;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Server-side proxy between the storefront widget and the FastAPI chatbot
 * backend. Forwards the message and attaches the backend API key here, so
 * the key never has to reach the browser.
 *
 * This route only forwards the request/response — it contains no
 * chatbot / RAG / LLM logic itself.
 */
#[Route(defaults: [PlatformRequest::ATTRIBUTE_ROUTE_SCOPE => [StoreApiRouteScope::ID]])]
class ChatbotMessageRoute extends AbstractChatbotMessageRoute
{
    public function __construct(
        private readonly ChatbotConfigService $chatbotConfigService,
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    public function getDecorated(): AbstractChatbotMessageRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(
        path: '/store-api/chatbot-connector/message',
        name: 'store-api.chatbot-connector.message',
        methods: ['POST'],
    )]
    public function send(Request $request, SalesChannelContext $context): ChatbotMessageRouteResponse
    {
        $salesChannelId = $context->getSalesChannelId();

        if (!$this->chatbotConfigService->isActive($salesChannelId)) {
            throw new \RuntimeException('Chatbot is not active for this sales channel.');
        }

        $message = trim((string) $request->request->get('message', ''));

        if ($message === '') {
            throw new \InvalidArgumentException('Message must not be empty.');
        }

        $apiKey = $this->chatbotConfigService->getBackendApiKey($salesChannelId);

        try {
            $response = $this->httpClient->request('POST', $this->chatbotConfigService->getBackendUrl() . '/chat', [
                'headers' => array_filter([
                    'X-API-Key' => $apiKey,
                    'Content-Type' => 'application/json',
                ]),
                'json' => [
                    'message' => $message,
                ],
                'timeout' => 15,
            ]);

            $data = $response->toArray(false);
        } catch (TransportExceptionInterface) {
            throw new \RuntimeException('Chatbot backend is not reachable.');
        }

        $reply = is_string($data['reply'] ?? null)
            ? $data['reply']
            : 'Entschuldigung, es gab ein Problem bei der Verarbeitung deiner Nachricht.';

        return new ChatbotMessageRouteResponse(new ChatbotMessageStruct($reply));
    }
}
