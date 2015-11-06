<?php

include __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Config\FileLocator;
use MSIC\Loader\YamlFileLoader;
use MSIC\Container;

$container = new Container();

$ymlLoader = new YamlFileLoader($container, new FileLocator(__DIR__));
$ymlLoader->load('container.yml');

echo $container->getService('bck1Server')->get('/uf')->getBody() . "\n";

echo $container->getService('bck2Server')->get('/cidade')->getBody() . "\n";

echo $container->getService('bck3Server')->get('/unidade')->getBody() . "\n";
