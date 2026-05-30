<?php

namespace LeanCommerce\LeanZote\Model\Banner\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\StoreManagerInterface;

class StoreViews implements OptionSourceInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            [
                'label' => __('All Store Views'),
                'value' => '0',
            ],
        ];

        foreach ($this->storeManager->getStores(false) as $store) {
            $website = $store->getWebsite();
            $group = $store->getGroup();
            $labelParts = [];

            if ($website) {
                $labelParts[] = $website->getName();
            }

            if ($group) {
                $labelParts[] = $group->getName();
            }

            $labelParts[] = $store->getName();

            $options[] = [
                'label' => implode(' / ', $labelParts),
                'value' => (string) $store->getId(),
            ];
        }

        return $options;
    }
}
