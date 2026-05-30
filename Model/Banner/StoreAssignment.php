<?php

namespace LeanCommerce\LeanZote\Model\Banner;

use Magento\Framework\App\ResourceConnection;

class StoreAssignment
{
    private const STORE_TABLE = 'leanCommerce_leanZote_store';

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param int $bannerId
     * @param int|null $fallbackStoreId
     * @return int[]
     */
    public function getAssignedStoreIds($bannerId, $fallbackStoreId = null)
    {
        $bannerId = (int) $bannerId;
        if ($bannerId <= 0) {
            return $fallbackStoreId !== null ? [(int) $fallbackStoreId] : [0];
        }

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(self::STORE_TABLE);

        $select = $connection->select()
            ->from($tableName, ['store_id'])
            ->where('banner_id = ?', $bannerId)
            ->order('store_id ASC');

        $storeIds = array_map('intval', $connection->fetchCol($select));
        if (!$storeIds && $fallbackStoreId !== null) {
            $storeIds = [(int) $fallbackStoreId];
        }

        return $this->normalizeStoreIds($storeIds);
    }

    /**
     * @param int $bannerId
     * @param mixed $storeIds
     * @return void
     */
    public function saveAssignments($bannerId, $storeIds)
    {
        $bannerId = (int) $bannerId;
        if ($bannerId <= 0) {
            return;
        }

        $storeIds = $this->normalizeStoreIds($storeIds);
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(self::STORE_TABLE);

        $connection->delete($tableName, ['banner_id = ?' => $bannerId]);

        $rows = [];
        foreach ($storeIds as $storeId) {
            $rows[] = [
                'banner_id' => $bannerId,
                'store_id' => $storeId,
            ];
        }

        if ($rows) {
            $connection->insertMultiple($tableName, $rows);
        }
    }

    /**
     * @param mixed $storeIds
     * @return int[]
     */
    public function normalizeStoreIds($storeIds)
    {
        if (!is_array($storeIds)) {
            $storeIds = $storeIds === null || $storeIds === '' ? [] : explode(',', (string) $storeIds);
        }

        $normalized = [];
        foreach ($storeIds as $storeId) {
            if (is_array($storeId)) {
                continue;
            }

            $storeId = (int) $storeId;
            if ($storeId < 0) {
                continue;
            }

            $normalized[$storeId] = $storeId;
        }

        if (!$normalized) {
            return [0];
        }

        if (isset($normalized[0])) {
            return [0];
        }

        sort($normalized);

        return array_values($normalized);
    }
}
