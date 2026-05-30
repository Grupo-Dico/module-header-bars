<?php

namespace LeanCommerce\LeanZote\Model;

use LeanCommerce\LeanZote\Model\ResourceModel\Banner as BannerResourceModel;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

class Banner extends AbstractModel implements IdentityInterface
{
    public const CACHE_TAG = 'leanzote_banner';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * @var string
     */
    protected $_eventPrefix = 'leanzote_banner';

    protected $_isPkAutoIncrement = true;
    protected $_isUtcDates = false;

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(BannerResourceModel::class);
    }

    /**
     * @return array
     */
    public function getIdentities()
    {
        return [
            self::CACHE_TAG,
            self::CACHE_TAG . '_' . $this->getId(),
        ];
    }
}
