<?php
declare(strict_types=1);

namespace GertKaaeHansen\GkhRssImport\Tests\Functional;

use Nimut\TestingFramework\Http\Response;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use PHPUnit\Util\PHP\AbstractPhpProcess;
use Text_Template;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class RenderingTest
 */
class RenderingTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/gkh_rss_import'];

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['fluid'];

    public function setUp()
    {
        parent::setUp();
        $this->importDataSet(__DIR__ . '/Fixtures/Database/pages.xml');
        $this->setUpFrontendRootPage(1, ['EXT:gkh_rss_import/Tests/Functional/Fixtures/Frontend/Basic.typoscript']);
    }

    /**
     * @test
     */
    public function emailViewHelperWorksWithSpamProtection(): void
    {
        $requestArguments = ['id' => '1'];
        $expectedContent = '<a href="javascript:linkTo_UnCryptMailto(\'ocknvq,kphqBjgnjwo0kq\');">info(AT)helhum(DOT)io</a>';
        $this->assertSame($expectedContent, $this->fetchFrontendResponse($requestArguments)->getContent());
    }

    /* ***************
     * Utility methods
     * ***************/

    /**
     * @param array $requestArguments
     * @param bool $failOnFailure
     * @return Response
     */
    protected function fetchFrontendResponse(array $requestArguments, $failOnFailure = true): Response
    {
        if (!empty($requestArguments['url'])) {
            $requestUrl = '/' . ltrim($requestArguments['url'], '/');
        } else {
            $requestUrl = '/?' . GeneralUtility::implodeArrayForUrl('', $requestArguments);
        }
        if (property_exists($this, 'instancePath')) {
            $instancePath = $this->instancePath;
        } else {
            $instancePath = $this->getInstancePath();
        }
        $arguments = [
            'documentRoot' => $instancePath,
            'requestUrl' => 'http://localhost' . $requestUrl,
        ];

        $template = new Text_Template('ntf://Frontend/Request.tpl');
        $template->setVar([
            'arguments' => var_export($arguments, true),
            'originalRoot' => ORIGINAL_ROOT,
            'ntfRoot' => __DIR__ . '/../../.Build/vendor/nimut/testing-framework/',
        ]);

        $php = AbstractPhpProcess::factory();
        $response = $php->runJob($template->render());
        $result = json_decode($response['stdout'], true);

        if ($result === null) {
            $this->fail('Frontend Response is empty.' . LF . 'Error: ' . LF . $response['stderr']);
        }

        if ($failOnFailure && $result['status'] === Response::STATUS_Failure) {
            $this->fail('Frontend Response has failure:' . LF . $result['error']);
        }

        return new Response($result['status'], $result['content'], $result['error']);
    }
}
