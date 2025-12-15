<?php

define('MINI_ENGINE_LIBRARY', true);
define('MINI_ENGINE_ROOT', __DIR__);
require_once(__DIR__ . '/mini-engine.php');
if (file_exists(__DIR__ . '/config.inc.php')) {
    include(__DIR__ . '/config.inc.php');
} elseif (file_exists("/srv/config/lawtrace.inc.php")) {
    include("/srv/config/lawtrace.inc.php");
}
set_include_path(
    __DIR__ . '/libraries'
    . PATH_SEPARATOR . __DIR__ . '/models'
);
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once(__DIR__ . '/vendor/autoload.php');
}
if (!getenv('LYAPI_HOST')) {
    putenv('LYAPI_HOST=ly.govapi.tw/v2');
}
if (!getenv('POLICYAPI_HOST')) {
    putenv('POLICYAPI_HOST=policy.join.govapi.tw');
}
MiniEngine::initEnv();
