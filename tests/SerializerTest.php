<?php

namespace Tests;

use Onion\Framework\Hydrator\Interfaces\HydratableInterface;
use Onion\Framework\Hydrator\PropertyHydrator;
use Onion\Framework\Rest\Interfaces\EntityInterface;
use \Onion\Framework\Rest\Transformer;
use \Tests\Stubs\Serializer\A;

class SerializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Transformer
     */
    private $testable;

    public function setUp()
    {
        $this->testable = new Transformer([
            A::class => [
                'rel' => 'a',
                'links' => [
                    ['rel' => 'self', 'href' => '/entity/id'],
                ],
                'fields' => ['id']
            ]
        ]);
    }

    public function testSimpleSerialization()
    {
        $this->assertInstanceOf(EntityInterface::class,$this->testable->transform(new A()));
        $entity = $this->testable->transform(new A());
    }

    public function testExceptionWHenNoMappingDefined()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->testable->transform(new class implements HydratableInterface {
            use PropertyHydrator;
        });
    }
}
