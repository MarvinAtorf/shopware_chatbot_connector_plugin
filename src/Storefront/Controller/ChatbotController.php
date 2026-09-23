<?php declare(strict_types=1);

namespace ShopwareChatbotConnectorPlugin\Storefront\Controller;

use ShopwareChatbotConnectorPlugin\Service\ChatbotConfigService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Server-side proxy between the storefront widget and the FastAPI chatbot
 * backend. Forwards the message and attaches the backend API key here, so
 * the key never has to reach the browser.
 *
 * Plain Storefront AJAX route (session-based, no sw-access-key needed) —
 * this is only ever called from our own widget on the same page, unlike
 * ChatbotContextRoute which is a Store-API route for external/headless use.
 *
 * This route only forwards the request/response — it contains no
 * chatbot / RAG / LLM logic itself.
 */
#[Route(defaults: ['_routeScope' => ['storefront']])]
class ChatbotController extends StorefrontController
{
    public function __construct(
        private readonly ChatbotConfigService $chatbotConfigService,
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    #[Route(
        path: '/widgets/chatbot-connector/message',
        name: 'frontend.chatbot-connector.message',
        defaults: ['XmlHttpRequest' => true],
        methods: ['POST'],
    )]
    public function sendMessage(Request $request, SalesChannelContext $context): JsonResponse
    {
        $salesChannelId = $context->getSalesChannelId();

        if (!$this->chatbotConfigService->isActive($salesChannelId)) {
            return new JsonResponse(['error' => 'Chatbot is not active'], 404);
        }

        $payload = json_decode($request->getContent(), true) ?? [];
        $message = trim((string) ($payload['message'] ?? ''));

        if ($message === '') {
            return new JsonResponse(['error' => 'Message must not be empty'], 400);
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
            return new JsonResponse(['error' => 'Chatbot backend is not reachable'], 502);
        }

        $reply = is_string($data['reply'] ?? null)
            ? $data['reply']
            : 'Entschuldigung, es gab ein Problem bei der Verarbeitung deiner Nachricht.';

        return new JsonResponse(['reply' => $reply]);
    }
}
