<?php
declare(strict_types=1);

require __DIR__ . '/../src/Config.php';
require __DIR__ . '/../src/HttpClient.php';
require __DIR__ . '/../src/Cache/FileCache.php';
require __DIR__ . '/../src/Ogame/Endpoints.php';
require __DIR__ . '/../src/Ogame/OgameApi.php';
require __DIR__ . '/../src/Util/Coords.php';
require __DIR__ . '/../src/Util/Html.php';

use App\Config;
use App\HttpClient;
use App\Cache\FileCache;
use App\Ogame\OgameApi;
use App\Util\Html;

$api = new OgameApi(
    new HttpClient(),
    new FileCache(Config::CACHE_DIR),
    Config::OGAME_BASE_URL
);

$page = (string)($_GET['page'] ?? 'home');
$allowed = ['home','player','planets','inactives','distance','api'];
if (!in_array($page, $allowed, true)) {
    $page = 'home';
}

$viewFile = __DIR__ . '/../views/pages/' . $page . '.php';

$ctx = [
    'page' => $page,
    'api'  => $api,
    'get'  => $_GET,
];

ob_start();
require $viewFile;
$content = (string)ob_get_clean();

require __DIR__ . '/../views/layout.php';
