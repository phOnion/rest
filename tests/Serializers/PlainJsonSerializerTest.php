<?php

namespace Serializers;

use Onion\Framework\Http\Header\Interfaces\AcceptInterface;
use Onion\Framework\Rest\Interfaces\EntityInterface;
use Onion\Framework\Rest\Serializers\PlainJsonSerializer;

class PlainJsonSerializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PlainJsonSerializer
     */
    private $testable;
    public function setUp()
    {
        $this->testable = new PlainJsonSerializer();
    }

    public function testContentType()
    {
        $this->assertSame('application/json', $this->testable->getContentType());
    }

    public function testSupport()
    {
        $accept = $this->prophesize(AcceptInterface::class);
        $accept->supports('application/json')->willReturn(true);

        $this->assertTrue($this->testable->supports($accept->reveal()));
    }

    public function testDataTransformation()
    {
        $entity = $this->prophesize(EntityInterface::class);
        $entity->getData()->willReturn(['id' => 5]);
        $entity->getEmbedded()->willReturn([]);
        $this->assertJsonStringEqualsJsonString('{"id": 5}', $this->testable->serialize($entity->reveal()));
    }

    public function testTransformationWithEmbedded()
    {
        $embeddedEntity = $this->prophesize(EntityInterface::class);
        $embeddedEntity->getData()->willReturn(['name' => 'John']);
        $embeddedEntity->getEmbedded()->willReturn([]);
        $entity = $this->prophesize(EntityInterface::class);
        $entity->getData()->willReturn(['id' => 5]);
        $entity->getEmbedded()->willReturn(['mock' => $embeddedEntity->reveal()]);

        $this->assertJsonStringEqualsJsonString(
            '{"id":5, "mock": {"name": "John"}}',
            $this->testable->serialize($entity->reveal())
        );
    }

    public function testTransformationWithEmbeddedInData()
    {
        $embeddedEntity = $this->prophesize(EntityInterface::class);
        $embeddedEntity->getData()->willReturn(['name' => 'John']);
        $embeddedEntity->getEmbedded()->willReturn([]);
        $entity = $this->prophesize(EntityInterface::class);
        $entity->getData()->willReturn(['id' => 5, 'mock' => $embeddedEntity->reveal()]);
        $entity->getEmbedded()->willReturn([]);

        $this->assertJsonStringEqualsJsonString(
            '{"id":5, "mock": {"name": "John"}}',
            $this->testable->serialize($entity->reveal())
        );
    }

    public function testTransformationWithEmbeddedArray()
    {
        $embeddedEntity = $this->prophesize(EntityInterface::class);
        $embeddedEntity->getData()->willReturn(['name' => 'John']);
        $embeddedEntity->getEmbedded()->willReturn([]);
        $entity = $this->prophesize(EntityInterface::class);
        $entity->getData()->willReturn(['id' => 5]);
        $entity->getEmbedded()->willReturn(['mock' => [$embeddedEntity->reveal()]]);

        $this->assertJsonStringEqualsJsonString(
            '{"id":5, "mock": [{"name": "John"}]}',
            $this->testable->serialize($entity->reveal())
        );
    }

    public function testTransformationWithEmbeddedArrayInData()
    {
        $embeddedEntity = $this->prophesize(EntityInterface::class);
        $embeddedEntity->getData()->willReturn(['name' => 'John']);
        $embeddedEntity->getEmbedded()->willReturn([]);
        $entity = $this->prophesize(EntityInterface::class);
        $entity->getData()->willReturn(['id' => 5, 'mock' => [$embeddedEntity->reveal()]]);
        $entity->getEmbedded()->willReturn([]);

        $this->assertJsonStringEqualsJsonString(
            '{"id":5, "mock": [{"name": "John"}]}',
            $this->testable->serialize($entity->reveal())
        );
    }
}
