<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', __DIR__);

require BASE_PATH . '/vendor/autoload.php';

use App\Core\App;

App::boot();
