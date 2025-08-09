<?php

return [
    'name' => env('PANEL_NAME', 'AlrelShop'),
    'url' => env('PANEL_URL', 'https://panel.alrelshop.my.id'),
    'admin_email' => env('ADMIN_EMAIL', 'admin@alrelshop.com'),
    'price_per_account' => env('PRICE_PER_ACCOUNT', 18000),
    
    'vps_targets' => [
        'vps1' => [
            'name' => 'ANYM NETWORK',
            'host' => 'servervip5.alrelshop.my.id',
            'ip' => '140.213.202.13',
            'ssh_port' => 22,
            'ssh_user' => 'root',
            'ssh_key' => '/root/.ssh/alrelshop_panel',
            'location' => 'Indonesia',
            'provider' => 'DigitalOcean',
            'status' => 'online'
        ],
        'vps2' => [
            'name' => 'ARGON DATA 1',
            'host' => 'servervip10.alrelshop.my.id',
            'ip' => '103.150.197.96',
            'ssh_port' => 22,
            'ssh_user' => 'root',
            'ssh_key' => '/root/.ssh/alrelshop_panel',
            'location' => 'Indonesia',
            'provider' => 'Vultr',
            'status' => 'online'
        ],
        'vps3' => [
            'name' => 'DIGITAL OCEAN 2',
            'host' => 'sg-vip-9.alrelshop.my.id',
            'ip' => '128.199.104.75',
            'ssh_port' => 22,
            'ssh_user' => 'root',
            'ssh_key' => '/root/.ssh/alrelshop_panel',
            'location' => 'Singapura',
            'provider' => 'DigitalOcean',
            'status' => 'online'
        ]
    ]
];
