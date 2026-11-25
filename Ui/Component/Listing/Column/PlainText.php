<?php
declare(strict_types=1);

namespace LeanCommerce\LeanZote\Ui\Component\Listing\Column;

use LeanCommerce\LeanZote\Model\Banner\RichTextSanitizer;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Muestra en el grid solo el texto (con emojis) de un campo con formato, sin etiquetas HTML
 * ni shortcodes de Page Builder.
 */
class PlainText extends Column
{
    /**
     * @var RichTextSanitizer
     */
    private $richTextSanitizer;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param RichTextSanitizer $richTextSanitizer
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        RichTextSanitizer $richTextSanitizer,
        array $components = [],
        array $data = []
    ) {
        $this->richTextSanitizer = $richTextSanitizer;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        $field = $this->getData('name');

        foreach ($dataSource['data']['items'] ?? [] as $index => $item) {
            if (!isset($item[$field])) {
                continue;
            }

            $html = $this->richTextSanitizer->unwrapPageBuilderShortcode((string) $item[$field]);
            $html = preg_replace('/<br\s*\/?>|<\/(p|div)>/i', ' ', $html);
            $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            $dataSource['data']['items'][$index][$field] = trim((string) preg_replace('/\s+/u', ' ', $text));
        }

        return $dataSource;
    }
}
