<?php

declare(strict_types=1);

namespace GertKaaeHansen\GkhRssImport\Cache\Frontend;

use TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend;

/**
 * A cache frontend for images
 */
class ImageFrontend extends AbstractFrontend
{
    /**
     * Saves data in the cache.
     *
     * @param string $entryIdentifier Something which identifies the data - depends on concrete cache
     * @param mixed $data The data to cache - also depends on the concrete cache implementation
     * @param array $tags Tags to associate with this cache entry
     * @param null $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
     * @throws \TYPO3\CMS\Core\Cache\Exception
     * @throws \TYPO3\CMS\Core\Cache\Exception\InvalidDataException
     */
    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null)
    {
        if (!$this->isValidEntryIdentifier($entryIdentifier)) {
            throw new \InvalidArgumentException(
                '"' . $entryIdentifier . '" is not a valid cache entry identifier.',
                1233058264
            );
        }

        $this->backend->set($entryIdentifier, $data, $tags, $lifetime);
    }

    /**
     * Finds and returns data from the cache.
     *
     * @param string $entryIdentifier Something which identifies the cache entry - depends on concrete cache
     * @return mixed
     */
    public function get($entryIdentifier)
    {
        if (!$this->isValidEntryIdentifier($entryIdentifier)) {
            throw new \InvalidArgumentException(
                '"' . $entryIdentifier . '" is not a valid cache entry identifier.',
                1233058294
            );
        }
        $rawResult = $this->backend->get($entryIdentifier);
        if ($rawResult === false) {
            return false;
        }
        return $rawResult;
    }

    /**
     * Checks the validity of an entry identifier. Returns TRUE if it's valid.
     *
     * @param string $identifier An identifier to be checked for validity
     * @return bool
     */
    public function isValidEntryIdentifier($identifier)
    {
        return preg_match('/^[a-zA-Z0-9_%\\-&.]{1,250}$/', $identifier) === 1;
    }
}
