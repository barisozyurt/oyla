<?php
return [
    'provider' => $_ENV['SMS_PROVIDER'] ?? 'netgsm',
    'mock' => ($_ENV['SMS_MOCK'] ?? 'true') === 'true',
    'netgsm' => [
        'username' => $_ENV['NETGSM_USERNAME'] ?? '',
        'password' => $_ENV['NETGSM_PASSWORD'] ?? '',
        'from' => $_ENV['NETGSM_FROM'] ?? 'OYLA',
        'api_url' => 'https://api.netgsm.com.tr/sms/send/get',
    ],
];
