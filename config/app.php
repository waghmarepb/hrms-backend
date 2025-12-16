<?php

return [
    'name' => 'HRMS API',
    'env' => getenv('APP_ENV') ?: 'production',
    'debug' => getenv('APP_DEBUG') === 'true',
    'timezone' => 'UTC',
    'locale' => 'en',
];

