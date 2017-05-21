<?php

namespace Tests\Response;

use Onion\Framework\Rest\Interfaces\EntityInterface;
use Onion\Framework\Rest\Response\JsonPlainResponse;

class JsonPlainResponseTest extends \PHPUnit_Framework_TestCase
{
    public function testDataTransformation()
    {
        $entity = $this->prophesize(EntityInterface::class);
        $entity->getData()->willReturn(['id' => 5]);
        $entity->getEmbedded()->willReturn([]);
        $this->assertJsonStringEqualsJsonString(
            '{"id": 5}',
            (string) (new JsonPlainResponse($entity->reveal()))->getBody()->getContents()
        );
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
            (string) (new JsonPlainResponse($entity->reveal()))->getBody()->getContents()
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
            (string) (new JsonPlainResponse($entity->reveal()))->getBody()->getContents()
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
            (string) (new JsonPlainResponse($entity->reveal()))->getBody()->getContents()
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
            (string) (new JsonPlainResponse($entity->reveal()))->getBody()->getContents()
        );
    }
}
