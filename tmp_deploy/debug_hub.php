<?php
use Symfony\Component\HttpFoundation\Request;

require '/srv/web_synergygig/vendor/autoload.php';
$kernel = new App\Kernel('prod', false);
$request = Request::create('/offers/');
// simulate a logged-in session via cookie
try {
    $response = $kernel->handle($request);
    echo 'STATUS: '.$response->getStatusCode().PHP_EOL;
    if ($response->getStatusCode() >= 400) {
        echo substr($response->getContent(), 0, 2000).PHP_EOL;
    } else {
        echo 'OK ('.strlen($response->getContent()).' bytes)'.PHP_EOL;
    }
} catch (\Throwable $e) {
    echo 'ERROR: '.$e->getMessage().PHP_EOL.$e->getFile().':'.$e->getLine().PHP_EOL.$e->getTraceAsString().PHP_EOL;
}
