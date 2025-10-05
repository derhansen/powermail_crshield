<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Powermail challenge/response spambot shield',
    'description' => 'Adds a hidden input field containing a challenge string to powermail forms. Client must execute included JavaScript to calculate the expected response.',
    'category' => 'fe',
    'author' => 'Torben Hansen',
    'author_email' => 'derhansen@gmail.com',
    'state' => 'stable',
    'uploadfolder' => '0',
    'createDirs' => '',
    'clearCacheOnLoad' => 1,
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-12.4.99',
            'powermail' => '12.0.0-12.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
