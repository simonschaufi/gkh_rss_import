<?php
declare(strict_types=1);

namespace GertKaaeHansen\GkhRssImport\Tests\Unit;

use GertKaaeHansen\GkhRssImport\Tests\Unit\Fixtures\LoadableClass;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Nimut\TestingFramework\TestCase\ViewHelperBaseTestcase;

class FirstClassTest extends UnitTestCase
{
    /**
     * @test
     */
    public function methodReturnsTrue(): void
    {
        $firstClassObject = new LoadableClass();
        $this->assertTrue($firstClassObject->returnsTrue());
    }

    /**
     * @test
     */
    public function viewHelperBaseClassIsLoadable(): void
    {
        $this->assertTrue(class_exists(ViewHelperBaseTestcase::class));
    }
}
