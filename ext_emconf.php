<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'RSS feed import',
    'description' => 'Fetch an RSS / Atom Feed and display its content on the Frontend.',
    'category' => 'plugin',
    'version' => '10.0.1',
    'state' => 'stable',
    'author' => 'Simon Schaufelberger',
    'author_email' => 'simonschaufi+gkhrssimport@gmail.com',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'php' => '8.2.0-8.4.99',
            'typo3' => '13.4.10-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
