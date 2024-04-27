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

namespace GertKaaeHansen\GkhRssImport\Tests\Unit\Controller;

use GertKaaeHansen\GkhRssImport\Controller\RssImportController;
use GertKaaeHansen\GkhRssImport\Tests\Unit\Fixtures\Controller\RssImportControllerFixture;
use GertKaaeHansen\GkhRssImport\Tests\Unit\Page\PageRendererFactoryTrait;
use PHPUnit\Framework\MockObject\Exception;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\LanguageStore;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Page\ImportMap;
use TYPO3\CMS\Core\Page\ImportMapFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RssImportControllerTest extends UnitTestCase
{
    use PageRendererFactoryTrait;

    private RssImportController $subject;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $site = $this->createSiteWithLanguage();

        /** @see https://github.com/TYPO3/typo3/blob/58fb6ad4b00e1a72d1e728e1db19760a52ff1449/typo3/sysext/frontend/Tests/Unit/ContentObject/Menu/AbstractMenuContentObjectTest.php#L61-L102 */
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://www.example.com', 'GET'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);

        $cacheManagerMock = $this->createMock(CacheManager::class);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerMock);

        $cacheFrontendMock = $this->createMock(FrontendInterface::class);
        $cacheManagerMock->method('getCache')->with('l10n')->willReturn($cacheFrontendMock);
        $cacheFrontendMock->method('get')->willReturn(false);
        $cacheFrontendMock->method('set')->willReturn(null);

        // Define languageDebug because it's expected to be set in LanguageService
        $GLOBALS['TYPO3_CONF_VARS']['BE']['languageDebug'] = false;

        $languageService = new LanguageService(
            new Locales(),
            new LocalizationFactory(
                new LanguageStore($this->createMock(PackageManager::class)),
                $cacheManagerMock
            ),
            $cacheFrontendMock
        );

        $languageServiceFactoryMock = $this->createMock(LanguageServiceFactory::class);
        $languageServiceFactoryMock->method('createFromSiteLanguage')->willReturn($languageService);
        GeneralUtility::addInstance(LanguageServiceFactory::class, $languageServiceFactoryMock);

        // This is needed for PageRenderer
        $importMapMock = $this->createMock(ImportMap::class);
        $importMapMock->method('render')->willReturn('')->withAnyParameters();

        $importMapFactoryMock = $this->createMock(ImportMapFactory::class);
        $importMapFactoryMock->method('create')->willReturn($importMapMock);
        GeneralUtility::setSingletonInstance(ImportMapFactory::class, $importMapFactoryMock);

        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );
        $frontendUserMock = $this->createMock(FrontendUserAuthentication::class);

        $GLOBALS['TSFE'] = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->setConstructorArgs(
                [
                    new Context(),
                    $site,
                    $site->getDefaultLanguage(),
                    new PageArguments(1, '1', []),
                    $frontendUserMock,
                ]
            )
            ->onlyMethods(['initCaches'])
            ->getMock();

        $GLOBALS['TSFE']->cObj = new ContentObjectRenderer();
        $GLOBALS['TSFE']->page = [];

        GeneralUtility::addInstance(MarkerBasedTemplateService::class, new MarkerBasedTemplateService(
            new NullFrontend('hash'),
            new NullFrontend('runtime'),
        ));

        $this->subject = new RssImportController();
        $this->subject->setContentObjectRenderer($GLOBALS['TSFE']->cObj);
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    public static function cropHTMLDataProvider(): \Generator
    {
        yield '10 characters' => [
            10,
            'Lorem ipsum dolor sit amet',
            'Lorem...',
        ];
        yield '20 characters' => [
            20,
            'Lorem ipsum dolor sit amet',
            'Lorem ipsum dolor...',
        ];
        yield '20 characters with HTML' => [
            20,
            'Lorem <strong>ipsum dolor</strong> sit amet',
            'Lorem <strong>ipsum dolor</strong>...',
        ];
    }

    /**
     * @test
     * @dataProvider cropHTMLDataProvider
     */
    public function cropHtml(int $length, string $input, string $expected): void
    {
        $GLOBALS['TSFE']->register['RSS_IMPORT_ITEM_LENGTH'] = $length;

        $result = $this->subject->cropHTML($input, []);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function getCachedImageLocation(): void
    {
        GeneralUtility::addInstance(MarkerBasedTemplateService::class, new MarkerBasedTemplateService(
            new NullFrontend('hash'),
            new NullFrontend('runtime'),
        ));

        $subject = new RssImportControllerFixture();
        $result = $subject->getFileExtensionFromUrl(
            'https://i1.wp.com/example.com/wp-content/uploads/2020/cropped-logo.png?fit=32%2C32&#038;ssl=1'
        );

        self::assertEquals('png', $result);
    }

    private function createSiteWithLanguage(): Site
    {
        return new Site('test', 1, [
            'identifier' => 'test',
            'rootPageId' => 1,
            'base' => '/',
            'languages' => [
                [
                    'languageId' => 1,
                    'title' => 'Default',
                    'locale' => 'en_US.UTF-8',
                    'base' => '/',
                ],
            ],
        ]);
    }
}
