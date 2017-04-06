<?php

namespace Serializers;

use Onion\Framework\Http\Header\Interfaces\AcceptInterface;
use Onion\Framework\Rest\Interfaces\EntityInterface;
use Onion\Framework\Rest\Serializers\HalJsonSerializer;
use Psr\Link\EvolvableLinkInterface;

class HalJsonSerializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var HalJsonSerializer
     */
    private $testable;

    public function setUp()
    {
        $this->testable = new HalJsonSerializer();
    }

    public function testSupport()
    {
        $accept = $this->prophesize(AcceptInterface::class);
        $accept->supports('application/hal+json')->wilLReturn(true);

        $this->assertTrue($this->testable->supports($accept->reveal()));
    }

    public function testContentType()
    {
        $this->assertSame('application/hal+json', $this->testable->getContentType());
    }

    public function testExceptionOnMissingLinks()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('must have "self" link');
        $entity = $this->prophesize(EntityInterface::class);
        $entity->getData()->willReturn(['id' => 5]);
        $entity->getLinksByRel('self')->willReturn([]);

        $this->testable->serialize($entity->reveal());
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


        $this->assertJsonStringEqualsJsonString(
            '{"_links": {"self": {"href": "/", "templated": false}}, "id": 5}',
            $this->testable->serialize($entity->reveal())
        );
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


        $this->assertJsonStringEqualsJsonString(
            '{"_links": {"self": {"href": "/", "templated": false}, "curies":[' .
            '{"href":"/docs/{rel}", "templated":true, "name":"docs"}, ' .
            '{"href":"/rels/{rel}", "templated":true, "name":"rel"}' .
            ']}, "id": 5}',
            $this->testable->serialize($entity->reveal())
        );
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
        $embeddedEntity->getData()->willReturn(['name' => 'John']);
        $embeddedEntity->getEmbedded()->willReturn([]);
        $embeddedEntity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $embeddedEntity->getLinks()->willReturn([$self->reveal()]);

        $entity = $this->prophesize(EntityInterface::class);
        $entity->getData()->willReturn(['id' => 5]);
        $entity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $entity->getLinks()->willReturn([$self->reveal()]);
        $entity->getEmbedded()->willReturn(['mock' => $embeddedEntity->reveal()]);

        // When the element is root
        $this->assertJsonStringEqualsJsonString(
            '{"_links": {"self": {"href": "/", "templated": false}}, "id": 5, "_embedded": {"mock": [{"_links": {"self": {"href": "/", "templated": false}}, "name": "John"}]}}',
            $this->testable->serialize($entity->reveal(), true)
        );

        // When the element is not root (avoids creating a large test case)
        $this->assertJsonStringEqualsJsonString(
            '{"_links": {"self": {"href": "/", "templated": false}}, "id": 5}',
            $this->testable->serialize($entity->reveal())
        );
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
        $embeddedEntity->getData()->willReturn(['name' => 'John']);
        $embeddedEntity->getEmbedded()->willReturn([]);
        $embeddedEntity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $embeddedEntity->getLinks()->willReturn([$self->reveal()]);

        $entity = $this->prophesize(EntityInterface::class);
        $entity->getData()->willReturn(['id' => 5]);
        $entity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $entity->getLinks()->willReturn([$self->reveal()]);
        $entity->getEmbedded()->willReturn(['mock' => [$embeddedEntity->reveal()]]);

        // When the element is root
        $this->assertJsonStringEqualsJsonString(
            '{"_links": {"self": {"href": "/", "templated": false}}, "id": 5, "_embedded": {"mock": [{"_links": {"self": {"href": "/", "templated": false}}, "name": "John"}]}}',
            $this->testable->serialize($entity->reveal(), true)
        );

        // When the element is not root (avoids creating a large test case)
        $this->assertJsonStringEqualsJsonString(
            '{"_links": {"self": {"href": "/", "templated": false}}, "id": 5}',
            $this->testable->serialize($entity->reveal())
        );
    }
}
