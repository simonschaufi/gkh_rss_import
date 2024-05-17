<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * (c) Gert Kaae Hansen, Simon Schaufelberger
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace GertKaaeHansen\GkhRssImport\Cache\Frontend;

use TYPO3\CMS\Core\Cache\Exception;
use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;
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
     * @param int|null $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
     * @throws Exception|InvalidDataException|\InvalidArgumentException
     */
    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null): void
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
     * @throws \InvalidArgumentException
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
     */
    public function isValidEntryIdentifier($identifier): bool
    {
        return preg_match('/^[a-zA-Z0-9_%\\-&.]{1,250}$/', $identifier) === 1;
    }
}
