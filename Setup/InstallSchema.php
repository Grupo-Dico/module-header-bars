<?php

namespace LeanCommerce\LeanZote\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    private const MAIN_TABLE = 'leanCommerce_leanZote';
    private const STORE_TABLE = 'leanCommerce_leanZote_store';

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $this->createMainTable($setup);
        $this->createStoreTable($setup);
        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     * @return void
     */
    private function createMainTable(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $tableName = $setup->getTable(self::MAIN_TABLE);

        if ($connection->isTableExists($tableName)) {
            return;
        }

        $table = $connection->newTable($tableName)
            ->addColumn(
                'banner_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true],
                'Banner ID'
            )
            ->addColumn('rich_text_content', Table::TYPE_TEXT, null, ['nullable' => true], 'Rich Text Content')
            ->addColumn('button_text', Table::TYPE_TEXT, 255, ['nullable' => true], 'Button Text')
            ->addColumn('button_link', Table::TYPE_TEXT, 255, ['nullable' => true], 'Button Link')
            ->addColumn('start_date', Table::TYPE_DATETIME, null, ['nullable' => true], 'Start Date')
            ->addColumn('end_date', Table::TYPE_DATETIME, null, ['nullable' => true], 'End Date')
            ->addColumn('background_color', Table::TYPE_TEXT, 25, ['nullable' => true], 'Background Color')
            ->addColumn(
                'counter_background_color',
                Table::TYPE_TEXT,
                25,
                ['nullable' => true],
                'Counter Background Color'
            )
            ->addColumn('text_color', Table::TYPE_TEXT, 25, ['nullable' => true], 'Text Color')
            ->addColumn('is_active', Table::TYPE_BOOLEAN, null, ['nullable' => false, 'default' => false], 'Is Active')
            ->addColumn(
                'button_enabled',
                Table::TYPE_BOOLEAN,
                null,
                ['nullable' => false, 'default' => false],
                'Button Enabled'
            )
            ->addColumn(
                'counter_enabled',
                Table::TYPE_BOOLEAN,
                null,
                ['nullable' => false, 'default' => false],
                'Counter Enabled'
            )
            ->addColumn('banned_urls', Table::TYPE_TEXT, null, ['nullable' => true], 'Banned URLs')
            ->addColumn('start_date_button', Table::TYPE_DATETIME, null, ['nullable' => true], 'Start Date Button')
            ->addColumn('end_date_button', Table::TYPE_DATETIME, null, ['nullable' => true], 'End Date Button')
            ->addColumn('button_color_text', Table::TYPE_TEXT, 25, ['nullable' => true], 'Button Color Text')
            ->addColumn(
                'button_color_background',
                Table::TYPE_TEXT,
                25,
                ['nullable' => true],
                'Button Color Background'
            )
            ->addColumn('counter_color_text', Table::TYPE_TEXT, 25, ['nullable' => true], 'Counter Color Text')
            ->addColumn(
                'button_before',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => 0],
                'Show Button Before Counter'
            )
            ->addColumn(
                'store_id',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'unsigned' => true, 'default' => 0],
                'Legacy Store View ID'
            )
            ->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                'Created At'
            )
            ->addColumn(
                'updated_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
                'Updated At'
            )
            ->addIndex(
                $setup->getIdxName(self::MAIN_TABLE, ['is_active', 'start_date', 'end_date']),
                ['is_active', 'start_date', 'end_date']
            )
            ->addIndex(
                $setup->getIdxName(
                    self::MAIN_TABLE,
                    ['rich_text_content', 'button_text'],
                    AdapterInterface::INDEX_TYPE_FULLTEXT
                ),
                ['rich_text_content', 'button_text'],
                ['type' => AdapterInterface::INDEX_TYPE_FULLTEXT]
            )
            ->setComment('LeanZote Banner Table')
            ->setOption('charset', 'utf8mb4')
            ->setOption('collate', 'utf8mb4_unicode_ci');

        $connection->createTable($table);
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
}
