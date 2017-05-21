<?php

namespace Response;

use Onion\Framework\Rest\Interfaces\EntityInterface;
use Onion\Framework\Rest\Response\JsonLdResponse;
use Psr\Link\EvolvableLinkInterface;


class JsonLdResponseTest extends \PHPUnit_Framework_TestCase
{
    public function testBasicSerialization()
    {
        $self = $this->prophesize(EvolvableLinkInterface::class);
        $self->getHref()->willReturn('/{id}');
        $self->getAttributes()->willReturn([]);
        $self->isTemplated()->willReturn(false);
        $self->getRels()->willReturn(['self']);

        $entity = $this->prophesize(EntityInterface::class);
        $entity->getMetaData()->willReturn([
            'ld' => [
                '@removed' => true,
                '@base' => 'http://example.com',
                'visible' => true
            ]
        ]);
        $entity->getRel()->willReturn('Thing');
        $entity->getData()->willReturn(['id' => 5]);
        $entity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $entity->getLinks()->willReturn([$self->reveal()]);
        $entity->withoutDataItem('id')->will(function () use (&$entity) {
            $entity->getData()->willReturn([]);
            return $entity->reveal();
        });
        $entity->getEmbedded()->willReturn([]);



        $this->assertJsonStringEqualsJsonString(
            '{"@context": {"@base": "http://example.com", "visible": true}, ' .
            '"@id": "http://example.com/5", ' .
            '"@type": "Thing"}',
            (string) (new JsonLdResponse($entity->reveal()))->getBody()->getContents()
        );
    }

    public function testBasicSerializationWithOnlySchemaContext()
    {
        $self = $this->prophesize(EvolvableLinkInterface::class);
        $self->getHref()->willReturn('/{id}');
        $self->getAttributes()->willReturn([]);
        $self->isTemplated()->willReturn(false);
        $self->getRels()->willReturn(['self']);

        $entity = $this->prophesize(EntityInterface::class);
        $entity->getMetaData()->willReturn([
            'ld' => [
                '@vocab' => 'http://schema.org',
            ]
        ]);
        $entity->getRel()->willReturn('Thing');
        $entity->getData()->willReturn(['id' => 5]);
        $entity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $entity->getLinks()->willReturn([$self->reveal()]);
        $entity->withoutDataItem('id')->will(function () use (&$entity) {
            $entity->getData()->willReturn([]);
            return $entity->reveal();
        });
        $entity->getEmbedded()->willReturn([]);


        $this->assertJsonStringEqualsJsonString(
            '{"@context": "http://schema.org", ' .
            '"@id": "/5", ' .
            '"@type": "Thing"}',
            (string) (new JsonLdResponse($entity->reveal()))->getBody()->getContents()
        );
    }

    public function testBasicSerializationWithAdditionalLinks()
    {
        $self = $this->prophesize(EvolvableLinkInterface::class);
        $self->getHref()->willReturn('/{id}');
        $self->isTemplated()->willReturn(false);
        $self->getRels()->willReturn(['self']);
        $self->withHref('/')->willReturn($self->reveal());

        $c1 = $this->prophesize(EvolvableLinkInterface::class);
        $c1->getRels()->willReturn(['spouse']);
        $c1->getHref()->willReturn('/users/{spouse}');
        $c1->isTemplated()->willReturn(true);
        $c1->getAttributes()->willReturn(['name' => 'profile']);
        $c1->withHref('http://example.com/users/{spouse}')->will(function () use ($c1) {
            $c1->getHref()->willReturn('http://example.com/users/25');
            $c1->isTemplated()->willReturn(false);
            return $c1->reveal();
        });

        $c2 = $this->prophesize(EvolvableLinkInterface::class);
        $c2->getRels()->willReturn(['curies']);
        $c2->getHref()->willReturn('/rels/{rel}');
        $c2->isTemplated()->willReturn(true);
        $c2->getAttributes()->willReturn(['name' => 'rels']);
        $c2->withHref('http://example.com/rels/{rel}')->willReturn($c2->reveal());

        $entity = $this->prophesize(EntityInterface::class);
        $entity->getMetaData()->willReturn([
            'ld' => [
                '@base' => 'http://example.com'
            ]
        ]);
        $entity->getRel()->willReturn('Thing');
        $entity->getData()->willReturn(['id' => 5, 'spouse' => 25]);
        $entity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $entity->getLinks()->willReturn([$self->reveal(), $c1->reveal(), $c2->reveal()]);
        $entity->withoutDataItem('id')->will(function () use (&$entity) {
            $entity->getData()->willReturn(['spouse' => 25]);
            return $entity->reveal();
        });
        $entity->getEmbedded()->willReturn([]);



        $this->assertJsonStringEqualsJsonString(
            '{"@context": {"@base": "http://example.com"}, ' .
            '"@id": "http://example.com/5", ' .
            '"@type": "Thing", "spouse":"http://example.com/users/25"}',
            (string) (new JsonLdResponse($entity->reveal()))->getBody()->getContents()
        );
    }

