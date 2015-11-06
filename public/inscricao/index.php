<?php

include __DIR__ . '/../../vendor/autoload.php';

use Silex\Application;
use MSIC\DAO\KrotonService;
use Symfony\Component\HttpFoundation\Request;

$app = new Application();

$kroton = new KrotonService();

$app->post('/', function(Request $request) use ($kroton) {
    $user["Nome"] = $request->get("Nome");
    $user["CPF"] = $request->get("CPF");
    $user["RG"] = $request->get("RG");

    /*********************************/

   // $result = $kroton->InsertOrUpdate($user);

});

$app->run();