<?php

namespace LeanCommerce\LeanZote\Model\Banner;

class ColorNormalizer
{
    private const SAFE_HEX_PATTERN = '/^#(?:[A-Fa-f0-9]{3}|[A-Fa-f0-9]{4}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/';
    private const BARE_HEX_PATTERN = '/^(?:[A-Fa-f0-9]{3}|[A-Fa-f0-9]{4}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/';
    private const RGB_PATTERN = '/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/i';
    private const RGBA_PATTERN =
        '/^rgba\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(0|1|0?\.\d+)\s*\)$/i';

    /**
     * @param mixed $value
     * @return string
     */
    public function normalize($value)
    {
        if (is_array($value)) {
            return $this->normalizeString($this->extractColorValue($value));
        }

        if (!is_string($value)) {
            return '';
        }

        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if ($value[0] === '{' || $value[0] === '[') {
            $decodedValue = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->normalize($decodedValue);
            }
        }

        return $this->normalizeString($value);
    }

    /**
     * @param mixed $value
     * @param string $default
     * @return string
     */
    public function normalizeForPicker($value, $default = '')
    {
        $color = $this->normalize($value);
        if ($color === '') {
            return $default;
        }

        $rgb = $this->extractRgbFromString($color);
        if ($rgb !== null) {
            return $this->buildHexColor($rgb[0], $rgb[1], $rgb[2]);
        }

        $hexColor = $this->normalizeHexColor($color);

        return strlen($hexColor) === 9 ? substr($hexColor, 0, 7) : $hexColor;
    }

    /**
     * @param array $value
     * @return string
     */
    private function extractColorValue(array $value)
    {
        $rgbColor = $this->extractRgbColor($value);
        if ($rgbColor !== '') {
            return $rgbColor;
        }

        foreach (['value', 'color', 'hex', 'background', 'text'] as $key) {
            if (!isset($value[$key])) {
                continue;
            }

            if (is_array($value[$key])) {
                $color = $this->extractColorValue($value[$key]);
                if ($color !== '') {
                    return $color;
                }

                continue;
            }

            $color = trim((string) $value[$key]);
            if ($this->normalizeString($color) !== '') {
                return $color;
            }
        }

        foreach ($value as $item) {
            if (is_array($item)) {
                $color = $this->extractColorValue($item);
                if ($color !== '') {
                    return $color;
                }

                continue;
            }

            if (!is_string($item)) {
                continue;
            }

            $color = trim($item);
            if ($this->normalizeString($color) !== '') {
                return $color;
            }
        }

        return '';
    }

    /**
     * @param array $value
     * @return string
     */
    private function extractRgbColor(array $value)
    {
        $red = $value['r'] ?? $value['red'] ?? null;
        $green = $value['g'] ?? $value['green'] ?? null;
        $blue = $value['b'] ?? $value['blue'] ?? null;
        $alpha = $value['a'] ?? $value['alpha'] ?? null;

        if (!is_numeric($red) || !is_numeric($green) || !is_numeric($blue)) {
            return '';
        }

        $red = (int) $red;
        $green = (int) $green;
        $blue = (int) $blue;
        if (!$this->isRgbChannel($red) || !$this->isRgbChannel($green) || !$this->isRgbChannel($blue)) {
            return '';
        }

        if ($alpha === null || $alpha === '' || (float) $alpha >= 1.0) {
            return $this->buildHexColor($red, $green, $blue);
        }

        if (!is_numeric($alpha) || (float) $alpha < 0.0 || (float) $alpha > 1.0) {
            return '';
        }

        return sprintf(
            'rgba(%d, %d, %d, %s)',
            $red,
            $green,
            $blue,
            rtrim(rtrim(number_format((float) $alpha, 3, '.', ''), '0'), '.')
        );
    }

    /**
     * @param string $value
     * @return string
     */
    private function normalizeString($value)
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (preg_match(self::BARE_HEX_PATTERN, $value)) {
            $value = '#' . $value;
        }

        if (preg_match(self::SAFE_HEX_PATTERN, $value)) {
            return $this->normalizeHexColor($value);
        }

        $rgb = $this->extractRgbFromString($value);
        if ($rgb === null) {
            return '';
        }

        if (!$this->isRgbChannel($rgb[0]) || !$this->isRgbChannel($rgb[1]) || !$this->isRgbChannel($rgb[2])) {
            return '';
        }

        if (!isset($rgb[3]) || (float) $rgb[3] >= 1.0) {
            return $this->buildHexColor($rgb[0], $rgb[1], $rgb[2]);
        }

        return sprintf(
            'rgba(%d, %d, %d, %s)',
            $rgb[0],
            $rgb[1],
            $rgb[2],
            rtrim(rtrim(number_format((float) $rgb[3], 3, '.', ''), '0'), '.')
        );
    }

    /**
     * @param string $value
     * @return array|null
     */
    private function extractRgbFromString($value)
    {
        if (preg_match(self::RGB_PATTERN, $value, $matches)) {
            return [(int) $matches[1], (int) $matches[2], (int) $matches[3]];
        }

        if (preg_match(self::RGBA_PATTERN, $value, $matches)) {
            return [(int) $matches[1], (int) $matches[2], (int) $matches[3], (float) $matches[4]];
        }

        return null;
    }

    /**
     * @param string $value
     * @return string
     */
    private function normalizeHexColor($value)
    {
        $hex = ltrim(trim($value), '#');
        if (strlen($hex) === 3 || strlen($hex) === 4) {
            $expandedHex = '';
            foreach (str_split($hex) as $character) {
                $expandedHex .= $character . $character;
            }

            $hex = $expandedHex;
        }

        return '#' . strtoupper($hex);
    }

    /**
     * @param int $red
     * @param int $green
     * @param int $blue
     * @return string
     */
    private function buildHexColor($red, $green, $blue)
    {
        return sprintf('#%02X%02X%02X', $red, $green, $blue);
    }

    /**
     * @param int $value
     * @return bool
     */
    private function isRgbChannel($value)
    {
        return $value >= 0 && $value <= 255;
    }
}
