<?php
declare(strict_types=1);

namespace LeanCommerce\LeanZote\Model\Banner;

/**
 * Sanitiza el HTML producido por el editor de texto del Admin.
 *
 * Solo conserva etiquetas de formato de texto y un conjunto cerrado de propiedades de estilo
 * (tipo de letra, tamaño, negrita, cursiva, subrayado, color y alineación). Todo lo demás
 * (scripts, atributos, eventos, enlaces, imágenes, etc.) se elimina. Los emojis se conservan.
 */
class RichTextSanitizer
{
    public const MODE_BLOCK = 'block';
    public const MODE_INLINE = 'inline';

    /**
     * Tamaño máximo permitido (px) para que un texto no desborde la barra.
     */
    public const MAX_FONT_SIZE_PX = 32;

    private const INLINE_TAGS = [
        'span', 'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'del', 'sub', 'sup', 'br',
    ];

    private const BLOCK_TAGS = ['p', 'div'];

    /**
     * Etiquetas que se descartan junto con su contenido.
     */
    private const DROP_TAGS = [
        'script', 'style', 'iframe', 'object', 'embed', 'link', 'meta', 'noscript', 'template',
        'svg', 'math', 'form', 'input', 'button', 'textarea', 'select', 'img', 'video', 'audio', 'head',
    ];

    private const NAMED_COLORS_PATTERN = '/^[a-zA-Z]{3,30}$/';
    private const HEX_COLOR_PATTERN = '/^#[0-9a-fA-F]{3,8}$/';
    private const RGB_COLOR_PATTERN =
        '/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*(0|1|0?\.\d+)\s*)?\)$/';
    private const FONT_FAMILY_PATTERN = '/^[\p{L}\p{N} ,\'"\-]+$/u';

