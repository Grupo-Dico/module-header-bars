<?php

namespace LeanCommerce\LeanZote\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    private const MAIN_TABLE = 'leanCommerce_leanZote';
    private const STORE_TABLE = 'leanCommerce_leanZote_store';

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $connection = $setup->getConnection();
        $tableName = $setup->getTable(self::MAIN_TABLE);

        if (!$connection->isTableExists($tableName)) {
            $setup->endSetup();
            return;
        }

        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $this->addColumnIfMissing($setup, $tableName, 'banned_urls', [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'Banned URLs',
            ]);
            $this->addColumnIfMissing($setup, $tableName, 'start_date_button', [
                'type' => Table::TYPE_DATETIME,
                'nullable' => true,
                'comment' => 'Start Date Button',
            ]);
            $this->addColumnIfMissing($setup, $tableName, 'end_date_button', [
                'type' => Table::TYPE_DATETIME,
                'nullable' => true,
                'comment' => 'End Date Button',
            ]);
            $this->addColumnIfMissing($setup, $tableName, 'button_color_text', [
                'type' => Table::TYPE_TEXT,
                'length' => 25,
                'nullable' => true,
                'comment' => 'Button Color Text',
            ]);
            $this->addColumnIfMissing($setup, $tableName, 'button_color_background', [
                'type' => Table::TYPE_TEXT,
                'length' => 25,
                'nullable' => true,
                'comment' => 'Button Color Background',
            ]);
            $this->addColumnIfMissing($setup, $tableName, 'counter_color_text', [
                'type' => Table::TYPE_TEXT,
                'length' => 25,
                'nullable' => true,
                'comment' => 'Counter Color Text',
            ]);
        }

        if (version_compare($context->getVersion(), '1.0.2', '<')) {
            $this->addColumnIfMissing($setup, $tableName, 'button_before', [
                'type' => Table::TYPE_SMALLINT,
                'nullable' => false,
                'default' => 0,
                'comment' => 'Show Button Before Counter',
            ]);
        }

        if (version_compare($context->getVersion(), '1.0.3', '<')) {
            $this->addColumnIfMissing($setup, $tableName, 'store_id', [
                'type' => Table::TYPE_SMALLINT,
                'nullable' => false,
                'unsigned' => true,
                'default' => 0,
                'comment' => 'Legacy Store View ID',
            ]);
        }

        if (version_compare($context->getVersion(), '1.0.4', '<')) {
            $this->createStoreTable($setup);
            $this->migrateLegacyStoreAssignments($setup);
            $this->addIndexIfMissing(
                $setup,
                $tableName,
                $setup->getIdxName(self::MAIN_TABLE, ['is_active', 'start_date', 'end_date']),
                ['is_active', 'start_date', 'end_date']
            );
        }

        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param string $tableName
     * @param string $columnName
     * @param array $definition
     * @return void
     */
    private function addColumnIfMissing(SchemaSetupInterface $setup, $tableName, $columnName, array $definition)
    {
        $connection = $setup->getConnection();
        if (!$connection->tableColumnExists($tableName, $columnName)) {
            $connection->addColumn($tableName, $columnName, $definition);
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     * @return void
     */
    private function createStoreTable(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $tableName = $setup->getTable(self::STORE_TABLE);

        if ($connection->isTableExists($tableName)) {
            return;
        }

        $table = $connection->newTable($tableName)
            ->addColumn(
                'banner_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'unsigned' => true, 'primary' => true],
                'Banner ID'
            )
            ->addColumn(
                'store_id',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'unsigned' => true, 'primary' => true, 'default' => 0],
                'Store View ID'
            )
            ->addIndex($setup->getIdxName(self::STORE_TABLE, ['store_id']), ['store_id'])
            ->addForeignKey(
                $setup->getFkName(self::STORE_TABLE, 'banner_id', self::MAIN_TABLE, 'banner_id'),
                'banner_id',
                $setup->getTable(self::MAIN_TABLE),
                'banner_id',
                Table::ACTION_CASCADE
            )
            ->setComment('LeanZote Banner Store Assignments');

        $connection->createTable($table);
    }

    /**
     * @param SchemaSetupInterface $setup
     * @return void
     */
    private function migrateLegacyStoreAssignments(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $mainTable = $setup->getTable(self::MAIN_TABLE);
        $storeTable = $setup->getTable(self::STORE_TABLE);

        if (!$connection->tableColumnExists($mainTable, 'store_id')) {
            return;
        }

        $select = $connection->select()
            ->from($mainTable, ['banner_id', 'store_id']);
        $rows = [];

        foreach ($connection->fetchAll($select) as $row) {
            $rows[] = [
                'banner_id' => (int) $row['banner_id'],
                'store_id' => isset($row['store_id']) ? (int) $row['store_id'] : 0,
            ];
        }

        if ($rows) {
            $connection->insertOnDuplicate($storeTable, $rows, ['store_id']);
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param string $tableName
     * @param string $indexName
     * @param string[] $columns
     * @return void
     */
    private function addIndexIfMissing(SchemaSetupInterface $setup, $tableName, $indexName, array $columns)
    {
        $connection = $setup->getConnection();
        $indexList = $connection->getIndexList($tableName);

        if (!isset($indexList[$indexName])) {
            $connection->addIndex($tableName, $indexName, $columns, AdapterInterface::INDEX_TYPE_INDEX);
        }
    }
}
