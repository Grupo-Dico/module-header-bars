<?php

namespace LeanCommerce\LeanZote\Controller\Banner;

use LeanCommerce\LeanZote\Model\Banner\ActiveBannerProvider;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;

class Active implements HttpGetActionInterface
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ActiveBannerProvider
     */
    private $activeBannerProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        JsonFactory $resultJsonFactory,
        RequestInterface $request,
        ActiveBannerProvider $activeBannerProvider,
        LoggerInterface $logger
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->request = $request;
        $this->activeBannerProvider = $activeBannerProvider;
        $this->logger = $logger;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $result->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
        $result->setHeader('Pragma', 'no-cache', true);
        $result->setHeader('Expires', '0', true);

        try {
            $banners = $this->activeBannerProvider->getActiveBanners(
                null,
                (string) $this->request->getParam('current_path', '')
            );

            return $result->setData([
                'success' => true,
                'banners' => $banners,
            ]);
        } catch (\Throwable $exception) {
            $this->logger->error($exception);

            return $result->setData([
                'success' => false,
                'banners' => [],
            ]);
        }
    }
}
