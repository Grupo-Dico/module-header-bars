<?php
namespace LeanCommerce\LeanZote\Controller\Adminhtml\Banner;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;
use LeanCommerce\LeanZote\Model\BannerFactory;

class Edit extends Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'LeanCommerce_LeanZote::banners';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var BannerFactory
     */
    private $bannerFactory;

    /**
     * @param Action\Context $context
     * @param PageFactory $resultPageFactory
     * @param BannerFactory $bannerFactory
     */
    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory,
        BannerFactory $bannerFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->bannerFactory = $bannerFactory;
        parent::__construct($context);
    }

    /**
     * Edit banner
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('banner_id');
        $model = $this->bannerFactory->create();

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This banner no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('LeanCommerce_LeanZote::banners');
        
        if ($id) {
            $resultPage->getConfig()->getTitle()->prepend(__('Edit Banner'));
        } else {
            $resultPage->getConfig()->getTitle()->prepend(__('New Banner'));
        }

        return $resultPage;
    }
}