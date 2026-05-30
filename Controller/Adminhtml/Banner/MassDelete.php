<?php

namespace LeanCommerce\LeanZote\Controller\Adminhtml\Banner;

use LeanCommerce\LeanZote\Model\ResourceModel\Banner\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Type\Block;
use Magento\PageCache\Model\Cache\Type as PageCache;
use Magento\Ui\Component\MassAction\Filter;

class MassDelete extends Action
{
    const ADMIN_RESOURCE = 'LeanCommerce_LeanZote::banners';

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;

    public function __construct(
        Action\Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        TypeListInterface $cacheTypeList
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->cacheTypeList = $cacheTypeList;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            // Usamos el filtro que maneja selected/excluded automáticamente
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $deletedCount = 0;

            foreach ($collection as $banner) {
                $banner->delete();
                $deletedCount++;
            }

            if ($deletedCount > 0) {
                $this->cleanBannerCaches();
                $this->messageManager->addSuccessMessage(
                    __('A total of %1 record(s) have been deleted.', $deletedCount)
                );
            } else {
                $this->messageManager->addErrorMessage(__('No records were deleted.'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect->setPath('*/*/');
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
