<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use OpenApi\Generator;

$openapi = Generator::scan([
    'src/Controllers',
    'src/OpenAPI',
])->toJson();

echo $openapi;
