<?php declare(strict_types=1);

namespace ShopwareChatbotConnectorPlugin\SalesChannel;

use Shopware\Core\Framework\Struct\Struct;

class ChatbotMessageStruct extends Struct
{
    public function __construct(protected string $reply)
    {
    }

    public function getReply(): string
    {
        return $this->reply;
    }

    public function getApiAlias(): string
    {
        return 'shopware_chatbot_connector_message';
    }
}
