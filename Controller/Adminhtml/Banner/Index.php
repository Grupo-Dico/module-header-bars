<?php

namespace LeanCommerce\LeanZote\Controller\Adminhtml\Banner;

class Index extends \Magento\Backend\App\Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'LeanCommerce_LeanZote::banners';

	protected $resultPageFactory = false;

	public function __construct(
		\Magento\Backend\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory
	)
	{
		parent::__construct($context);
		$this->resultPageFactory = $resultPageFactory;
	}

	public function execute()
	{
		$resultPage = $this->resultPageFactory->create();
		
		// Establece el menú activo
		$resultPage->setActiveMenu('LeanCommerce_LeanZote::banners');
		
		// Añade breadcrumbs
		$resultPage->addBreadcrumb(__('LeanZote'), __('LeanZote'));
		$resultPage->addBreadcrumb(__('Banners'), __('Banners'));
		
		// Establece el título
		$resultPage->getConfig()->getTitle()->prepend((__('Banners')));

		return $resultPage;
	}
}