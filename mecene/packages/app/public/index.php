<?php

declare(strict_types=1);

use Mecene\Foundation\Application;
use Mecene\Http\Request;

require dirname(__DIR__) . '/vendor/autoload.php';

/** @var Application $app */
$app = require dirname(__DIR__) . '/bootstrap/app.php';

$request = Request::capture();
$response = $app->handle($request);
$response->send();
