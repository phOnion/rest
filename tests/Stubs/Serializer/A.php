<?php

namespace Tests\Stubs\Serializer;

use \Onion\Framework\Hydrator\Interfaces\HydratableInterface;
use \Onion\Framework\Hydrator\PropertyHydrator;

class A implements HydratableInterface
{
    use PropertyHydrator;

    public $id = 5;
    public $password = 'secret';
}
