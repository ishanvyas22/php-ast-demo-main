<?php

return [
    'default' => 'local',
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => getcwd(),
        ],
        'stubs' => [
            'driver' => 'local',
            'root' => getcwd() . DIRECTORY_SEPARATOR . 'stubs',
        ],
    ],
];
