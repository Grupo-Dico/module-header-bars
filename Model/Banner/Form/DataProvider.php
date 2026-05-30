<?php
namespace LeanCommerce\LeanZote\Model\Banner\Form;

use LeanCommerce\LeanZote\Model\Banner\ColorNormalizer;
use LeanCommerce\LeanZote\Model\Banner\StoreAssignment;
use LeanCommerce\LeanZote\Model\ResourceModel\Banner\CollectionFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

class DataProvider extends AbstractDataProvider
{
    private const MEXICO_CITY_FIXED_OFFSET_UTC_START = '2022-10-30 08:00:00';
    private const MEXICO_CITY_TIMEZONES = [
        'America/Mexico_City',
        'Mexico/General',
    ];
    private const DATE_FIELDS = [
        'start_date',
        'end_date',
        'start_date_button',
        'end_date_button',
    ];
    private const COLOR_DEFAULTS = [
        'background_color' => '#FFFFFF',
        'text_color' => '#333333',
        'button_color_background' => '#000000',
        'button_color_text' => '#FFFFFF',
        'counter_background_color' => '#000000',
        'counter_color_text' => '#FFFFFF',
    ];

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var StoreAssignment
     */
    private $storeAssignment;

    /**
     * @var ColorNormalizer
     */
    private $colorNormalizer;

    /**
     * @var TimezoneInterface
     */
    private $localeDate;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $bannerCollectionFactory
     * @param StoreAssignment $storeAssignment
     * @param ColorNormalizer $colorNormalizer
     * @param TimezoneInterface $localeDate
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $bannerCollectionFactory,
        StoreAssignment $storeAssignment,
        ColorNormalizer $colorNormalizer,
        TimezoneInterface $localeDate,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $bannerCollectionFactory->create();
        $this->storeAssignment = $storeAssignment;
        $this->colorNormalizer = $colorNormalizer;
        $this->localeDate = $localeDate;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        
        $this->loadedData = [];
        $items = $this->collection->getItems();
        foreach ($items as $banner) {
            $data = $banner->getData();
            $this->normalizeColorFieldsForPicker($data);
            $this->normalizeDateFieldsForPicker($data);
            $data['store_id'] = array_map(
                'strval',
                $this->storeAssignment->getAssignedStoreIds(
                    (int) $banner->getId(),
                    isset($data['store_id']) ? (int) $data['store_id'] : 0
                )
            );

            $this->loadedData[$banner->getId()] = $data;
        }
        
        return $this->loadedData;
    }

    /**
     * @param array $data
     * @return void
     */
    private function normalizeColorFieldsForPicker(array &$data)
    {
        foreach (self::COLOR_DEFAULTS as $field => $default) {
            $data[$field] = $this->colorNormalizer->normalizeForPicker($data[$field] ?? '', $default);
        }
    }

    /**
     * @param array $data
     * @return void
     */
    private function normalizeDateFieldsForPicker(array &$data)
    {
        foreach (self::DATE_FIELDS as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $data[$field] = $this->formatUtcDateForPicker($data[$field]);
        }
    }

    /**
     * @param string|null $date
     * @return string|null
     */
    private function formatUtcDateForPicker($date)
    {
        if (!$date) {
            return null;
        }

        try {
            $dateTime = new \DateTimeImmutable((string) $date, new \DateTimeZone('UTC'));
            $dateTime = $dateTime->setTimezone(new \DateTimeZone($this->getDisplayTimezone((string) $date)));
        } catch (\Exception $exception) {
            return $date;
        }

        return $dateTime->format('Y-m-d H:i:s');
    }

    /**
     * @param string $utcDateValue
     * @return string
     */
    private function getDisplayTimezone($utcDateValue)
    {
        try {
            $timezone = (string) $this->localeDate->getConfigTimezone();
        } catch (\Exception $exception) {
            return 'UTC';
        }

        if (!in_array($timezone, self::MEXICO_CITY_TIMEZONES, true)) {
            return $timezone;
        }

        if ($utcDateValue >= self::MEXICO_CITY_FIXED_OFFSET_UTC_START) {
            return 'Etc/GMT+6';
        }

        return $timezone;
    }
}
