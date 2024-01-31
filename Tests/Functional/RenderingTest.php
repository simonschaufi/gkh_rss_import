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
use GertKaaeHansen\GkhRssImport\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RenderingTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const LANGUAGE_PRESETS = [
        'EN' => [
            'id' => 0,
            'title' => 'English',
            'locale' => 'en_US.UTF8',
        ],
        'DE' => [
            'id' => 1,
            'title' => 'German',
            'locale' => 'de_DE.UTF8',
        ],
    ];

    private const VALUE_PAGE_ID = 1;

    protected array $testExtensionsToLoad = ['typo3conf/ext/gkh_rss_import'];

    protected array $configurationToUseInTestInstance = [
        'GFX' => [
            // This is only needed for GitHub actions because gm is not installed
            'processor' => 'ImageMagick',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/pages.csv');
        $this->setUpFrontendRootPage(1, ['EXT:gkh_rss_import/Tests/Functional/Fixtures/Frontend/Basic.typoscript']);

        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1, 'http://localhost/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/'),
            ]
        );
    }

    #[Test]
    public function renderFeed(): void
    {
        $imageUrl = __DIR__ . '/Fixtures/Images/1-10.png';

        $lastRssServiceMock = $this->getMockBuilder(LastRssService::class)
            ->onlyMethods(['getFeed'])
            ->getMock();

        $lastRssServiceMock->method('getFeed')
            ->willReturn([
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
                                'length' => 2097152,
                            ],
                        ],
                    ],
                ],
                'items_count' => 1,
            ]);

        GeneralUtility::addInstance(LastRssService::class, $lastRssServiceMock);

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PAGE_ID));
        self::assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();

        $cacheIdentifier = sha1($imageUrl . '_.png') . '.png';
        $expectedFilePath = '/typo3temp/assets/images/cache/data/gkh_rss_import_image/' . $cacheIdentifier;

        // Feed header
        self::assertStringContainsString('RSS feed of example.com', $content, 'Title not found');
        self::assertStringContainsString('Example Description', $content, 'Description not found');
        self::assertStringContainsString('/typo3temp/assets/images/cache/data/gkh_rss_import_image/', $content, 'Image url not found');
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

    #[Test]
    public function renderFeedInGerman(): void
    {
        $this->setUpFrontendRootPage(1, ['EXT:gkh_rss_import/Tests/Functional/Fixtures/Frontend/Basic-custom-date-format.typoscript']);

        $imageUrl = __DIR__ . '/Fixtures/Images/1-10.png';

        $lastRssServiceMock = $this->getMockBuilder(LastRssService::class)
            ->onlyMethods(['getFeed'])
            ->getMock();

        $lastRssServiceMock->method('getFeed')
            ->willReturn([
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
                                'length' => 2097152,
                            ],
                        ],
                    ],
                ],
                'items_count' => 1,
            ]);

        GeneralUtility::addInstance(LastRssService::class, $lastRssServiceMock);

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PAGE_ID)->withLanguageId(1)
        );
        self::assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();

        $cacheIdentifier = sha1($imageUrl . '_.png') . '.png';
        $expectedFilePath = '/typo3temp/assets/images/cache/data/gkh_rss_import_image/' . $cacheIdentifier;

        // Feed header
        self::assertStringContainsString('RSS feed of example.com', $content, 'Title not found');
        self::assertStringContainsString('Example Description', $content, 'Description not found');
        self::assertStringContainsString('/typo3temp/assets/images/cache/data/gkh_rss_import_image/', $content, 'Image url not found');
        self::assertStringContainsString('Image Title', $content, 'Image title not found');
        self::assertStringContainsString('http://localhost/Images/1-10.png', $content, 'Image link not found');

        // Item
        self::assertStringContainsString('Freitag, 31. Januar 2020', $content, 'Item pubDate not found');
        self::assertStringContainsString('John Doe', $content, 'Item author not found');
        self::assertStringContainsString('CATEGORY', $content, 'Item category not found');
        self::assertStringContainsString('VERY LONG DESCRIPTION', $content, 'Item description not found');
        self::assertStringContainsString('http://localhost/download.pdf', $content, 'Item download link not found');
        self::assertStringContainsString('(2 MB)', $content, 'Item download size not found');

        // File cache
        self::assertFileExists(Environment::getPublicPath() . $expectedFilePath);
    }

    #[Test]
    public function renderFeedWithImageEnclosure(): void
    {
        $imageUrl = __DIR__ . '/Fixtures/Images/1-10.png';

        $lastRssServiceMock = $this->getMockBuilder(LastRssService::class)
            ->onlyMethods(['getFeed'])
            ->getMock();

        $lastRssServiceMock->method('getFeed')
            ->willReturn([
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
                    ],
                ],
                'items_count' => 1,
            ]);

        GeneralUtility::addInstance(LastRssService::class, $lastRssServiceMock);

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PAGE_ID));
        self::assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();

        $cacheIdentifier = sha1($imageUrl . '_.png') . '.png';
        $expectedFilePath = '/typo3temp/assets/images/cache/data/gkh_rss_import_image/' . $cacheIdentifier;

        // Feed header
        self::assertStringContainsString('RSS feed of example.com', $content, 'Title not found');
        self::assertStringContainsString('example.com Description', $content, 'Description not found');
        self::assertStringContainsString('/typo3temp/assets/images/cache/data/gkh_rss_import_image/', $content, 'Image url not found');
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

    #[Test]
    public function renderFeedWithErrorMessage(): void
    {
        $lastRssServiceMock = $this->getMockBuilder(LastRssService::class)
            ->onlyMethods(['getFeed'])
            ->getMock();

        $lastRssServiceMock->method('getFeed')
            ->willReturn(false);

        GeneralUtility::addInstance(LastRssService::class, $lastRssServiceMock);

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PAGE_ID));
        self::assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();

        self::assertStringContainsString(
            'It&#039;s not possible to reach the RSS feed.',
            $content,
            'Error message not found'
        );
    }
}
