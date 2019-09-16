<?php

namespace Tests\Response;

use Onion\Framework\Rest\Interfaces\EntityInterface;
use Onion\Framework\Rest\Response\JsonPlainResponse;
use Onion\Framework\Rest\Responses\Json\PlainResponse;

class JsonPlainResponseTest extends \PHPUnit_Framework_TestCase
{
    public function testDataTransformation()
    {
        $entity = $this->prophesize(EntityInterface::class);
        $entity->getData()->willReturn(['id' => 5]);
        $entity->getEmbedded()->willReturn([]);
        $this->assertJsonStringEqualsJsonString(
            '{"id": 5}',
            (string) (new PlainResponse(200, [], new class($entity) implements TransformableInterface {
                private $entity;
                public function __construct($entity)
                {
                    $this->entity = $entity;
                }
                public function transform(iterable $includes = [], iterable $fields = []): \Onion\Framework\Rest\Interfaces\EntityInterface
                {
                    return $this->entity->reveal();
                }
            }))->getBody()->getContents()
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
            (string) (new PlainResponse(200, [], new class($entity) implements TransformableInterface {
                private $entity;
                public function __construct($entity)
                {
                    $this->entity = $entity;
                }
                public function transform(iterable $includes = [], iterable $fields = []): \Onion\Framework\Rest\Interfaces\EntityInterface
                {
                    return $this->entity->reveal();
                }
            }))->getBody()->getContents()
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
            (string) (new PlainResponse(200, [], new class($entity) implements TransformableInterface {
                private $entity;
                public function __construct($entity)
                {
                    $this->entity = $entity;
                }
                public function transform(iterable $includes = [], iterable $fields = []): \Onion\Framework\Rest\Interfaces\EntityInterface
                {
                    return $this->entity->reveal();
                }
            }))->getBody()->getContents()
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
            (string) (new PlainResponse(200, [], new class($entity) implements TransformableInterface {
                private $entity;
                public function __construct($entity)
                {
                    $this->entity = $entity;
                }
                public function transform(iterable $includes = [], iterable $fields = []): \Onion\Framework\Rest\Interfaces\EntityInterface
                {
                    return $this->entity->reveal();
                }
            }))->getBody()->getContents()
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
            (string) (new PlainResponse(200, [], new class($entity) implements TransformableInterface {
                private $entity;
                public function __construct($entity)
                {
                    $this->entity = $entity;
                }
                public function transform(iterable $includes = [], iterable $fields = []): \Onion\Framework\Rest\Interfaces\EntityInterface
                {
                    return $this->entity->reveal();
                }
            }))->getBody()->getContents()
        );
    }
}
