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

namespace GertKaaeHansen\GkhRssImport\Tests\Functional;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Http\MiddlewareDispatcher;
use TYPO3\CMS\Core\Http\MiddlewareStackResolver;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Http\SelfEmittableStreamInterface;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Frontend\Http\RequestHandler;

/**
 * Class FunctionalTestCase
 *
 * @see https://github.com/pixelant/pxa_lpeh/blob/7db2fd26e867ecc45172b5f14a1cfb9dbbb6e02a/Classes/Error/PageErrorHandler/LocalPageErrorHandler.php
 */
class FunctionalTestCase extends \Nimut\TestingFramework\TestCase\FunctionalTestCase
{
    /**
     * @param int $pageId
     * @return ResponseInterface|null
     * @throws InvalidDataException
     * @throws NoSuchCacheException
     * @throws \TYPO3\CMS\Core\Exception
     */
    protected function internalRequest(int $pageId = 1): ?ResponseInterface
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $uri = new Uri('http://localhost/');
        $request = $this->createServerRequest(
            'GET',
            $uri,
            $_SERVER
        );

        $site = $this->resolveSite($request, $pageId);
        $pageIsValid = $this->isPageValid($pageId);
        if ($site === null || !$pageIsValid) {
            return null;
        }

        $response = $this->buildSubRequest($request, $pageId);
        return $response->withStatus($response->getStatusCode());
    }

    /**
     * Create a new server request.
     *
     * Note that server-params are taken precisely as given - no parsing/processing
     * of the given values is performed, and, in particular, no attempt is made to
     * determine the HTTP method or URI, which must be provided explicitly.
     *
     * @param string $method The HTTP method associated with the request.
     * @param UriInterface|string $uri The URI associated with the request.
     * @param array $serverParams Array of SAPI parameters with which to seed the generated request instance.
     * @return ServerRequestInterface
     * @deprecated Can be removed and replaced with ServerRequestFactory when TYPO3 9 support is dropped
     */
    private function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return new ServerRequest($uri, $method, 'php://input', [], $serverParams);
    }

    /**
     * @param ServerRequestInterface $request
     * @param int $pageId
     * @return ResponseInterface
     * @throws InvalidDataException
     * @throws NoSuchCacheException
     * @throws \TYPO3\CMS\Core\Exception
     */
    protected function buildSubRequest(ServerRequestInterface $request, int $pageId): ResponseInterface
    {
        $request = $request->withQueryParams(['id' => $pageId]);
        return $this->buildDispatcher()->handle($request);
    }

    /**
     * @return MiddlewareDispatcher
     * @throws InvalidDataException
     * @throws NoSuchCacheException
     * @throws \TYPO3\CMS\Core\Exception
     */
    protected function buildDispatcher(): MiddlewareDispatcher
    {
        $requestHandler = GeneralUtility::makeInstance(RequestHandler::class);

        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class)->getVersion();
        if (VersionNumberUtility::convertVersionNumberToInteger($typo3Version) >= 10000000) {
            $resolver = GeneralUtility::makeInstance(
                MiddlewareStackResolver::class
            );
        } else {
            // Can be removed when TYPO3 9 support is dropped
            $resolver = GeneralUtility::makeInstance(
                MiddlewareStackResolver::class,
                GeneralUtility::makeInstance(PackageManager::class),
                GeneralUtility::makeInstance(DependencyOrderingService::class),
                GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_core')
            );
        }

        $middlewares = $resolver->resolve('frontend');
        return new MiddlewareDispatcher($requestHandler, $middlewares);
    }

    protected function getResponseContent(ResponseInterface $response): ?string
    {
        if ($response instanceof NullResponse) {
            return null;
        }

        $body = $response->getBody();
        if ($body instanceof SelfEmittableStreamInterface) {
            // Optimization for streams that use php functions like readfile() as fastpath for serving files.
            $body->emit();
        } else {
            return $body->__toString();
        }

        return null;
    }

    /**
     * @param ServerRequestInterface $request
     * @param int $pageId
     * @return Site|null
     */
    protected function resolveSite(ServerRequestInterface &$request, int $pageId): ?Site
    {
        $site = $request->getAttribute('site', null);
        if (!$site instanceof Site) {
            try {
                $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pageId);
                $request = $request->withAttribute('site', $site);
            } catch (\Throwable $th) {
                return null;
            }
        }
        return $request->getAttribute('site', null);
    }

    /**
     * Resolve PageId, make sure there is a translated version of the page.
     *
     * @param ServerRequestInterface $request
     * @param int $pageId
     * @return int|null
     */
    protected function resolvePageId(ServerRequestInterface $request, int $pageId): ?int
    {
        $siteLanguage = $request->getAttribute('language');
        if ($siteLanguage instanceof SiteLanguage) {
            $languageId = $siteLanguage->getLanguageId() ?? 0;
            if ($languageId > 0) {
                $translatedPageId = $this->getLocalizedPageId($pageId, $languageId);

                foreach ($siteLanguage->getFallbackLanguageIds() as $languageId) {
                    if ($translatedPageId !== null) {
                        break;
                    }

                    $translatedPageId = $this->getLocalizedPageId($pageId, $languageId);
                }

                return $translatedPageId;
            }
        }
        return $pageId;
    }

    /**
     * Get localized page id
     *
     * @param int $pageId
     * @param int $languageId
     * @return int|null
     */
    protected function getLocalizedPageId(int $pageId, int $languageId): ?int
    {
        $page = BackendUtility::getRecordLocalization(
            'pages',
            $pageId,
            $languageId
        );

        if ($page === false || empty($page)) {
            return null;
        }
        return $page[0]['uid'];
    }

    /**
     * @param int $pageId
     * @return bool
     */
    protected function isPageValid(int $pageId): bool
    {
        try {
            GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pageId);
        } catch (\Throwable $th) {
            return false;
        }
        return true;
    }
}
