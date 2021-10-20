<?php

/**
 * Extend PhpStorms code completion capabilities by providing a meta file
 *
 * Kudos to Alexander Schnitzler's work, see https://github.com/alexanderschnitzler/phpstorm.meta.php-typo3
 * @link https://www.jetbrains.com/help/phpstorm/ide-advanced-metadata.html
 */

namespace PHPSTORM_META {
    override(\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(0), type(0));

    // Contexts
    // @see https://docs.typo3.org/c/typo3/cms-core/master/en-us/Changelog/9.4/Feature-85389-ContextAPIForConsistentDataHandling.html
    expectedArguments(
        \TYPO3\CMS\Core\Context\Context::getAspect(),
        0,
        'date',
        'visibility',
        'backend.user',
        'frontend.user',
        'workspace',
        'language',
        'typoscript'
    );

    override(\TYPO3\CMS\Core\Context\Context::getAspect(), map([
        'date' => \TYPO3\CMS\Core\Context\DateTimeAspect::class,
        'visibility' => \TYPO3\CMS\Core\Context\VisibilityAspect::class,
        'backend.user' => \TYPO3\CMS\Core\Context\UserAspect::class,
        'frontend.user' => \TYPO3\CMS\Core\Context\UserAspect::class,
        'workspace' => \TYPO3\CMS\Core\Context\WorkspaceAspect::class,
        'language' => \TYPO3\CMS\Core\Context\LanguageAspect::class,
        'typoscript' => \TYPO3\CMS\Core\Context\TypoScriptAspect::class,
    ]));

    expectedArguments(
        \TYPO3\CMS\Core\Context\DateTimeAspect::get(),
        0,
        'timestamp',
        'iso',
        'timezone',
        'full',
        'accessTime'
    );

    expectedArguments(
        \TYPO3\CMS\Core\Context\VisibilityAspect::get(),
        0,
        'includeHiddenPages',
        'includeHiddenContent',
        'includeDeletedRecords'
    );

    expectedArguments(
        \TYPO3\CMS\Core\Context\UserAspect::get(),
        0,
        'id',
        'username',
        'isLoggedIn',
        'isAdmin',
        'groupIds',
        'groupNames'
    );

    expectedArguments(
        \TYPO3\CMS\Core\Context\WorkspaceAspect::get(),
        0,
        'id',
        'isLive',
        'isOffline'
    );

    expectedArguments(
        \TYPO3\CMS\Core\Context\LanguageAspect::get(),
        0,
        'id',
        'contentId',
        'fallbackChain',
        'overlayType',
        'legacyLanguageMode',
        'legacyOverlayType'
    );

    expectedArguments(
        \TYPO3\CMS\Core\Context\TypoScriptAspect::get(),
        0,
        'forcedTemplateParsing'
    );

    // TYPO3 testing framework
    // The accesible mock will be of type "self" as well as "MockObject" and "AccessibleObjectInterface"
    override(
        \TYPO3\TestingFramework\Core\BaseTestCase::getAccessibleMock(0),
        map([
            '' => '@|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface',
        ])
    );
    override(
        \TYPO3\TestingFramework\Core\BaseTestCase::getAccessibleMockForAbstractClass(0),
        map([
            '' => '@|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface',
        ])
    );
    override(
        \Prophecy\PhpUnit\ProphecyTrait::prophesize(0),
        map([
            '' => '@|\Prophecy\Prophecy\ObjectProphecy',
        ])
    );

    // Nimut testing framework
    // The accesible mock will be of type "self" as well as "MockObject" and "AccessibleMockObjectInterface"
    override(
        \Nimut\TestingFramework\TestCase\AbstractTestCase::getAccessibleMock(0),
        map([
            '' => '@|\PHPUnit\Framework\MockObject\MockObject|\Nimut\TestingFramework\MockObject\AccessibleMockObjectInterface',
        ])
    );
    override(
        \Nimut\TestingFramework\TestCase\AbstractTestCase::getAccessibleMockForAbstractClass(0),
        map([
            '' => '@|\PHPUnit\Framework\MockObject\MockObject|\Nimut\TestingFramework\MockObject\AccessibleMockObjectInterface',
        ])
    );
}
