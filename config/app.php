<?php

return [
    'name' => getenv('APP_NAME') ?: 'TinCup API',
    'env' => getenv('APP_ENV') ?: 'production',
    'debug' => getenv('APP_DEBUG') === 'true',
    'url' => getenv('APP_URL') ?: 'http://localhost',
    'jwt_secret' => getenv('JWT_SECRET'),
    'cors_origins' => getenv('CORS_ALLOWED_ORIGINS') ?: '*',
];
