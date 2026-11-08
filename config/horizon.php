'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['high', 'default', 'low', 'bulk'],
            'balance' => 'auto',
            'processes' => 10,
            'tries' => 3,
        ],
    ],

    'local' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['high', 'default', 'low', 'bulk'],
            'balance' => 'simple',
            'processes' => 3,
            'tries' => 3,
        ],
    ],
],