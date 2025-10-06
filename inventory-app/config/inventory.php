<?php

return [
    'settings_defaults' => [
        'alert_expiring_days' => '30,60,90',
        'notify_low_stock' => '1',
        'notify_channels' => 'inapp,email,line',
        'notify_emails' => 'manager@example.com,owner@example.com',
        'daily_scan_time' => '08:00',
    ],
    'notify_channel_options' => [
        'inapp' => 'แจ้งเตือนในระบบ',
        'email' => 'อีเมล',
        'line' => 'LINE Notify',
    ],
    'enable_price' => (bool) env('INVENTORY_ENABLE_PRICE', false),
];
