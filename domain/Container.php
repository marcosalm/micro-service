<?php
/**
 * Created by PhpStorm.
 * User: marcos.almeida-pit
 * Date: 02/10/2015
 * Time: 09:46
 */

namespace MSIC;

use GuzzleHttp\Client;

class Container
{
    private $container;

    public function addService($serviceName, array $serviceConfig=[])
    {
        $this->container[$serviceName] = new Client($serviceConfig);
    }

    /**
     * @param string $serviceName
     * @return \GuzzleHttp\Client
     */
    public function getService($serviceName)
    {
        return $this->container[$serviceName];
    }
}