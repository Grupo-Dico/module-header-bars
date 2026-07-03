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
     * Ensures the session uses utf8mb4 so 4-byte characters (emojis) survive.
     *
     * Magento initializes the default connection with `SET NAMES utf8` (utf8mb3),
     * which silently replaces 4-byte characters with `?` on both read and write.
     * Forcing utf8mb4 on the shared session preserves emojis end to end.
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
