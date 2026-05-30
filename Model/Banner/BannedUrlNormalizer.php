<?php

namespace LeanCommerce\LeanZote\Model\Banner;

use Magento\Framework\Exception\LocalizedException;

class BannedUrlNormalizer
{
    /**
     * @param mixed $value
     * @return string
     * @throws LocalizedException
     */
    public function normalizeForStorage($value)
    {
        if ($value === null || trim((string) $value) === '') {
            return '';
        }

        $paths = [];
        foreach (explode(',', (string) $value) as $entry) {
            $entry = trim($entry);
            if ($entry === '') {
                throw new LocalizedException(
                    __('Remove empty entries from "Ocultar banner en estas URLs".')
                );
            }

            $paths[] = $this->normalizeEntry($entry);
        }

        return implode(', ', array_values(array_unique($paths)));
    }

    /**
     * @param mixed $value
     * @return string[]
     */
    public function getPathsForMatch($value)
    {
        if ($value === null || trim((string) $value) === '') {
            return [];
        }

        $paths = [];
        foreach (explode(',', (string) $value) as $entry) {
            $entry = trim($entry);
            if ($entry === '') {
                continue;
            }

            try {
                $paths[] = $this->normalizeEntry($entry);
            } catch (LocalizedException $exception) {
                continue;
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * @param string $entry
     * @return string
     * @throws LocalizedException
     */
    private function normalizeEntry($entry)
    {
        if (preg_match('/\s/', $entry)) {
            throw new LocalizedException(
                __('Invalid URL in "Ocultar banner en estas URLs": %1', $entry)
            );
        }

        if (strpos($entry, '//') === 0) {
            throw new LocalizedException(
                __('Protocol-relative URLs are not allowed in "Ocultar banner en estas URLs".')
            );
        }

        $scheme = parse_url($entry, PHP_URL_SCHEME);
        if ($scheme && !in_array(strtolower($scheme), ['http', 'https'], true)) {
            throw new LocalizedException(
                __('Only http and https URLs are allowed in "Ocultar banner en estas URLs".')
            );
        }

        if ($scheme) {
            $path = parse_url($entry, PHP_URL_PATH);
        } else {
            if (strpos($entry, '/') !== 0) {
                throw new LocalizedException(
                    __('Use paths that start with "/" in "Ocultar banner en estas URLs".')
                );
            }

            $path = parse_url($entry, PHP_URL_PATH);
        }

        if (!is_string($path) || $path === '' || strpos($path, '/') !== 0) {
            throw new LocalizedException(
                __('Invalid URL in "Ocultar banner en estas URLs": %1', $entry)
            );
        }

        return $path;
    }
}
