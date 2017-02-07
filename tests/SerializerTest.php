<?php

namespace Tests;

use \Onion\REST\Serializer;
use \Tests\Stubs\Serializer\A;

class SerializerTest extends \PHPUnit_Framework_TestCase
{
    private $testable;

    public function setUp()
    {
        $this->testable = new Serializer([
            A::class => [
                'links' => [
                    'self' => ['href' => '/entity/{id}']
                ],
                'fields' => ['id']
            ]
        ]);
    }

    public function testSimpleSerialization()
    {
        $this->assertSame(
            ['_links' => ['self' => ['href' => '/entity/5']], 'id' => 5],
            $this->testable->serialize(new A())
        );
    }


}
