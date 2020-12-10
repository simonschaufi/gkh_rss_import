<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    die('This script supports command line usage only. Please check your command.');
}

$copyRightHeader = <<<EOF
This file is part of the TYPO3 CMS project.

(c) Gert Kaae Hansen, Simon Schaufelberger
EOF;

$config = \TYPO3\CodingStandards\CsFixerConfig::create()
    ->setHeader($copyRightHeader);

$config
    ->getFinder()
    ->exclude(
        [
            '.Build',
            '.github',
            'Documentation',
            'Resources',
        ]
    )
    ->in(__DIR__)
    ->depth('> 1');

return $config;
