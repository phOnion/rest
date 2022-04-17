<?php

namespace Tests\Response;

use Onion\Framework\Rest\Interfaces\EntityInterface;
use Onion\Framework\Rest\Responses\Json\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class JsonPlainResponseTest extends TestCase
{
    use ProphecyTrait;
    public function testDataTransformation()
    {
        $entity = $this->prophesize(EntityInterface::class);
        $entity->getData()->willReturn(['id' => 5]);
        $entity->getEmbedded()->willReturn([]);
        $this->assertJsonStringEqualsJsonString(
            '{"id": 5}',
            (string) (new Response(200, [], $entity->reveal()->getData()))->getBody()->getContents()
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
            '{"id":5}',
            (string) (new Response(200, [], $entity->reveal()->getData()))->getBody()->getContents()
        );
    }
}
