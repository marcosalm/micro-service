<?php

include __DIR__ . '/../../vendor/autoload.php';

use Silex\Application;
use MSIC\DAO\KrotonService;
use Symfony\Component\HttpFoundation\Request;

$app = new Application();

$kroton = new KrotonService();

$app->get('/', function(Request $request) use ($kroton) {

    $curso = $request->get('curso');
    $uf = $kroton->getUF($curso);

    return json_encode(array('retorno' => $uf));

});

$app->run();