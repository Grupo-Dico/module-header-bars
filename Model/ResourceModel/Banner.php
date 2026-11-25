<?php
namespace LeanCommerce\LeanZote\Model\ResourceModel;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Banner extends AbstractDb
{
    /**
     * @var bool
     */
    private $connectionCharsetInitialized = false;

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('leanCommerce_leanZote', 'banner_id');
    }

    /**
     * Fuerza utf8mb4 en la sesión para que los emojis (4 bytes) no se reemplacen por "?".
     *
     * @return AdapterInterface|false
     */
    public function getConnection()
    {
        $connection = parent::getConnection();

        if ($connection && !$this->connectionCharsetInitialized) {
            $this->connectionCharsetInitialized = true;
            $connection->query("SET NAMES 'utf8mb4'");
        }

        return $connection;
    }
}
