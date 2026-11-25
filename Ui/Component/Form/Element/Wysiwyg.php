<?php
declare(strict_types=1);

namespace LeanCommerce\LeanZote\Ui\Component\Form\Element;

use Magento\Framework\Data\FormFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Form\Element\Wysiwyg as MagentoWysiwyg;
use Magento\Ui\Component\Wysiwyg\ConfigInterface;
use Magento\Ui\Model\Validation\WysiwygValidationConfigResolver;

/**
 * Editor de texto (TinyMCE) para los campos de la barra superior.
 *
 * Magezon_PageBuilder declara una preference sobre Magento\Ui\Component\Form\Element\Wysiwyg que
 * activa su Page Builder en todos los WYSIWYG y omite la configuración utf8mb4 de Magento 2.4.9.
 * Como esta clase tiene otro nombre, la preference no aplica: el campo usa el TinyMCE nativo
 * (sin shortcodes [mgz_pagebuilder]) y la validación permite emojis de 4 bytes (🔥, 🎉, etc.).
 */
class Wysiwyg extends MagentoWysiwyg
{
    /**
     * @param ContextInterface $context
     * @param FormFactory $formFactory
     * @param ConfigInterface $wysiwygConfig
     * @param array $components
     * @param array $data
     * @param array $config
     * @param WysiwygValidationConfigResolver|null $validationConfigResolver
     */
    public function __construct(
        ContextInterface $context,
        FormFactory $formFactory,
        ConfigInterface $wysiwygConfig,
        array $components = [],
        array $data = [],
        array $config = [],
        ?WysiwygValidationConfigResolver $validationConfigResolver = null
    ) {
        // La tabla del módulo es utf8mb4, por lo que los emojis de 4 bytes siempre son válidos.
        $config['allowUtf8mb4'] = true;
        $config['wysiwygConfigData']['allowUtf8mb4'] = true;
        $config['wysiwygConfigData']['is_pagebuilder_enabled'] = false;

        parent::__construct(
            $context,
            $formFactory,
            $wysiwygConfig,
            $components,
            $data,
            $config,
            $validationConfigResolver
        );
    }
}
