<?php

namespace LeanCommerce\LeanZote\Model\Banner;

use LeanCommerce\LeanZote\Model\ResourceModel\Banner\CollectionFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;
use Zend_Db_Expr;

class ActiveBannerProvider
{
    private const ALL_STORE_VIEWS = 0;
    private const STORE_TABLE = 'leanCommerce_leanZote_store';

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var ColorNormalizer
     */
    private $colorNormalizer;

    /**
     * @var BannedUrlNormalizer
     */
    private $bannedUrlNormalizer;

    public function __construct(
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        UrlInterface $urlBuilder,
        DateTime $dateTime,
        ColorNormalizer $colorNormalizer,
        BannedUrlNormalizer $bannedUrlNormalizer
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
        $this->urlBuilder = $urlBuilder;
        $this->dateTime = $dateTime;
        $this->colorNormalizer = $colorNormalizer;
        $this->bannedUrlNormalizer = $bannedUrlNormalizer;
    }

    /**
     * @param int|null $storeId
     * @param string $currentPath
     * @return array
     */
    public function getActiveBanners($storeId = null, $currentPath = '')
    {
        $storeId = $storeId === null ? $this->getCurrentStoreId() : (int) $storeId;
        $currentPath = $this->normalizePath($currentPath);
        $now = $this->dateTime->gmtDate('Y-m-d H:i:s');

        $collection = $this->collectionFactory->create();
        $storeTable = $collection->getTable(self::STORE_TABLE);

        $collection->addFieldToFilter('is_active', 1);
        $select = $collection->getSelect();
        $select->joinInner(
            ['banner_store' => $storeTable],
            'main_table.banner_id = banner_store.banner_id',
            []
        );
        $select->where('banner_store.store_id IN (?)', [self::ALL_STORE_VIEWS, $storeId]);
        $select->where('(main_table.start_date IS NULL OR main_table.start_date <= ?)', $now);
        $select->where('(main_table.end_date IS NULL OR main_table.end_date >= ?)', $now);
        $select->group('main_table.banner_id');
        $select->order(new Zend_Db_Expr('MAX(banner_store.store_id) DESC'));
        $select->order('main_table.banner_id DESC');

        $banners = [];
        foreach ($collection as $banner) {
            if (!$this->isAllowedForPath((string) $banner->getBannedUrls(), $currentPath)) {
                continue;
            }

            $banners[] = $this->buildPayload($banner, $storeId);
        }

        return $banners;
    }

    /**
     * @param int|null $storeId
     * @param string $currentPath
     * @return array|null
     */
    public function getActiveBanner($storeId = null, $currentPath = '')
    {
        $banners = $this->getActiveBanners($storeId, $currentPath);

        return $banners[0] ?? null;
    }

    /**
     * @param \LeanCommerce\LeanZote\Model\Banner $banner
     * @param int $storeId
     * @return array
     */
    private function buildPayload($banner, $storeId)
    {
        $counterStartDate = $banner->getStartDateButton() ?: $banner->getStartDate();
        $counterEndDate = $banner->getEndDateButton() ?: $banner->getEndDate();
        $backgroundColor = $this->sanitizeColor($banner->getBackgroundColor(), '#FFFFFF');
        $textColor = $this->sanitizeColor($banner->getTextColor(), '#333333');
        $buttonTextColor = $this->sanitizeColor($banner->getButtonColorText(), '#FFFFFF');
        $buttonBackgroundColor = $this->sanitizeColor($banner->getButtonColorBackground(), '#000000');
        $counterTextColor = $this->sanitizeColor($banner->getCounterColorText(), '#FFFFFF');
        $counterBackgroundColor = $this->sanitizeColor($banner->getCounterBackgroundColor(), '#000000');

        return [
            'id' => (int) $banner->getId(),
            'content' => (string) $banner->getRichTextContent(),
            'start_date' => $this->formatUtcDate($banner->getStartDate()),
            'end_date' => $this->formatUtcDate($banner->getEndDate()),
            'background_color' => $backgroundColor,
            'text_color' => $textColor,
            'button_color_text' => $buttonTextColor,
            'button_color_background' => $buttonBackgroundColor,
            'counter_bg_color' => $counterBackgroundColor,
            'counter_color_text' => $counterTextColor,
            'button' => [
                'enabled' => (bool) $banner->getButtonEnabled(),
                'text' => (string) $banner->getButtonText(),
                'link' => $this->sanitizeUrl($banner->getButtonLink(), $storeId),
                'before_counter' => (bool) $banner->getButtonBefore(),
                'text_color' => $buttonTextColor,
                'background_color' => $buttonBackgroundColor,
            ],
            'counter' => [
                'enabled' => (bool) $banner->getCounterEnabled() && !empty($counterEndDate),
                'start_date' => $this->formatUtcDate($counterStartDate),
                'end_date' => $this->formatUtcDate($counterEndDate),
                'text_color' => $counterTextColor,
                'background_color' => $counterBackgroundColor,
            ],
        ];
    }

    /**
     * @return int
     */
    private function getCurrentStoreId()
    {
        return (int) $this->storeManager->getStore()->getId();
    }

    /**
     * @param string|null $date
     * @return string|null
     */
    private function formatUtcDate($date)
    {
        if (!$date) {
            return null;
        }

        try {
            $dateTime = new \DateTime((string) $date, new \DateTimeZone('UTC'));
        } catch (\Exception $exception) {
            return null;
        }

        return $dateTime->format('Y-m-d\TH:i:s\Z');
    }

    /**
     * @param mixed $color
     * @param string $default
     * @return string
     */
    private function sanitizeColor($color, $default)
    {
        $color = $this->colorNormalizer->normalize($color);

        return $color !== '' ? $color : $default;
    }

    /**
     * @param string|null $url
     * @param int $storeId
     * @return string
     */
    private function sanitizeUrl($url, $storeId)
    {
        if (!is_string($url)) {
            return '';
        }

        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
            return $this->resolveRelativeUrl($url, $storeId);
        }

        if (strpos($url, '#') === 0) {
            return $url;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        if ($scheme && in_array(strtolower($scheme), ['http', 'https'], true)) {
            return $url;
        }

        return '';
    }

    /**
     * @param string $url
     * @param int $storeId
     * @return string
     */
    private function resolveRelativeUrl($url, $storeId)
    {
        try {
            return $this->urlBuilder->getUrl('', [
                '_direct' => ltrim($url, '/'),
                '_scope' => $storeId,
                '_scope_to_url' => true,
                '_nosid' => true,
            ]);
        } catch (\Exception $exception) {
            try {
                $baseUrl = $this->storeManager
                    ->getStore($storeId)
                    ->getBaseUrl(UrlInterface::URL_TYPE_WEB);
            } catch (\Exception $fallbackException) {
                return $url;
            }

            return rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
        }
    }

    /**
     * @param string $bannedUrls
     * @param string $currentPath
     * @return bool
     */
    private function isAllowedForPath($bannedUrls, $currentPath)
    {
        $bannedPaths = $this->bannedUrlNormalizer->getPathsForMatch($bannedUrls);
        if (!$bannedPaths) {
            return true;
        }

        foreach ($bannedPaths as $bannedPath) {
            if (stripos($currentPath, $bannedPath) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $path
     * @return string
     */
    private function normalizePath($path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '/';
        }

        $parsedPath = parse_url($path, PHP_URL_PATH);
        if (is_string($parsedPath) && $parsedPath !== '') {
            $path = $parsedPath;
        }

        if (strpos($path, '/') !== 0) {
            $path = '/' . $path;
        }

        return $path;
    }
}
