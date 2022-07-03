<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'RSS feed import',
    'description' => 'Fetch an RSS / Atom Feed and display its content on the Frontend.',
    'category' => 'plugin',
    'version' => '9.0.2',
    'state' => 'stable',
    'uploadfolder' => false,
    'clearCacheOnLoad' => true,
    'author' => 'Simon Schaufelberger',
    'author_email' => 'simonschaufi+gkhrssimport@gmail.com',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '11.4.0-11.5.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ]
];
