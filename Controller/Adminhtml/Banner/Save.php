<?php

namespace LeanCommerce\LeanZote\Controller\Adminhtml\Banner;

use LeanCommerce\LeanZote\Model\Banner\BannedUrlNormalizer;
use LeanCommerce\LeanZote\Model\Banner\ColorNormalizer;
use LeanCommerce\LeanZote\Model\Banner\StoreAssignment;
use LeanCommerce\LeanZote\Model\BannerFactory;
use Magento\Backend\App\Action;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Type\Block;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\PageCache\Model\Cache\Type as PageCache;

class Save extends Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'LeanCommerce_LeanZote::banners';

    /**
     * Campos permitidos para guardar (whitelist para evitar mass assignment)
     */
    private const ALLOWED_FIELDS = [
        'rich_text_content',
        'button_text',
        'button_link',
        'start_date',
        'end_date',
        'background_color',
        'counter_background_color',
        'text_color',
        'is_active',
        'button_enabled',
        'counter_enabled',
        'banned_urls',
        'start_date_button',
        'end_date_button',
        'button_color_text',
        'button_color_background',
        'counter_color_text',
        'button_before',
        'store_id',
    ];

    /**
     * Mexico City no longer observes DST for current/future promotion dates,
     * but some PHP tzdata versions still apply UTC-5 in summer.
     */
    private const MEXICO_CITY_FIXED_OFFSET_START = '2022-10-30 02:00:00';
    private const MEXICO_CITY_TIMEZONES = [
        'America/Mexico_City',
        'Mexico/General',
    ];

    /**
     * @var BannerFactory
     */
    private $bannerFactory;

    /**
     * @var StoreAssignment
     */
    private $storeAssignment;

    /**
     * @var ColorNormalizer
     */
    private $colorNormalizer;

    /**
     * @var BannedUrlNormalizer
     */
    private $bannedUrlNormalizer;

    /**
     * @var TimezoneInterface
     */
    private $localeDate;

    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;

    /**
     * @param Action\Context $context
     * @param BannerFactory $bannerFactory
     * @param StoreAssignment $storeAssignment
     * @param ColorNormalizer $colorNormalizer
     * @param BannedUrlNormalizer $bannedUrlNormalizer
     * @param TimezoneInterface $localeDate
     * @param TypeListInterface $cacheTypeList
     */
    public function __construct(
        Action\Context $context,
        BannerFactory $bannerFactory,
        StoreAssignment $storeAssignment,
        ColorNormalizer $colorNormalizer,
        BannedUrlNormalizer $bannedUrlNormalizer,
        TimezoneInterface $localeDate,
        TypeListInterface $cacheTypeList
    ) {
        $this->bannerFactory = $bannerFactory;
        $this->storeAssignment = $storeAssignment;
        $this->colorNormalizer = $colorNormalizer;
        $this->bannedUrlNormalizer = $bannedUrlNormalizer;
        $this->localeDate = $localeDate;
        $this->cacheTypeList = $cacheTypeList;
        parent::__construct($context);
    }

    /**
     * Save banner
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        $formData = $data['data'] ?? $data;
        $id = $this->getRequest()->getParam('banner_id')
            ?? $this->getRequest()->getParam('id')
            ?? $formData['banner_id']
            ?? $formData['id']
            ?? null;
        $id = $id ? (int) $id : null;

        /** @var \LeanCommerce\LeanZote\Model\Banner $model */
        $model = $this->bannerFactory->create();
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This banner no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            }
        }

        $storeIds = $this->storeAssignment->normalizeStoreIds($formData['store_id'] ?? []);

        // =========================
        // VALIDACIONES DE FECHAS
        // =========================
        try {
            $startDate = $this->createUtcDate($formData['start_date'] ?? null);
            $endDate = $this->createUtcDate($formData['end_date'] ?? null);
            $startDateButton = $this->createUtcDate($formData['start_date_button'] ?? null);
            $endDateButton = $this->createUtcDate($formData['end_date_button'] ?? null);

            // startDateButton y endDateButton deben existir juntos
            if (($startDateButton && !$endDateButton) || (!$startDateButton && $endDateButton)) {
                throw new \Exception(__('Las fechas del contador deben incluir fecha de inicio y fecha de finalización.'));
            }

            // end_date >= start_date
            if ($startDate && $endDate && $endDate < $startDate) {
                throw new \Exception(__('La fecha de finalización no puede ser anterior a la fecha de inicio.'));
            }

            // startDateButton dentro del rango
            if ($startDateButton && $startDate && $startDateButton < $startDate) {
                throw new \Exception(__('La fecha de inicio del contador no puede ser anterior a la fecha de inicio del banner.'));
            }

            if ($startDateButton && $endDate && $startDateButton > $endDate) {
                throw new \Exception(__('La fecha de inicio del contador no puede ser posterior a la fecha de finalización del banner.'));
            }

            // endDateButton >= startDateButton
            if ($startDateButton && $endDateButton && $endDateButton < $startDateButton) {
                throw new \Exception(__('La fecha de finalización del contador no puede ser anterior a la fecha de inicio del contador.'));
            }

            // endDateButton <= endDate
            if ($endDateButton && $endDate && $endDateButton > $endDate) {
                throw new \Exception(__('La fecha de finalización del contador no puede ser posterior a la fecha de finalización del banner.'));
            }

            if (array_key_exists('banned_urls', $formData)) {
                $formData['banned_urls'] = $this->bannedUrlNormalizer->normalizeForStorage($formData['banned_urls']);
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect->setPath('*/*/edit', ['banner_id' => $id]);
        }

        $safeData = array_intersect_key($formData, array_flip(self::ALLOWED_FIELDS));
        $safeData['store_id'] = reset($storeIds);
        $this->normalizeDateFields($safeData, [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_date_button' => $startDateButton,
            'end_date_button' => $endDateButton,
        ]);
        $this->normalizeColorFields($safeData);
        $model->addData($safeData);
        try {
            $model->save();
            $this->storeAssignment->saveAssignments((int) $model->getId(), $storeIds);
            $this->cleanBannerCaches();
            $this->messageManager->addSuccessMessage(__('You saved the banner.'));

            // Save and Continue
            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['banner_id' => $model->getId()]);
            }

            return $resultRedirect->setPath('*/*/');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect->setPath('*/*/edit', ['banner_id' => $id]);
        }
    }

    /**
     * @param mixed $value
     * @return \DateTimeImmutable|null
     * @throws \Exception
     */
    private function createUtcDate($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            throw new \Exception(__('Invalid date value.'));
        }

        $value = trim((string) $value);
        $effectiveTimezone = $this->getConfigTimezone($value);
        $date = new \DateTimeImmutable(
            $value,
            new \DateTimeZone($effectiveTimezone)
        );

        return $date->setTimezone(new \DateTimeZone('UTC'));
    }

    /**
     * @param string $dateValue
     * @return string
     */
    private function getConfigTimezone($dateValue)
    {
        try {
            $timezone = (string) $this->localeDate->getConfigTimezone();
        } catch (\Exception $exception) {
            return 'UTC';
        }

        return $this->resolveTimezone($timezone, $dateValue);
    }

    /**
     * @param string $timezone
     * @param string $dateValue
     * @return string
     */
    private function resolveTimezone($timezone, $dateValue)
    {
        if (!in_array($timezone, self::MEXICO_CITY_TIMEZONES, true)) {
            return $timezone;
        }

        $normalizedDate = str_replace('T', ' ', substr($dateValue, 0, 19));
        if ($normalizedDate >= self::MEXICO_CITY_FIXED_OFFSET_START) {
            return 'Etc/GMT+6';
        }

        return $timezone;
    }

    /**
     * @param array $data
     * @param \DateTimeImmutable[]|null[] $dates
     * @return void
     */
    private function normalizeDateFields(array &$data, array $dates)
    {
        foreach ($dates as $field => $date) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $data[$field] = $date ? $date->format('Y-m-d H:i:s') : null;
        }
    }

    /**
     * @param array $data
     * @return void
     */
    private function normalizeColorFields(array &$data)
    {
        $colorFields = [
            'background_color',
            'counter_background_color',
            'text_color',
            'button_color_text',
            'button_color_background',
            'counter_color_text',
        ];

        foreach ($colorFields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $data[$field] = $this->colorNormalizer->normalize($data[$field]);
        }
    }

    /**
     * @return void
     */
    private function cleanBannerCaches()
    {
        $this->cacheTypeList->cleanType(Block::TYPE_IDENTIFIER);
        $this->cacheTypeList->cleanType(PageCache::TYPE_IDENTIFIER);
    }
}
