<?php
return [
    'env' => getenv('APP_ENV') ?: 'local',
    'debug' => getenv('APP_DEBUG') === 'true',
    'app_url' => getenv('APP_URL') ?: 'http://localhost',
];
