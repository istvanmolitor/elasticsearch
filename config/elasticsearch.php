<?php

return [
    'host'     => env('ELASTICSEARCH_HOST', 'localhost'),
    'port'     => env('ELASTICSEARCH_PORT', 9200),
    'scheme'   => env('ELASTICSEARCH_SCHEME', 'http'),
    'username' => env('ELASTICSEARCH_USERNAME'),
    'password' => env('ELASTICSEARCH_PASSWORD'),
];
