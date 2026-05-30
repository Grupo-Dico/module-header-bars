<?php
namespace LeanCommerce\LeanZote\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Banner extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('leanCommerce_leanZote', 'banner_id');
    }
}