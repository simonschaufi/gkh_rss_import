<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Basic\SingleLineEmptyBodyFixer;
use PhpCsFixer\Fixer\CastNotation\CastSpacesFixer;
use PhpCsFixer\Fixer\ClassNotation\ClassAttributesSeparationFixer;
use PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use PhpCsFixer\Fixer\Operator\OperatorLinebreakFixer;
use PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocAnnotationRemoveFixer;
use PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocIndentFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use PhpCsFixer\Fixer\Strict\StrictComparisonFixer;
use PhpCsFixer\Fixer\StringNotation\ExplicitStringVariableFixer;
use PhpCsFixer\Fixer\Whitespace\IndentationTypeFixer;
use Symplify\CodingStandard\Fixer\ArrayNotation\ArrayOpenerAndCloserNewlineFixer;
use Symplify\CodingStandard\Fixer\Commenting\RemoveUselessDefaultCommentFixer;
use Symplify\CodingStandard\Fixer\LineLength\LineLengthFixer;
use Symplify\CodingStandard\Fixer\Spacing\MethodChainingNewlineFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->paths([
        __DIR__ . '/../../Build',
        __DIR__ . '/../../Classes',
        __DIR__ . '/../../Configuration',
        __DIR__ . '/../../Resources/PHP',
        __DIR__ . '/../../Tests',
        __DIR__ . '/../../ext_emconf.php',
        __DIR__ . '/../../ext_localconf.php',
    ]);

    $ecsConfig->sets([
        SetList::PSR_12,
        SetList::CLEAN_CODE,
        SetList::SYMPLIFY,
        SetList::ARRAY,
        SetList::COMMON,
        SetList::COMMENTS,
        SetList::CONTROL_STRUCTURES,
        SetList::DOCBLOCK,
        SetList::NAMESPACES,
        SetList::PHPUNIT,
        SetList::SPACES,
        SetList::STRICT,
    ]);

    $ecsConfig->ruleWithConfiguration(GeneralPhpdocAnnotationRemoveFixer::class, [
        'annotations' => ['author', 'package', 'group'],
    ]);

    $ecsConfig->ruleWithConfiguration(NoSuperfluousPhpdocTagsFixer::class, [
        'allow_mixed' => true,
    ]);

    $ecsConfig->ruleWithConfiguration(CastSpacesFixer::class, [
        'space' => 'none',
    ]);

    // Rules that are not in a set
    $ecsConfig->rule(OperatorLinebreakFixer::class);
    $ecsConfig->rule(SingleLineEmptyBodyFixer::class);

    $ecsConfig->skip([
        LineLengthFixer::class,
        DeclareStrictTypesFixer::class => [
            __DIR__ . '/../../ext_emconf.php',
            __DIR__ . '/../../ext_localconf.php',
        ],
        NotOperatorWithSuccessorSpaceFixer::class,
        OrderedClassElementsFixer::class => [
            __DIR__ . '/../../Classes/Controller/RssImportController.php',
        ],

        MethodChainingNewlineFixer::class => [
            __DIR__ . '/../../Classes/Controller/AbstractPlugin.php',
            __DIR__ . '/../../Classes/Controller/RssImportController.php',
        ],

        ExplicitStringVariableFixer::class => [
            __DIR__ . '/../../Resources/PHP/lastRSS.php',
        ],
        ClassAttributesSeparationFixer::class => [
            __DIR__ . '/../../Resources/PHP/lastRSS.php',
        ],
        PhpdocIndentFixer::class => [
            __DIR__ . '/../../Resources/PHP/lastRSS.php',
        ],
        RemoveUselessDefaultCommentFixer::class => [
            __DIR__ . '/../../Resources/PHP/lastRSS.php',
        ],
        ArrayOpenerAndCloserNewlineFixer::class => [
            __DIR__ . '/../../Resources/PHP/lastRSS.php',
        ],
        IndentationTypeFixer::class => [
            __DIR__ . '/../../Resources/PHP/lastRSS.php',
        ],

        StrictComparisonFixer::class,
    ]);
};
