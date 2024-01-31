<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'RSS feed import',
    'description' => 'Fetch an RSS / Atom Feed and display its content on the Frontend.',
    'category' => 'plugin',
    'version' => '10.0.0',
    'state' => 'stable',
    'author' => 'Simon Schaufelberger',
    'author_email' => 'simonschaufi+gkhrssimport@gmail.com',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '13.0.0-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ]
];
