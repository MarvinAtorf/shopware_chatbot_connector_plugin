<?php declare(strict_types=1);

namespace ShopwareChatbotConnectorPlugin\SalesChannel;

use Shopware\Core\Framework\Struct\Struct;

class ChatbotContextStruct extends Struct
{
    /**
     * @param list<string> $ragCategoryIds
     */
    public function __construct(
        protected bool $active,
        protected string $salesChannelId,
        protected ?string $customerId,
        protected array $ragCategoryIds,
        protected string $backendUrl,
    ) {
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function getBackendUrl(): string
    {
        return $this->backendUrl;
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    /**
     * @return list<string>
     */
    public function getRagCategoryIds(): array
    {
        return $this->ragCategoryIds;
    }

    public function getApiAlias(): string
    {
        return 'shopware_chatbot_connector_context';
    }
}
