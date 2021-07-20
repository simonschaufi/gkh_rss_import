<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'RSS feed import',
    'description' => 'Fetch an RSS / Atom Feed and display its content on the Frontend.',
    'category' => 'plugin',
    'version' => '8.0.0',
    'state' => 'stable',
    'uploadfolder' => false,
    'clearCacheOnLoad' => true,
    'author' => 'Simon Schaufelberger',
    'author_email' => 'simonschaufi+gkhrssimport@gmail.com',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-11.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ]
];
