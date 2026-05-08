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

namespace CustomRectorRules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Type\ObjectType;
use PHPUnit\Framework\TestCase;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class MethodCallToSelfStaticCallRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var string[]
     */
    private array $methodNames = [];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Transforms $this->methodName() to self::methodName() for configured methods',
            [
                new ConfiguredCodeSample(
                    '$this->createStub(SomeClass::class);',
                    'self::createStub(SomeClass::class);',
                    ['createStub', 'anotherMethod']
                ),
            ]
        );
    }

    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    /**
     * @param MethodCall $node
     */
    public function refactor(Node $node): ?Node
    {
        // Check if the method being called is in our configured list
        if (!$this->isNames($node->name, $this->methodNames)) {
            return null;
        }

        // Ensure we are calling this on a PHPUnit TestCase instance
        if (!$this->isObjectType($node->var, new ObjectType(TestCase::class))) {
            return null;
        }

        // Transform to a static call using the relative self keyword
        return new StaticCall(
            new Name('self'),
            $node->name,
            $node->args
        );
    }

    /**
     * @param mixed[] $configuration
     */
    public function configure(array $configuration): void
    {
        // Store the configured method names passed from rector.php
        $this->methodNames = $configuration;
    }
}
