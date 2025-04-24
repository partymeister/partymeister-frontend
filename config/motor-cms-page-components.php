<?php

return [
    'groups' => [
        'partymeister-frontend' => [
            'name' => 'Partymeister frontend',
        ],
    ],
    'components' => [
        'photowalls' => [
            'name' => 'Photowall',
            'description' => 'Show Photowall component',
            'view' => 'partymeister-frontend::frontend.components.photowalls',
            'component_class' => 'Partymeister\Frontend\Components\ComponentPhotowalls',
            'compatibility' => [

            ],
            'permissions' => [

            ],
            'group' => 'partymeister-frontend',
        ],
    ],
];
