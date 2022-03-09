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

use GertKaaeHansen\GkhRssImport\Service\LastRssService;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Exception as CoreException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class RenderingTest extends FunctionalTestCase
{
    protected const VALUE_PAGE_ID = 1;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/gkh_rss_import'];

    public function setUp(): void
    {
        parent::setUp();

        $this->importDataSet(__DIR__ . '/Fixtures/Database/pages.xml');
        $this->setUpFrontendRootPage(1, ['EXT:gkh_rss_import/Tests/Functional/Fixtures/Frontend/Basic.typoscript']);

        $this->setUpFrontendSite(1);
    }

    /**
     * Create a simple site config for the tests that
     * call a frontend page.
     *
     * @param int $pageId
     */
    protected function setUpFrontendSite(int $pageId): void
    {
        $configuration = [
            'rootPageId' => $pageId,
            'base' => '/',
            'websiteTitle' => '',
            'languages' => [
                [
                    'title' => 'English',
                    'enabled' => true,
                    'languageId' => '0',
                    'base' => '/',
                    'typo3Language' => 'default',
                    'locale' => 'en_US.UTF-8',
                    'iso-639-1' => 'en',
                    'websiteTitle' => 'Site EN',
                    'navigationTitle' => '',
                    'hreflang' => '',
                    'direction' => '',
                    'flag' => 'us',
                ]
            ],
            'errorHandling' => [],
            'routes' => [],
        ];
        GeneralUtility::mkdir_deep($this->instancePath . '/typo3conf/sites/testing/');
        $yamlFileContents = Yaml::dump($configuration, 99, 2);
        $fileName = $this->instancePath . '/typo3conf/sites/testing/config.yaml';
        GeneralUtility::writeFile($fileName, $yamlFileContents);
    }

    /**
     * @test
     * @throws InvalidDataException
     * @throws NoSuchCacheException
     * @throws CoreException
     */
    public function renderFeed(): void
    {
        $imageUrl = __DIR__ . '/Fixtures/Images/1-10.png';

        $lastRssServiceMock = $this->getMockBuilder(LastRssService::class)
            ->onlyMethods(['getFeed'])
            ->getMock();

        $lastRssServiceMock->method('getFeed')->willReturn([
            'encoding' => 'UTF-8',
            'title' => 'RSS feed of example.com',
            'link' => 'http://localhost/',
            'description' => 'Example Description',
            'language' => 'de-DE',
            'lastBuildDate' => '05/19/2020',
            'image_url' => $imageUrl,
            'image_title' => 'Image Title',
            'image_link' => 'http://localhost/Images/1-10.png',
            'items' => [
                [
                    'title' => 'Example Title',
                    'link' => 'http://localhost/example-title.html',
                    'description' => 'VERY LONG DESCRIPTION',
                    'category' => 'CATEGORY',
                    'guid' => 'http://localhost/?p=1',
                    'pubDate' => '01/31/2020',
                    'author' => 'John Doe',
                    'content' => 'CONTENT',
                    'enclosure' => [
                        'prop' => [
                            'url' => 'http://localhost/download.pdf',
                            'length' => 2097152
                        ]
                    ]
                ]
            ],
            'items_count' => 1
        ]);

        GeneralUtility::addInstance(LastRssService::class, $lastRssServiceMock);

        $request = (new InternalRequest())->withPageId(self::VALUE_PAGE_ID);

        $response = $this->executeFrontendSubRequest($request);
        self::assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();

        $cacheIdentifier = sha1($imageUrl . '_.png') . '.png';
        $expectedFilePath = '/typo3temp/assets/images/cache/data/gkh_rss_import_image/' . $cacheIdentifier;

        // Feed header
        self::assertStringContainsString('RSS feed of example.com', $content, 'Title not found');
        self::assertStringContainsString('Example Description', $content, 'Description not found');
        self::assertStringContainsString($expectedFilePath, $content, 'Image url not found');
        self::assertStringContainsString('Image Title', $content, 'Image title not found');
        self::assertStringContainsString('http://localhost/Images/1-10.png', $content, 'Image link not found');

        // Item
        self::assertStringContainsString('31/01 - 2020', $content, 'Item pubDate not found');
        self::assertStringContainsString('John Doe', $content, 'Item author not found');
        self::assertStringContainsString('CATEGORY', $content, 'Item category not found');
        self::assertStringContainsString('VERY LONG DESCRIPTION', $content, 'Item description not found');
        self::assertStringContainsString('http://localhost/download.pdf', $content, 'Item download link not found');
        self::assertStringContainsString('(2 MB)', $content, 'Item download size not found');

        // File cache
        self::assertFileExists(Environment::getPublicPath() . $expectedFilePath);
    }

    /**
     * @test
     * @throws InvalidDataException
     * @throws NoSuchCacheException
     * @throws CoreException
     */
    public function renderFeedWithImageEnclosure(): void
    {
        $imageUrl = __DIR__ . '/Fixtures/Images/1-10.png';

        $lastRssServiceMock = $this->getMockBuilder(LastRssService::class)
            ->onlyMethods(['getFeed'])
            ->getMock();

        $lastRssServiceMock->method('getFeed')->willReturn([
            'encoding' => 'UTF-8',
            'title' => 'RSS feed of example.com',
            'link' => 'https://www.example.com/rss.html?type=123&amp;cHash=xxx',
            'description' => 'example.com Description',
            'language' => 'de',
            'lastBuildDate' => '10/30/2020',
            'image_url' => $imageUrl,
            'image_title' => 'Image Title',
            'image_link' => 'https://www.example.com/rss.html?type=123&amp;cHash=xxx',
            'image_width' => '273',
            'image_height' => '121',
            'items' => [
                [
                    'title' => 'Example Title',
                    'link' => 'https://www.example.com/item.html',
                    'description' => 'VERY LONG DESCRIPTION',
                    'guid' => 'https://www.example.com/item.html',
                    'pubDate' => '10/30/2020',
                    'enclosure' => [
                        'prop' => [
                            'url' => 'https://www.example.com/typo3temp/pics/d69f9ef8c4.jpg',
                            'type' => 'image/jpeg',
                        ],
                    ],
                ]
            ],
            'items_count' => 1,
        ]);

        GeneralUtility::addInstance(LastRssService::class, $lastRssServiceMock);

        $request = (new InternalRequest())->withPageId(self::VALUE_PAGE_ID);

        $response = $this->executeFrontendSubRequest($request);
        self::assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();

        $cacheIdentifier = sha1($imageUrl . '_.png') . '.png';
        $expectedFilePath = '/typo3temp/assets/images/cache/data/gkh_rss_import_image/' . $cacheIdentifier;

        // Feed header
        self::assertStringContainsString('RSS feed of example.com', $content, 'Title not found');
        self::assertStringContainsString('example.com Description', $content, 'Description not found');
        self::assertStringContainsString($expectedFilePath, $content, 'Image url not found');
        self::assertStringContainsString('Image Title', $content, 'Image title not found');
        self::assertStringContainsString(
            'https://www.example.com/rss.html?type=123&amp;cHash=xxx',
            $content,
            'Image link not found'
        );

        // Item
        self::assertStringContainsString('30/10 - 2020', $content, 'Item pubDate not found');
        self::assertStringContainsString('VERY LONG DESCRIPTION', $content, 'Item description not found');
        self::assertStringContainsString(
            'https://www.example.com/typo3temp/pics/d69f9ef8c4.jpg',
            $content,
            'Item download link not found'
        );

        // File cache
        self::assertFileExists(Environment::getPublicPath() . $expectedFilePath);
    }

    /**
     * @test
     * @throws InvalidDataException
     * @throws NoSuchCacheException
     * @throws CoreException
     */
    public function renderFeedWithErrorMessage(): void
    {
        $lastRssServiceMock = $this->getMockBuilder(LastRssService::class)
            ->onlyMethods(['getFeed'])
            ->getMock();

        $lastRssServiceMock->method('getFeed')->willReturn(false);

        GeneralUtility::addInstance(LastRssService::class, $lastRssServiceMock);

        $request = (new InternalRequest())->withPageId(self::VALUE_PAGE_ID);

        $response = $this->executeFrontendSubRequest($request);
        self::assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();

        self::assertStringContainsString(
            'It\'s not possible to reach the RSS feed.',
            $content,
            'Error message not found'
        );
    }
}
