<?php declare(strict_types=1);

namespace ShopwareChatbotConnectorPlugin\SalesChannel;

use Shopware\Core\System\SalesChannel\StoreApiResponse;

/**
 * @extends StoreApiResponse<ChatbotMessageStruct>
 */
class ChatbotMessageRouteResponse extends StoreApiResponse
{
    public function getMessage(): ChatbotMessageStruct
    {
        return $this->object;
    }
}
