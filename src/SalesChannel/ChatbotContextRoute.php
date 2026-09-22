<?php declare(strict_types=1);

namespace ShopwareChatbotConnectorPlugin\SalesChannel;

use ShopwareChatbotConnectorPlugin\Service\ChatbotConfigService;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Routing\StoreApiRouteScope;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: [PlatformRequest::ATTRIBUTE_ROUTE_SCOPE => [StoreApiRouteScope::ID]])]
class ChatbotContextRoute extends AbstractChatbotContextRoute
{
    public function __construct(private readonly ChatbotConfigService $chatbotConfigService)
    {
    }

    public function getDecorated(): AbstractChatbotContextRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(
        path: '/store-api/chatbot-connector/context',
        name: 'store-api.chatbot-connector.context',
        methods: ['GET'],
    )]
    public function load(Request $request, SalesChannelContext $context): ChatbotContextRouteResponse
    {
        $salesChannelId = $context->getSalesChannelId();

        $struct = new ChatbotContextStruct(
            active: $this->chatbotConfigService->isActive($salesChannelId),
            salesChannelId: $salesChannelId,
            customerId: $context->getCustomer()?->getId(),
            ragCategoryIds: $this->chatbotConfigService->getRagCategoryIds($salesChannelId),
        );

        return new ChatbotContextRouteResponse($struct);
    }
}
