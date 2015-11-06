<?php

include __DIR__ . '/../../vendor/autoload.php';

use Silex\Application;
use MSIC\DAO\KrotonService;
use Symfony\Component\HttpFoundation\Request;

$app = new Application();

$kroton = new KrotonService();

$app->get('/', function(Request $request) use ($kroton) {

return;

});

$app->run();