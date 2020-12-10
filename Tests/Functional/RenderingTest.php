<?php

declare(strict_types=1);

namespace GertKaaeHansen\GkhRssImport\Tests\Functional;

use GertKaaeHansen\GkhRssImport\Service\LastRssService;
use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Exception as CoreException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RenderingTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/gkh_rss_import'];

    public function setUp(): void
    {
        // Hacky as the request type set by the testing framework is "BE | CLI" and then a logged in backend user is
        // required in several places in the core
        define('TYPO3_REQUESTTYPE', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        parent::setUp();

        $this->importDataSet(__DIR__ . '/Fixtures/Database/pages.xml');
        $this->setUpFrontendRootPage(1, ['EXT:gkh_rss_import/Tests/Functional/Fixtures/Frontend/Basic.typoscript']);
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
            ->setMethods(['getFeed'])
            ->getMock();

        $lastRssServiceMock->method('getFeed')->willReturn([
            'encoding' => 'UTF-8',
            'title' => 'RSS feed of example.com',
            'link' => 'http://localhost/',
            'description' => 'Example Description',
            'language' => 'de-DE',
            'lastBuildDate' => '05/19/2020',
            'image_url' => $imageUrl,
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

        $response = $this->getResponseContent($this->internalRequest(1));

        $cacheIdentifier = sha1($imageUrl . '_.png') . '.png';
        $expectedFilePath = '/typo3temp/assets/images/cache/data/gkh_rss_import_image/' . $cacheIdentifier;

        // Feed header
        self::assertStringContainsString('RSS feed of example.com', $response, 'Title not found');
        self::assertStringContainsString('Example Description', $response, 'Description not found');
        self::assertStringContainsString($expectedFilePath, $response, 'Image url not found');
        self::assertStringContainsString('http://localhost/Images/1-10.png', $response, 'Image link not found');

        // Item
        self::assertStringContainsString('31/01 - 2020', $response, 'Item pubDate not found');
        self::assertStringContainsString('John Doe', $response, 'Item author not found');
        self::assertStringContainsString('CATEGORY', $response, 'Item category not found');
        self::assertStringContainsString('VERY LONG DESCRIPTION', $response, 'Item description not found');
        self::assertStringContainsString('http://localhost/download.pdf', $response, 'Item download link not found');
        self::assertStringContainsString('(2 MB)', $response, 'Item download size not found');

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
            ->setMethods(['getFeed'])
            ->getMock();

        $lastRssServiceMock->method('getFeed')->willReturn([
            'encoding' => 'UTF-8',
            'title' => 'RSS feed of example.com',
            'link' => 'https://www.example.com/rss.html?type=123&amp;cHash=xxx',
            'description' => 'example.com Description',
            'language' => 'de',
            'lastBuildDate' => '10/30/2020',
            'image_title' => 'example.com',
            'image_url' => $imageUrl,
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

        $response = $this->getResponseContent($this->internalRequest(1));

        $cacheIdentifier = sha1($imageUrl . '_.png') . '.png';
        $expectedFilePath = '/typo3temp/assets/images/cache/data/gkh_rss_import_image/' . $cacheIdentifier;

        // Feed header
        self::assertStringContainsString('RSS feed of example.com', $response, 'Title not found');
        self::assertStringContainsString('example.com Description', $response, 'Description not found');
        self::assertStringContainsString($expectedFilePath, $response, 'Image url not found');
        self::assertStringContainsString(
            'https://www.example.com/rss.html?type=123&amp;cHash=xxx',
            $response,
            'Image link not found'
        );

        // Item
        self::assertStringContainsString('30/10 - 2020', $response, 'Item pubDate not found');
        self::assertStringContainsString('VERY LONG DESCRIPTION', $response, 'Item description not found');
        self::assertStringContainsString(
            'https://www.example.com/typo3temp/pics/d69f9ef8c4.jpg',
            $response,
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
            ->setMethods(['getFeed'])
            ->getMock();

        $lastRssServiceMock->method('getFeed')->willReturn(false);

        GeneralUtility::addInstance(LastRssService::class, $lastRssServiceMock);

        $response = $this->getResponseContent($this->internalRequest(1));

        self::assertStringContainsString(
            'It\'s not possible to reach the RSS feed.',
            $response,
            'Error message not found'
        );
    }
}
