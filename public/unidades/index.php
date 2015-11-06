
<?php

include __DIR__ . '/../../vendor/autoload.php';

use Silex\Application;
use MSIC\DAO\KrotonService;
use Symfony\Component\HttpFoundation\Request;

$app = new Application();

$kroton = new KrotonService();


$app->get('/', function(Request $request) use ($kroton) {

        $cidade = $request->get("cidade");
        $curso = $request->get('curso');
        $unidades = $kroton->getUnidade($cidade,$curso);

        return json_encode(array('retorno' => $unidades));

    });

$app->run();