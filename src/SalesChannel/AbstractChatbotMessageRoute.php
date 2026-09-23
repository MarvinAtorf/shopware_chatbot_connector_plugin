<?php declare(strict_types=1);

namespace ShopwareChatbotConnectorPlugin\SalesChannel;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractChatbotMessageRoute
{
    abstract public function getDecorated(): AbstractChatbotMessageRoute;

    abstract public function send(Request $request, SalesChannelContext $context): ChatbotMessageRouteResponse;
}
