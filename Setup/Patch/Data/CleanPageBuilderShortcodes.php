<?php
declare(strict_types=1);

namespace LeanCommerce\LeanZote\Setup\Patch\Data;

use LeanCommerce\LeanZote\Model\Banner\RichTextSanitizer;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Limpia los banners guardados con el shortcode [mgz_pagebuilder] de Magezon.
 *
 * Esos registros mostraban el JSON del Page Builder en la barra y en el grid. Se reemplaza el
 * valor por el HTML saneado de su texto, conservando formato y emojis.
 */
class CleanPageBuilderShortcodes implements DataPatchInterface
{
    private const TABLE = 'leanCommerce_leanZote';

    private const BLOCK_FIELDS = [
        'rich_text_content',
        'mobile_text_content',
        'desktop_text_content_2',
        'mobile_text_content_2',
        'desktop_text_content_3',
        'mobile_text_content_3',
    ];

    private const INLINE_FIELDS = [
        'button_text',
        'desktop_button_text_1',
        'mobile_button_text_1',
        'desktop_button_text_2',
        'mobile_button_text_2',
        'desktop_button_text_3',
        'mobile_button_text_3',
        'counter_text',
        'counter_numbers_format',
        'counter_labels_format',
        'counter_separator_format',
    ];

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var RichTextSanitizer
     */
    private $richTextSanitizer;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param RichTextSanitizer $richTextSanitizer
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        RichTextSanitizer $richTextSanitizer
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->richTextSanitizer = $richTextSanitizer;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable(self::TABLE);

        if (!$connection->isTableExists($table)) {
            return $this;
        }

        $connection->query("SET NAMES 'utf8mb4'");

        $columns = array_keys($connection->describeTable($table));
        $fields = [];
        foreach (self::BLOCK_FIELDS as $field) {
            $fields[$field] = RichTextSanitizer::MODE_BLOCK;
        }
        foreach (self::INLINE_FIELDS as $field) {
            $fields[$field] = RichTextSanitizer::MODE_INLINE;
        }
        $fields = array_intersect_key($fields, array_flip($columns));

        if (!$fields) {
            return $this;
        }

        $rows = $connection->fetchAll(
            $connection->select()->from($table, array_merge(['banner_id'], array_keys($fields)))
        );

        foreach ($rows as $row) {
            $update = [];
            foreach ($fields as $field => $mode) {
                $value = (string) ($row[$field] ?? '');
                if (stripos($value, '[mgz_pagebuilder]') === false) {
                    continue;
                }
                $update[$field] = $this->richTextSanitizer->sanitize($value, $mode);
            }

            if ($update) {
                $connection->update($table, $update, ['banner_id = ?' => (int) $row['banner_id']]);
            }
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
