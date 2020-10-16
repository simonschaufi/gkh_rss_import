<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'RSS feed import',
    'description' => 'Import an RSS feed and show the content on a page.',
    'category' => 'plugin',
    'version' => '7.0.0',
    'state' => 'stable',
    'uploadfolder' => false,
    'createDirs' => 'uploads/tx_gkhrssimport/',
    'clearCacheOnLoad' => true,
    'author' => 'Simon Schaufelberger',
    'author_email' => 'simonschaufi+gkhrssimport@gmail.com',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-10.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ]
];
