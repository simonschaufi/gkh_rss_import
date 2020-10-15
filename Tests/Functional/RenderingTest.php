<?php
declare(strict_types=1);

namespace GertKaaeHansen\GkhRssImport\Tests\Functional;

use GertKaaeHansen\GkhRssImport\Service\LastRssService;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\MiddlewareDispatcher;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Http\SelfEmittableStreamInterface;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Http\RequestHandler;

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
     */
    public function renderFeed(): void
    {
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

        $response = $this->internalRequest(1);

        // Feed header
        self::assertStringContainsString('RSS feed of example.com', $response, 'RSS title not found');
        self::assertStringContainsString('Example Description', $response, 'RSS description not found');

        // Item
        self::assertStringContainsString('31/01 - 2020', $response, 'RSS item pubDate not found');
        self::assertStringContainsString('John Doe', $response, 'RSS item author not found');
        self::assertStringContainsString('CATEGORY', $response, 'RSS item category not found');
        self::assertStringContainsString('VERY LONG DESCRIPTION', $response, 'RSS item description not found');
        self::assertStringContainsString('http://localhost/download.pdf', $response, 'RSS item download link not found');
        self::assertStringContainsString('(2 MB)', $response, 'RSS item download size not found');
    }

    private function internalRequest(int $id = 1): ?string
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['REQUEST_URI'] = '/';

        $_GET['id'] = $id;

        SystemEnvironmentBuilder::run(3, SystemEnvironmentBuilder::REQUESTTYPE_FE);

        $requestHandler = $this->getRequestHandler(GeneralUtility::getContainer());
        $response = $requestHandler->handle(ServerRequestFactory::fromGlobals());

        return $this->getResponseContent($response);
    }

    private function getResponseContent(ResponseInterface $response): ?string
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

    private function getRequestHandler(ContainerInterface $container): MiddlewareDispatcher
    {
        return new MiddlewareDispatcher(
            $container->get(RequestHandler::class),
            $container->get('frontend.middlewares'),
            $container
        );
    }
}
