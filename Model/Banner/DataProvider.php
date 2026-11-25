<?php
namespace LeanCommerce\LeanZote\Model\Banner;

use Magento\Ui\DataProvider\AbstractDataProvider;
use LeanCommerce\LeanZote\Model\ResourceModel\Banner\CollectionFactory;

class DataProvider extends AbstractDataProvider
{
    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $bannerCollectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $bannerCollectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $bannerCollectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (!$this->getCollection()->isLoaded()) {
            $this->getCollection()->load();
        }
        
        $items = $this->getCollection()->toArray();
        
        $rows = array_values($items['items'] ?? []);

        // El grid muestra texto plano: se quita el HTML del editor para que sea legible.
        foreach ($rows as &$row) {
            foreach (['rich_text_content', 'button_text'] as $field) {
                if (isset($row[$field])) {
                    $row[$field] = trim(html_entity_decode(strip_tags((string) $row[$field]), ENT_QUOTES, 'UTF-8'));
                }
            }
        }
        unset($row);

        return [
            'totalRecords' => $this->getCollection()->getSize(),
            'items' => $rows,
        ];
    }
}