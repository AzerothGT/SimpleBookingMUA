<?php

use App\Actions\Services\SaveServiceImage;
use App\Models\Service;
use App\Models\ServiceImage;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

app(SaveServiceImage::class)->update(
    Service::findOrFail($argv[1]),
    ServiceImage::findOrFail($argv[2]),
    ['is_cover' => true],
);

echo 'accepted';
