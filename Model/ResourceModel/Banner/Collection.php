<?php
namespace LeanCommerce\LeanZote\Model\ResourceModel\Banner;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'banner_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \LeanCommerce\LeanZote\Model\Banner::class,
            \LeanCommerce\LeanZote\Model\ResourceModel\Banner::class
        );
    }
}