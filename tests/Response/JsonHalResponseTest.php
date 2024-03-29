<?php

namespace Tests\Response;

use Onion\Framework\Rest\Interfaces\EntityInterface;
use Psr\Link\EvolvableLinkInterface;
use JsonSchema\Validator;
use Onion\Framework\Rest\Responses\Json\HalResponse;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class JsonHalResponseTest extends TestCase
{
    use ProphecyTrait;
    // https://raw.githubusercontent.com/scottsmith130/hal-json-schema/master/hal.json

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    public function testExceptionOnMissingLinks()
    {
        $this->expectException(\LogicException::class);
        $entity = $this->prophesize(EntityInterface::class);
        $entity->getData()->willReturn(['id' => 5]);
        $entity->getLinksByRel('self')->willReturn([]);

        new HalResponse(200, [], $entity->reveal());
    }

    public function testBasicSerialization()
    {
        $self = $this->prophesize(EvolvableLinkInterface::class);
        $self->getHref()->willReturn('/');
        $self->getAttributes()->willReturn([]);
        $self->isTemplated()->willReturn(false);
        $self->getRels()->willReturn(['self']);
        $self->withHref('/')->willReturn($self->reveal());

        $entity = $this->prophesize(EntityInterface::class);
        $entity->getData()->willReturn(['id' => 5]);
        $entity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $entity->getLinks()->willReturn([$self->reveal()]);
        $entity->hasEmbedded()->willReturn(false);


        $this->validator->check(
            (new HalResponse(200, [], $entity->reveal()))->getBody()->getContents(),
            (object) ['$ref' => 'https://raw.githubusercontent.com/scottsmith130/hal-json-schema/master/hal.json']
        );
        $this->assertTrue($this->validator->isValid());
    }

    public function testBasicSerializationWithCuries()
    {
        $self = $this->prophesize(EvolvableLinkInterface::class);
        $self->getHref()->willReturn('/');
        $self->getAttributes()->willReturn([]);
        $self->isTemplated()->willReturn(false);
        $self->getRels()->willReturn(['self']);
        $self->withHref('/')->willReturn($self->reveal());

        $c1 = $this->prophesize(EvolvableLinkInterface::class);
        $c1->getRels()->willReturn(['curies']);
        $c1->getHref()->willReturn('/docs/{rel}');
        $c1->isTemplated()->willReturn(true);
        $c1->getAttributes()->willReturn(['name' => 'docs']);
        $c1->withHref('/docs/{rel}')->willReturn($c1->reveal());

        $c2 = $this->prophesize(EvolvableLinkInterface::class);
        $c2->getRels()->willReturn(['curies']);
        $c2->getHref()->willReturn('/rels/{rel}');
        $c2->isTemplated()->willReturn(true);
        $c2->getAttributes()->willReturn(['name' => 'rel']);
        $c2->withHref('/rels/{rel}')->willReturn($c2->reveal());

        $entity = $this->prophesize(EntityInterface::class);
        $entity->getData()->willReturn(['id' => 5]);
        $entity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $entity->getLinks()->willReturn([$self->reveal(), $c1->reveal(), $c2->reveal()]);
        $entity->hasEmbedded()->willReturn(false);


        $this->validator->check(
            (new HalResponse(200, [], $entity->reveal()))->getBody()->getContents(),
            (object) ['$ref' => 'https://raw.githubusercontent.com/scottsmith130/hal-json-schema/master/hal.json']
        );
        $this->assertTrue($this->validator->isValid());
    }

    public function testSerializationWithEmbedded()
    {
        $self = $this->prophesize(EvolvableLinkInterface::class);
        $self->getHref()->willReturn('/');
        $self->getAttributes()->willReturn([]);
        $self->isTemplated()->willReturn(false);
        $self->getRels()->willReturn(['self']);
        $self->withHref('/')->willReturn($self->reveal());

        $embeddedEntity = $this->prophesize(EntityInterface::class);
        $embeddedEntity->getRel()->willReturn('rel');
        $embeddedEntity->getData()->willReturn(['name' => 'John']);
        $embeddedEntity->getEmbedded()->willReturn([]);
        $embeddedEntity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $embeddedEntity->hasEmbedded()->willReturn(false);
        $embeddedEntity->getLinks()->willReturn([$self->reveal()]);

        $entity = $this->prophesize(EntityInterface::class);
        $entity->getData()->willReturn(['id' => 5]);
        $entity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $entity->getLinks()->willReturn([$self->reveal()]);
        $entity->getEmbedded()->willReturn(['mock' => $embeddedEntity->reveal()]);
        $entity->hasEmbedded()->willReturn(true);

        // When the element is root
        $this->validator->check(
            (new HalResponse(200, [], $entity->reveal()))->getBody()->getContents(),
            (object) ['$ref' => 'https://raw.githubusercontent.com/scottsmith130/hal-json-schema/master/hal.json']
        );
        $this->assertTrue($this->validator->isValid());
    }

    public function testSerializationWithEmbeddedArray()
    {
        $self = $this->prophesize(EvolvableLinkInterface::class);
        $self->getHref()->willReturn('/');
        $self->getAttributes()->willReturn([]);
        $self->isTemplated()->willReturn(false);
        $self->getRels()->willReturn(['self']);
        $self->withHref('/')->willReturn($self->reveal());

        $embeddedEntity = $this->prophesize(EntityInterface::class);
        $embeddedEntity->getRel()->willReturn('rel');
        $embeddedEntity->getData()->willReturn(['name' => 'John']);
        $embeddedEntity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $embeddedEntity->hasEmbedded()->willReturn(false);
        $embeddedEntity->getLinks()->willReturn([$self->reveal()]);

        $entity = $this->prophesize(EntityInterface::class);
        $entity->getRel()->willReturn('root');
        $entity->getData()->willReturn(['id' => 5]);
        $entity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $entity->getLinks()->willReturn([$self->reveal()]);
        $entity->hasEmbedded()->willReturn(true);
        $entity->getEmbedded()->willReturn(['mock' => $embeddedEntity->reveal()]);

        // When the element is root
        $this->validator->check(
            (new HalResponse(200, [], $entity->reveal()))->getBody()->getContents(),
            (object) ['$ref' => 'https://raw.githubusercontent.com/scottsmith130/hal-json-schema/master/hal.json']
        );
        $this->assertTrue($this->validator->isValid());
    }
}
