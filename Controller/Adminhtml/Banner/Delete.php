<?php
namespace LeanCommerce\LeanZote\Controller\Adminhtml\Banner;

use LeanCommerce\LeanZote\Model\BannerFactory;
use Magento\Backend\App\Action;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Type\Block;
use Magento\Framework\Controller\ResultInterface;
use Magento\PageCache\Model\Cache\Type as PageCache;

class Delete extends Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'LeanCommerce_LeanZote::banners';

    /**
     * @var BannerFactory
     */
    private $bannerFactory;

    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;

    /**
     * @param Action\Context $context
     * @param BannerFactory $bannerFactory
     * @param TypeListInterface $cacheTypeList
     */
    public function __construct(
        Action\Context $context,
        BannerFactory $bannerFactory,
        TypeListInterface $cacheTypeList
    ) {
        $this->bannerFactory = $bannerFactory;
        $this->cacheTypeList = $cacheTypeList;
        parent::__construct($context);
    }

    /**
     * Delete banner
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('banner_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        
        if ($id) {
            try {
                $model = $this->bannerFactory->create();
                $model->load($id);
                
                if (!$model->getId()) {
                    $this->messageManager->addErrorMessage(__('This banner no longer exists.'));
                    return $resultRedirect->setPath('*/*/');
                }
                
                $model->delete();
                $this->cleanBannerCaches();
                $this->messageManager->addSuccessMessage(__('The banner has been deleted.'));
                
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
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
