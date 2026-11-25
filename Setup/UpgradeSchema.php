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
     * Columnas que antes eran VARCHAR(255) y ahora guardan HTML del editor de texto.
     */
    private const RICH_TEXT_COLUMNS = [
        'button_text',
        'desktop_button_text_1',
        'mobile_button_text_1',
        'desktop_button_text_2',
        'mobile_button_text_2',
        'desktop_button_text_3',
        'mobile_button_text_3',
    ];

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

        if (version_compare($context->getVersion(), '1.0.5', '<')) {
            $this->addColumnIfMissing($setup, $tableName, 'mobile_text_content', [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'Mobile Text Content',
            ]);
            $this->addColumnIfMissing($setup, $tableName, 'desktop_text_content_2', [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'Desktop Text Content 2',
            ]);
            $this->addColumnIfMissing($setup, $tableName, 'mobile_text_content_2', [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'Mobile Text Content 2',
            ]);
            $this->addColumnIfMissing($setup, $tableName, 'desktop_text_content_3', [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'Desktop Text Content 3',
            ]);
            $this->addColumnIfMissing($setup, $tableName, 'mobile_text_content_3', [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'Mobile Text Content 3',
            ]);
        }


        if (version_compare($context->getVersion(), '1.0.6', '<')) {
            $this->addColumnIfMissing($setup, $tableName, 'carousel_interval', [
                'type' => Table::TYPE_SMALLINT,
                'nullable' => false,
                'unsigned' => true,
                'default' => 5,
                'comment' => 'Carousel Interval Seconds',
            ]);
        }

        // 1.0.8 repairs the 1.0.7 schema migration. In 1.0.7 these columns
        // were accidentally placed inside the "table does not exist" branch, so
        // existing installations could reach schema_version 1.0.7 without them.
        if (version_compare($context->getVersion(), '1.0.8', '<')) {
            $this->addColumnIfMissing($setup, $tableName, 'desktop_highlight_1', [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'Desktop Highlight 1',
            ]);
            $this->addColumnIfMissing($setup, $tableName, 'desktop_button_text_1', [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'comment' => 'Desktop Button Text 1',
            ]);
            $this->addColumnIfMissing($setup, $tableName, 'desktop_button_link_1', [
                'type' => Table::TYPE_TEXT,
                'length' => 1024,
                'nullable' => true,
                'comment' => 'Desktop Button Link 1',
            ]);
            $this->addColumnIfMissing($setup, $tableName, 'mobile_highlight_1', [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'Mobile Highlight 1',
            ]);
            $this->addColumnIfMissing($setup, $tableName, 'mobile_button_text_1', [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'comment' => 'Mobile Button Text 1',
            ]);
            $this->addColumnIfMissing($setup, $tableName, 'mobile_button_link_1', [
                'type' => Table::TYPE_TEXT,
                'length' => 1024,
                'nullable' => true,
                'comment' => 'Mobile Button Link 1',
            ]);
            $this->addColumnIfMissing($setup, $tableName, 'desktop_highlight_2', [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'Desktop Highlight 2',
            ]);
            $this->addColumnIfMissing($setup, $tableName, 'desktop_button_text_2', [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'comment' => 'Desktop Button Text 2',
            ]);
            $this->addColumnIfMissing($setup, $tableName, 'desktop_button_link_2', [
                'type' => Table::TYPE_TEXT,
                'length' => 1024,
                'nullable' => true,
                'comment' => 'Desktop Button Link 2',
            ]);
            $this->addColumnIfMissing($setup, $tableName, 'mobile_highlight_2', [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'Mobile Highlight 2',
            ]);
            $this->addColumnIfMissing($setup, $tableName, 'mobile_button_text_2', [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'comment' => 'Mobile Button Text 2',
            ]);
            $this->addColumnIfMissing($setup, $tableName, 'mobile_button_link_2', [
                'type' => Table::TYPE_TEXT,
                'length' => 1024,
                'nullable' => true,
                'comment' => 'Mobile Button Link 2',
            ]);
            $this->addColumnIfMissing($setup, $tableName, 'desktop_highlight_3', [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'Desktop Highlight 3',
            ]);
            $this->addColumnIfMissing($setup, $tableName, 'desktop_button_text_3', [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'comment' => 'Desktop Button Text 3',
            ]);
            $this->addColumnIfMissing($setup, $tableName, 'desktop_button_link_3', [
                'type' => Table::TYPE_TEXT,
                'length' => 1024,
                'nullable' => true,
                'comment' => 'Desktop Button Link 3',
            ]);
            $this->addColumnIfMissing($setup, $tableName, 'mobile_highlight_3', [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'Mobile Highlight 3',
            ]);
            $this->addColumnIfMissing($setup, $tableName, 'mobile_button_text_3', [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'comment' => 'Mobile Button Text 3',
            ]);
            $this->addColumnIfMissing($setup, $tableName, 'mobile_button_link_3', [
                'type' => Table::TYPE_TEXT,
                'length' => 1024,
                'nullable' => true,
                'comment' => 'Mobile Button Link 3',
            ]);
        }

        if (version_compare($context->getVersion(), '1.0.11', '<')) {
            // DW-360: textos con formato (HTML) y emojis.
            // Los índices FULLTEXT exigen el mismo collation en todas sus columnas, por eso se quitan
            // mientras se altera la tabla y se recrean al terminar.
            $fulltextIndexes = $this->dropFulltextIndexes($setup, $tableName);
            $this->convertTableToUtf8mb4($setup, $tableName);
            $this->widenColumnsToText($setup, $tableName, self::RICH_TEXT_COLUMNS);
            $this->addColumnIfMissing($setup, $tableName, 'counter_text', [
                'type' => Table::TYPE_TEXT,
                'length' => '64k',
                'nullable' => true,
                'comment' => 'Counter Accompanying Text (HTML)',
            ]);
            $this->addColumnIfMissing($setup, $tableName, 'counter_numbers_format', [
                'type' => Table::TYPE_TEXT,
                'length' => '64k',
                'nullable' => true,
                'comment' => 'Counter Numbers Format Sample (HTML)',
            ]);
            $this->restoreFulltextIndexes($setup, $tableName, $fulltextIndexes);
        }

        if (version_compare($context->getVersion(), '1.0.12', '<')) {
            // DW-360: formato de etiquetas y separadores del contador.
            $this->addColumnIfMissing($setup, $tableName, 'counter_labels_format', [
                'type' => Table::TYPE_TEXT,
                'length' => '64k',
                'nullable' => true,
                'comment' => 'Counter Labels Format Sample (HTML)',
            ]);
            $this->addColumnIfMissing($setup, $tableName, 'counter_separator_format', [
                'type' => Table::TYPE_TEXT,
                'length' => '64k',
                'nullable' => true,
                'comment' => 'Counter Separator Format Sample (HTML)',
            ]);
        }

        $setup->endSetup();
    }

    /**
     * Convierte la tabla a utf8mb4 para poder guardar emojis (caracteres de 4 bytes).
     *
     * @param SchemaSetupInterface $setup
     * @param string $tableName
     * @return void
     */
    private function convertTableToUtf8mb4(SchemaSetupInterface $setup, $tableName)
    {
        $connection = $setup->getConnection();
        $connection->query(
            sprintf(
                'ALTER TABLE %s CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
                $connection->quoteIdentifier($tableName)
            )
        );
    }

    /**
     * Cambia columnas existentes a TEXT utf8mb4 conservando su contenido.
     *
     * Se usa ALTER explícito (no modifyColumn) para fijar el mismo charset y collation que el resto de
     * la tabla y evitar que Magento aplique uno distinto.
     *
     * @param SchemaSetupInterface $setup
     * @param string $tableName
     * @param string[] $columns
     * @return void
     */
    private function widenColumnsToText(SchemaSetupInterface $setup, $tableName, array $columns)
    {
        $connection = $setup->getConnection();
        foreach ($columns as $column) {
            if (!$connection->tableColumnExists($tableName, $column)) {
                continue;
            }
            $connection->query(
                sprintf(
                    'ALTER TABLE %s MODIFY COLUMN %s TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT %s',
                    $connection->quoteIdentifier($tableName),
                    $connection->quoteIdentifier($column),
                    $connection->quote(ucwords(str_replace('_', ' ', $column)) . ' (HTML)')
                )
            );
        }
    }

    /**
     * Quita los índices FULLTEXT de la tabla y devuelve su definición para recrearlos después.
     *
     * @param SchemaSetupInterface $setup
     * @param string $tableName
     * @return array<string, string[]> nombre del índice => columnas
     */
    private function dropFulltextIndexes(SchemaSetupInterface $setup, $tableName)
    {
        $connection = $setup->getConnection();
        $dropped = [];
        foreach ($connection->getIndexList($tableName) as $name => $index) {
            if (strtolower((string) ($index['INDEX_TYPE'] ?? '')) !== AdapterInterface::INDEX_TYPE_FULLTEXT) {
                continue;
            }
            $keyName = (string) ($index['KEY_NAME'] ?? $name);
            $dropped[$keyName] = array_values($index['COLUMNS_LIST']);
            $connection->dropIndex($tableName, $keyName);
        }

        return $dropped;
    }

    /**
     * Recrea los índices FULLTEXT que se quitaron antes de alterar la tabla.
     *
     * @param SchemaSetupInterface $setup
     * @param string $tableName
     * @param array<string, string[]> $indexes
     * @return void
     */
    private function restoreFulltextIndexes(SchemaSetupInterface $setup, $tableName, array $indexes)
    {
        $connection = $setup->getConnection();
        if (!$indexes) {
            // Reintento tras un upgrade interrumpido: el índice ya pudo haberse quitado.
            $defaultColumns = ['rich_text_content', 'button_text'];
            $defaultName = $setup->getIdxName(self::MAIN_TABLE, $defaultColumns, AdapterInterface::INDEX_TYPE_FULLTEXT);
            $hasFulltext = false;
            foreach ($connection->getIndexList($tableName) as $index) {
                if (strtolower((string) ($index['INDEX_TYPE'] ?? '')) === AdapterInterface::INDEX_TYPE_FULLTEXT) {
                    $hasFulltext = true;
                    break;
                }
            }
            $indexes = $hasFulltext ? [] : [$defaultName => $defaultColumns];
        }
        foreach ($indexes as $name => $columns) {
            if (isset($connection->getIndexList($tableName)[strtoupper($name)])) {
                continue;
            }
            $connection->addIndex($tableName, $name, $columns, AdapterInterface::INDEX_TYPE_FULLTEXT);
        }
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