    public function testSerializationWithEmbedded()
    {
        $self = $this->prophesize(EvolvableLinkInterface::class);
        $self->getHref()->willReturn('/{id}');
        $self->getAttributes()->willReturn([]);
        $self->isTemplated()->willReturn(false);
        $self->getRels()->willReturn(['self']);
        $self->withHref('/')->willReturn($self->reveal());

        $embeddedEntity = $this->prophesize(EntityInterface::class);
        $embeddedEntity->getMetaData()->willReturn([
            'ld' => [
                '@base' => 'http://example.com'
            ]
        ]);
        $embeddedEntity->getRel()->willReturn('Thing');
        $embeddedEntity->getData()->willReturn(['id' => 5, 'name' => 'John']);
        $embeddedEntity->getEmbedded()->willReturn([]);
        $embeddedEntity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $embeddedEntity->getLinks()->willReturn([$self->reveal()]);
        $embeddedEntity->withoutDataItem('id')->will(function () use (&$embeddedEntity) {
            $embeddedEntity->getData()->willReturn(['name' => 'John']);
            return $embeddedEntity->reveal();
        });

        $entity = $this->prophesize(EntityInterface::class);
        $entity->getMetaData()->willReturn([
            'ld' => [
                '@base' => 'http://example.com'
            ]
        ]);
        $entity->getRel()->willReturn('Thing');
        $entity->getData()->willReturn(['id' => 5]);
        $entity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $entity->getLinks()->willReturn([$self->reveal()]);
        $entity->getEmbedded()->willReturn(['mock' => $embeddedEntity->reveal()]);
        $entity->withoutDataItem('id')->will(function () use (&$entity) {
            $entity->getData()->willReturn([]);
            return $entity->reveal();
        });

        // When the element is root
        $this->assertJsonStringEqualsJsonString(
            '{"@context": {"@base": "http://example.com"}, ' .
            '"@id": "http://example.com/5", ' .
            '"@type": "Thing", "mock": {"@id": "http://example.com/5", "@type": "Thing", "@context": {"@base": "http://example.com"},"name": "John"}}',
            (string) (new JsonLdResponse($entity->reveal()))->getBody()->getContents()
        );
    }

    public function testSerializationWithEmbeddedArray()
    {
        $self = $this->prophesize(EvolvableLinkInterface::class);
        $self->getHref()->willReturn('/{id}');
        $self->getAttributes()->willReturn([]);
        $self->isTemplated()->willReturn(false);
        $self->getRels()->willReturn(['self']);
        $self->withHref('/')->willReturn($self->reveal());

        $embeddedEntity = $this->prophesize(EntityInterface::class);
        $embeddedEntity->getMetaData()->willReturn([
            'ld' => [
                '@base' => 'http://example.com'
            ]
        ]);
        $embeddedEntity->getRel()->willReturn('Thing');
        $embeddedEntity->getData()->willReturn(['id' => 5, 'name' => 'John']);
        $embeddedEntity->getEmbedded()->willReturn([]);
        $embeddedEntity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $embeddedEntity->getLinks()->willReturn([$self->reveal()]);
        $embeddedEntity->withoutDataItem('id')->will(function () use (&$embeddedEntity) {
            $embeddedEntity->getData()->willReturn(['name' => 'John']);
            return $embeddedEntity->reveal();
        });

        $entity = $this->prophesize(EntityInterface::class);
        $entity->getMetaData()->willReturn([
            'ld' => [
                '@base' => 'http://example.com'
            ]
        ]);
        $entity->getRel()->willReturn('Thing');
        $entity->getData()->willReturn(['id' => 5]);
        $entity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $entity->getLinks()->willReturn([$self->reveal()]);
        $entity->getEmbedded()->willReturn(['mock' => [$embeddedEntity->reveal()]]);
        $entity->withoutDataItem('id')->will(function () use (&$entity) {
            $entity->getData()->willReturn([]);
            return $entity->reveal();
        });

        // When the element is root
        $this->assertJsonStringEqualsJsonString(
            '{"@context": {"@base": "http://example.com"}, ' .
            '"@id": "http://example.com/5", ' .
            '"@type": "Thing", "mock": [{"@id": "http://example.com/5", "@type": "Thing", "@context": {"@base": "http://example.com"},"name": "John"}]}',
            (string) (new JsonLdResponse($entity->reveal()))->getBody()->getContents()
        );
    }
}
