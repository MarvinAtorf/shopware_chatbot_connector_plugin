<?php declare(strict_types=1);

namespace ShopwareChatbotConnectorPlugin\SalesChannel;

use Shopware\Core\System\SalesChannel\StoreApiResponse;

/**
 * @extends StoreApiResponse<ChatbotContextStruct>
 */
class ChatbotContextRouteResponse extends StoreApiResponse
{
    public function getContext(): ChatbotContextStruct
    {
        return $this->object;
    }
}
