<?php

declare(strict_types=1);

namespace GertKaaeHansen\GkhRssImport\Tests\Unit\Controller;

use GertKaaeHansen\GkhRssImport\Controller\RssImportController;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Prophecy\Argument;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\LanguageStore;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class RssImportControllerTest extends UnitTestCase
{
    /**
     * @var RssImportController
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class)->getVersion();
        if (VersionNumberUtility::convertVersionNumberToInteger($typo3Version) >= 10000000) {
            /** @see https://github.com/TYPO3/TYPO3.CMS/blob/67dde9c018909997833f50d1b4deeb48dede1770/typo3/sysext/frontend/Tests/Unit/ContentObject/Menu/AbstractMenuContentObjectTest.php#L59-L92 */
            $GLOBALS['TYPO3_REQUEST'] = new ServerRequest('https://www.example.com', 'GET');

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
                            'base' => '/'
                        ]
                    ]
                ]
            );

            $cacheManagerProphecy = $this->prophesize(CacheManager::class);
            GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());

            $cacheFrontendProphecy = $this->prophesize(FrontendInterface::class);
            $cacheManagerProphecy->getCache('l10n')->willReturn($cacheFrontendProphecy->reveal());
            $cacheFrontendProphecy->get(Argument::cetera())->willReturn(false);
            $cacheFrontendProphecy->set(Argument::cetera())->willReturn(null);

            $languageService = new LanguageService(
                new Locales(),
                new LocalizationFactory(new LanguageStore(), $cacheManagerProphecy->reveal())
            );

            $languageServiceFactoryProphecy = $this->prophesize(LanguageServiceFactory::class);
            $languageServiceFactoryProphecy->create(Argument::any())->willReturn($languageService);
            GeneralUtility::addInstance(LanguageServiceFactory::class, $languageServiceFactoryProphecy->reveal());

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
                ->setMethods(['initCaches'])
                ->getMock();
        } else {
            // Can be removed when TYPO3 9 support is dropped
            $GLOBALS['TSFE'] = $this->getMockBuilder(TypoScriptFrontendController::class)
                ->setConstructorArgs([
                    $GLOBALS['TYPO3_CONF_VARS'],
                    1,
                    1
                ])
                ->setMethods(['initCaches'])
                ->getMock();
        }

        $GLOBALS['TSFE']->cObj = new ContentObjectRenderer();
        $GLOBALS['TSFE']->page = [];

        $this->subject = new RssImportController();
        $this->subject->cObj = $GLOBALS['TSFE']->cObj;
    }

    public function cropHTMLDataProvider(): array
    {
        return [
            '10 characters' => [
                10,
                'Lorem ipsum dolor sit amet',
                'Lorem...',
            ],
            '20 characters' => [
                20,
                'Lorem ipsum dolor sit amet',
                'Lorem ipsum dolor...',
            ],
            '20 characters with HTML' => [
                20,
                'Lorem <strong>ipsum dolor</strong> sit amet',
                'Lorem <strong>ipsum dolor</strong>...',
            ],
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
}
