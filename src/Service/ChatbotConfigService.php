<?php declare(strict_types=1);

namespace ShopwareChatbotConnectorPlugin\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;

class ChatbotConfigService
{
    private const CONFIG_DOMAIN = 'ShopwareChatbotConnectorPlugin.config.';

    public function __construct(private readonly SystemConfigService $systemConfigService)
    {
    }

    public function isActive(?string $salesChannelId = null): bool
    {
        return (bool) $this->systemConfigService->get(self::CONFIG_DOMAIN . 'active', $salesChannelId);
    }

    /**
     * @return list<string>
     */
    public function getRagCategoryIds(?string $salesChannelId = null): array
    {
        $ids = [
            $this->systemConfigService->get(self::CONFIG_DOMAIN . 'ragCategory1', $salesChannelId),
            $this->systemConfigService->get(self::CONFIG_DOMAIN . 'ragCategory2', $salesChannelId),
        ];

        return array_values(array_filter($ids));
    }
}
