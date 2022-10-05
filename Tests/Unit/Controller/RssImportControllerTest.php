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
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
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

class RssImportControllerTest extends UnitTestCase
{
    use ProphecyTrait;
    use PageRendererFactoryTrait;

    protected RssImportController $subject;

    /**
     * @throws NoSuchCacheException
     * @throws \JsonException
     */
    protected function setUp(): void
    {
        parent::setUp();

        /** @see https://github.com/TYPO3/typo3/blob/36096733dea4bd6f6168209609fa879dc25c0138/typo3/sysext/frontend/Tests/Unit/ContentObject/Menu/AbstractMenuContentObjectTest.php#L68-L112 */
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://www.example.com', 'GET'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);

        $site = new Site(
            'test',
            1,
            [
                'base' => 'https://www.example.com',
                'languages' => [
                    [
                        'languageId' => 0,
                        'title' => 'English',
                        'locale' => 'en_UK',
                        'base' => '/',
                    ],
                ],
            ]
        );

        $packageManagerProphecy = $this->prophesize(PackageManager::class);

        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());

        $cacheFrontendProphecy = $this->prophesize(FrontendInterface::class);
        $cacheManagerProphecy->getCache('l10n')->willReturn($cacheFrontendProphecy->reveal());
        $cacheFrontendProphecy->get(Argument::cetera())->willReturn(false);
        $cacheFrontendProphecy->set(Argument::cetera())->willReturn(null);

        $languageService = new LanguageService(
            new Locales(),
            new LocalizationFactory(
                new LanguageStore($packageManagerProphecy->reveal()),
                $cacheManagerProphecy->reveal()
            ),
            $cacheFrontendProphecy->reveal()
        );

        $languageServiceFactoryProphecy = $this->prophesize(LanguageServiceFactory::class);
        $languageServiceFactoryProphecy->createFromSiteLanguage(Argument::any())->willReturn($languageService);
        GeneralUtility::addInstance(LanguageServiceFactory::class, $languageServiceFactoryProphecy->reveal());

        $importMapProphecy = $this->prophesize(ImportMap::class);
        $importMapProphecy->render(Argument::type('string'), Argument::type('string'))->willReturn('');

        $importMapFactoryProphecy = $this->prophesize(ImportMapFactory::class);
        $importMapFactoryProphecy->create()->willReturn($importMapProphecy->reveal());

        GeneralUtility::setSingletonInstance(ImportMapFactory::class, $importMapFactoryProphecy->reveal());
        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );
        $frontendUserProphecy = $this->prophesize(FrontendUserAuthentication::class);

        $GLOBALS['TSFE'] = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->setConstructorArgs(
                [
                    new Context(),
                    $site,
                    $site->getDefaultLanguage(),
                    new PageArguments(1, '1', []),
                    $frontendUserProphecy->reveal()
                ]
            )
            ->onlyMethods(['initCaches'])
            ->getMock();

        $GLOBALS['TSFE']->cObj = new ContentObjectRenderer();

        // Can be removed as soon as TYPO3 12.1.0 gets released
        $GLOBALS['TSFE']->cObj->data = [
            'pi_flexform' => null,
        ];
        // END

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

    public function cropHTMLDataProvider(): \Generator
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
     * @param int $length
     * @param string $input
     * @param string $expected
     */
    public function cropHTML(int $length, string $input, string $expected): void
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
}
