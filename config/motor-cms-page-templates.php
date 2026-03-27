<?php

$templates = [
    'partymeister-livevoting' => [
        'meta' => [
            'namespace' => 'partymeister-frontend',
            'name' => 'Partymeister livevoting template',
        ],
        'items' => [
            [
                [
                    'alias' => 'content-full',
                    'class' => 'full-content',
                    'container' => 'first-row-content',
                    'width' => 12,
                ],
            ],
        ],
    ],
    'partymeister' => [
        'meta' => [
            'namespace' => 'partymeister-frontend',
            'name' => 'Partymeister default template',
        ],
        'items' => [
            [
                [
                    'alias' => 'content-two-thirds',
                    'class' => 'main-content',
                    'container' => 'first-row-content',
                    'width' => 8,
                ],
                [
                    'alias' => 'content-third',
                    'class' => 'sidebar-content',
                    'container' => 'second-row-sidebar',
                    'width' => 4,
                ],
            ],
        ],
    ],
];

return $templates;
