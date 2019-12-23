<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'RSS feed import',
    'description' => 'Imports a RSS feed and show the content on a page.',
    'category' => 'plugin',
    'version' => '6.0.5',
    'state' => 'stable',
    'uploadfolder' => false,
    'createDirs' => 'uploads/tx_gkhrssimport/',
    'clearCacheOnLoad' => true,
    'author' => 'Simon Schaufelberger',
    'author_email' => 'simonschaufi+gkhrssimport@gmail.com',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '7.6.0-8.7.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ]
];
