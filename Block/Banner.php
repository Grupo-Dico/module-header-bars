<?php

namespace LeanCommerce\LeanZote\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Banner extends Template
{
    /**
     * Patrón para validar colores CSS seguros (hex, rgb, rgba)
     */
    private const SAFE_COLOR_PATTERN =
        '/^(#[A-Fa-f0-9]{3,8}|rgb\(\s*(?:\d{1,3}\s*,){2}\s*\d{1,3}\s*\)|rgba\(\s*(?:\d{1,3}\s*,){3}\s*(?:0|1|0?\.\d+)\s*\))$/';

    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getEndpointUrl()
    {
        return $this->getUrl('leanzote/banner/active', ['_secure' => $this->getRequest()->isSecure()]);
    }

    /**
     * Sanitiza un valor de color CSS para evitar inyección
     *
     * @param string|null $color
     * @param string $default
     * @return string
     */
    public function escapeCssColor($color, $default = '#000000')
    {
        if (empty($color) || !is_string($color)) {
            return $default;
        }
        $trimmed = trim($color);
        if (preg_match(self::SAFE_COLOR_PATTERN, $trimmed)) {
            return $trimmed;
        }
        return $default;
    }

    /**
     * @return array
     */
    public function getCacheKeyInfo()
    {
        return [
            'LEANZOTE_BANNER_PLACEHOLDER',
            (int) $this->_storeManager->getStore()->getId(),
            $this->getTemplateFile(),
        ];
    }
}