    /**
     * @param mixed $html
     * @param string $mode MODE_BLOCK (mensajes) o MODE_INLINE (CTA y contador).
     * @return string HTML seguro, o cadena vacía si no hay contenido visible.
     */
    public function sanitize($html, string $mode = self::MODE_BLOCK): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $previous = libxml_use_internal_errors(true);
        $document = new \DOMDocument('1.0', 'UTF-8');
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8"?><div id="leanzote-root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            return $this->escapePlain($html);
        }

        $root = $document->getElementById('leanzote-root');
        if (!$root) {
            return $this->escapePlain($html);
        }

        $this->cleanChildren($root, $mode);

        $output = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $output .= $document->saveHTML($child);
        }
        $output = trim($output);

        return $this->hasVisibleContent($output) ? $output : '';
    }

    /**
     * Indica si el HTML tiene texto o emojis visibles (no solo etiquetas/espacios vacíos).
     *
     * @param mixed $html
     * @return bool
     */
    public function hasVisibleContent($html): bool
    {
        $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[\s\x{00A0}\x{200B}]+/u', '', $text);

        return $text !== null && $text !== '';
    }

    /**
     * @param \DOMNode $node
     * @param string $mode
     * @return void
     */
    private function cleanChildren(\DOMNode $node, string $mode): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof \DOMText) {
                continue;
            }
            if (!$child instanceof \DOMElement) {
                $node->removeChild($child);
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROP_TAGS, true)) {
                $node->removeChild($child);
                continue;
            }

            $this->cleanChildren($child, $mode);

            if ($tag === 'br') {
                $this->stripAttributes($child);
                continue;
            }

            if (in_array($tag, self::INLINE_TAGS, true)) {
                $this->filterAttributes($child);
                continue;
            }

            if (in_array($tag, self::BLOCK_TAGS, true) && $mode === self::MODE_BLOCK) {
                $this->filterAttributes($child);
                continue;
            }

            // Cualquier otra etiqueta (o bloque en modo inline) se reemplaza por un span que conserva
            // el contenido y el estilo de texto permitido.
            $this->replaceWithSpan($node, $child, $mode);
        }
    }

    /**
     * @param \DOMNode $parent
     * @param \DOMElement $element
     * @param string $mode
     * @return void
     */
    private function replaceWithSpan(\DOMNode $parent, \DOMElement $element, string $mode): void
    {
        $document = $element->ownerDocument;
        $span = $document->createElement('span');
        $style = $this->filterStyle((string) $element->getAttribute('style'));
        if ($style !== '') {
            $span->setAttribute('style', $style);
        }

        while ($element->firstChild) {
            $span->appendChild($element->firstChild);
        }

        $isBlock = in_array(strtolower($element->tagName), array_merge(self::BLOCK_TAGS, [
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'pre', 'blockquote', 'li',
        ]), true);

        $parent->replaceChild($span, $element);

        // En modo inline los bloques consecutivos se separan con salto de línea.
        if ($isBlock && $mode === self::MODE_INLINE && $span->nextSibling) {
            $parent->insertBefore($document->createElement('br'), $span->nextSibling);
        }
    }

    /**
     * @param \DOMElement $element
     * @return void
     */
    private function filterAttributes(\DOMElement $element): void
    {
        $style = $this->filterStyle((string) $element->getAttribute('style'));
        $this->stripAttributes($element);
        if ($style !== '') {
            $element->setAttribute('style', $style);
        }
    }

    /**
     * @param \DOMElement $element
     * @return void
     */
    private function stripAttributes(\DOMElement $element): void
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            $element->removeAttribute($attribute->name);
        }
    }

    /**
     * @param string $style
     * @return string
     */
    private function filterStyle(string $style): string
    {
        $allowed = [];

        foreach (explode(';', $style) as $declaration) {
            $parts = explode(':', $declaration, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $property = strtolower(trim($parts[0]));
            $value = trim(str_ireplace('!important', '', $parts[1]));
            if ($value === '' || preg_match('/(url\s*\(|expression|javascript|[<>\\\\;{}])/i', $value)) {
                continue;
            }

            $clean = $this->filterDeclaration($property, $value);
            if ($clean !== null) {
                $allowed[$property] = $clean;
            }
        }

        $result = [];
        foreach ($allowed as $property => $value) {
            $result[] = $property . ': ' . $value;
        }

        return implode('; ', $result);
    }

    /**
     * @param string $property
     * @param string $value
     * @return string|null
     */
    private function filterDeclaration(string $property, string $value): ?string
    {
        switch ($property) {
            case 'font-family':
                return preg_match(self::FONT_FAMILY_PATTERN, $value) ? $value : null;

            case 'font-size':
                return $this->normalizeFontSize($value);

            case 'font-weight':
                return preg_match('/^(normal|bold|bolder|lighter|[1-9]00)$/i', $value) ? strtolower($value) : null;

            case 'font-style':
                return preg_match('/^(normal|italic|oblique)$/i', $value) ? strtolower($value) : null;

            case 'text-decoration':
            case 'text-decoration-line':
                return preg_match('/^(none|underline|line-through|overline)( (underline|line-through|overline))*$/i', $value)
                    ? strtolower($value)
                    : null;

            case 'text-align':
                return preg_match('/^(left|right|center|justify)$/i', $value) ? strtolower($value) : null;

            case 'color':
            case 'background-color':
                return $this->isSafeColor($value) ? $value : null;

            case 'line-height':
                return preg_match('/^\d{1,3}(\.\d+)?(px|em|rem|%)?$/', $value) ? $value : null;
        }

        return null;
    }

    /**
     * @param string $value
     * @return string|null
     */
    private function normalizeFontSize(string $value): ?string
    {
        if (!preg_match('/^(\d{1,3}(?:\.\d+)?)(px|pt|em|rem|%)$/i', $value, $matches)) {
            if (preg_match('/^(xx-small|x-small|small|medium|large|x-large)$/i', $value)) {
                return strtolower($value);
            }
            return null;
        }

        $number = (float) $matches[1];
        $unit = strtolower($matches[2]);
        $px = $number;
        switch ($unit) {
            case 'pt':
                $px = $number * 96 / 72;
                break;
            case 'em':
            case 'rem':
                $px = $number * 16;
                break;
            case '%':
                $px = $number * 16 / 100;
                break;
        }

        if ($px > self::MAX_FONT_SIZE_PX) {
            return self::MAX_FONT_SIZE_PX . 'px';
        }

        return $number . $unit;
    }

    /**
     * @param string $value
     * @return bool
     */
    private function isSafeColor(string $value): bool
    {
        return (bool) (
            preg_match(self::HEX_COLOR_PATTERN, $value)
            || preg_match(self::RGB_COLOR_PATTERN, $value)
            || preg_match(self::NAMED_COLORS_PATTERN, $value)
        );
    }

    /**
     * @param string $text
     * @return string
     */
    private function escapePlain(string $text): string
    {
        return htmlspecialchars(strip_tags($text), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
