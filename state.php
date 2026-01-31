<?php

return [
    // Pre-defined page data
    'data' => [
        'chunk' => 5,
        'deep' => 0,
        'sort' => [1, 'path']
    ],
    'x' => [
        'json' => [['From::JSON', [true]], ['To::JSON', [2]]],
        'txt' => [['From::page', [true]], ['To::page', [2, '---', '...']]]
    ]
];