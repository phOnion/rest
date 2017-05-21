<?php

namespace Tests\Response;

use Onion\Framework\Rest\Interfaces\EntityInterface;
use Onion\Framework\Rest\Response\JsonApiResponse;
use Psr\Link\EvolvableLinkInterface;

class JsonApiResponseTest extends \PHPUnit_Framework_TestCase
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
            'api' => [
                '@type' => 'Thing'
            ]
        ]);
        $entity->isError()->willReturn(false);
        $entity->getRel()->willReturn('user');
        $entity->getData()->willReturn(['id' => 5]);
        $entity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $entity->getLinks()->willReturn([$self->reveal()]);
        $entity->getDataItem('id', false)->willReturn(5);
        $entity->getDataItem('id')->willReturn(5);
        $entity->withoutDataItem('id')->will(function () use (&$entity) {
            $entity->getData()->willReturn([]);
            return $entity->reveal();
        });
        $entity->getEmbedded()->willReturn([]);

        $this->assertJsonStringEqualsJsonString(
            '{"links": {"self": "/{id}"}, ' .
            '"id": "5", ' .
            '"type": "Thing"}',
            (string) (new JsonApiResponse($entity->reveal()))->getBody()->getContents()
        );
    }

    public function testBasicSerializationWithoutId()
    {
        $self = $this->prophesize(EvolvableLinkInterface::class);
        $self->getHref()->willReturn('/{id}');
        $self->getAttributes()->willReturn([]);
        $self->isTemplated()->willReturn(false);
        $self->getRels()->willReturn(['self']);

        $entity = $this->prophesize(EntityInterface::class);
        $entity->getMetaData()->willReturn([
            'api' => [
                '@type' => 'Thing'
            ]
        ]);
        $entity->isError()->willReturn(false);
        $entity->getRel()->willReturn('user');
        $entity->getData()->willReturn(['id' => 5]);
        $entity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $entity->getLinks()->willReturn([$self->reveal()]);
        $entity->getDataItem('id', false)->willReturn(false);
        $entity->withoutDataItem('id')->will(function () use (&$entity) {
            $entity->getData()->willReturn([]);
            return $entity->reveal();
        });
        $entity->getEmbedded()->willReturn([]);

        $this->assertJsonStringEqualsJsonString(
            '{"links": {"self": "/{id}"}, "data": []}',
            (string) (new JsonApiResponse($entity->reveal()))->getBody()->getContents()
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
            'api' => [
                '@type' => 'Thing',
            ]
        ]);
        $entity->getRel()->willReturn('Thing');
        $entity->isError()->willReturn(false);
        $entity->getData()->willReturn(['id' => 5]);
        $entity->getDataItem('id', false)->willReturn(5);
        $entity->getDataItem('id')->willReturn(5);
        $entity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $entity->getLinks()->willReturn([$self->reveal()]);
        $entity->withoutDataItem('id')->will(function () use (&$entity) {
            $entity->getData()->willReturn([]);
            return $entity->reveal();
        });
        $entity->getEmbedded()->willReturn([]);


        $this->assertJsonStringEqualsJsonString(
            '{"links": {"self": "/{id}"}, ' .
            '"id": "5", ' .
            '"type": "Thing"}',
            (string) (new JsonApiResponse($entity->reveal()))->getBody()->getContents()
        );
    }

    public function testBasicSerializationWithAdditionalLinks()
    {
        $self = $this->prophesize(EvolvableLinkInterface::class);
        $self->getHref()->willReturn('/{id}');
        $self->isTemplated()->willReturn(false);
        $self->getRels()->willReturn(['self']);
        $self->withHref('/')->willReturn($self->reveal());
        $self->getAttributes()->willReturn([]);

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
            'api' => [
                '@type' => 'Thing'
            ]
        ]);
        $entity->isError()->willReturn(false);
        $entity->getRel()->willReturn('Thing');
        $entity->getDataItem('id', false)->willReturn(5);
        $entity->getDataItem('id')->willReturn(5);
        $entity->getData()->willReturn(['id' => 5, 'spouse' => 25]);
        $entity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $entity->getLinks()->willReturn([$self->reveal(), $c1->reveal(), $c2->reveal()]);
        $entity->withoutDataItem('id')->will(function () use (&$entity) {
            $entity->getData()->willReturn(['spouse' => 25]);
            return $entity->reveal();
        });
        $entity->getEmbedded()->willReturn([]);



        $this->assertJsonStringEqualsJsonString(
            '{"links": {"self": "/{id}", "spouse": {"href": "/users/{spouse}", "meta": {"name": "profile"}}}, ' .
            '"id": 5, ' .
            '"type": "Thing", "attributes": {"spouse":25}}',
            (string) (new JsonApiResponse($entity->reveal()))->getBody()->getContents()
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
        $embeddedEntity->isError()->willReturn(false);
        $embeddedEntity->getMetaData()->willReturn([
            'api' => [
                '@type' => 'Thing'
            ]
        ]);
        $embeddedEntity->getRel()->willReturn('relative');
        $embeddedEntity->getData()->willReturn(['id' => 5, 'name' => 'John']);
        $embeddedEntity->getEmbedded()->willReturn([]);
        $embeddedEntity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $embeddedEntity->getLinks()->willReturn([$self->reveal()]);
        $embeddedEntity->getDataItem('id', false)->willReturn(5);
        $embeddedEntity->getDataItem('id')->willReturn(5);
        $embeddedEntity->withoutDataItem('id')->will(function () use (&$embeddedEntity) {
            $embeddedEntity->getData()->willReturn(['name' => 'John']);
            return $embeddedEntity->reveal();
        });

        $entity = $this->prophesize(EntityInterface::class);
        $entity->isError()->willReturn(false);
        $entity->getMetaData()->willReturn([
            'api' => [
                '@type' => 'Thing'
            ]
        ]);
        $entity->getRel()->willReturn('master');
        $entity->getData()->willReturn(['id' => 5]);
        $entity->getDataItem('id', false)->willReturn(5);
        $entity->getDataItem('id')->willReturn(5);
        $entity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $entity->getLinks()->willReturn([$self->reveal()]);
        $entity->getEmbedded()->willReturn(['mock' => $embeddedEntity->reveal()]);
        $entity->withoutDataItem('id')->will(function () use (&$entity) {
            $entity->getData()->willReturn([]);
            return $entity->reveal();
        });

        // When the element is root
        $this->assertJsonStringEqualsJsonString(
            '{"data": {"links": {"self": "/{id}"}, ' .
            '"id": "5", ' .
            '"type": "Thing", "relationships": {"mock": {"links": {"self": "/{id}"}, "data": {"id": 5, "type": "Thing"}}}}, "included": [{"id": "5", "type": "Thing", "links": {"self": "/{id}"}, "attributes": {"name": "John"}}]}',
            (string) (new JsonApiResponse($entity->reveal()))->getBody()->getContents()
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
        $embeddedEntity->isError()->willReturn(false);
        $embeddedEntity->getMetaData()->willReturn([
            'api' => [
                '@type' => 'Thing'
            ]
        ]);
        $embeddedEntity->getRel()->willReturn('Thing');
        $embeddedEntity->getData()->willReturn(['id' => 5, 'name' => 'John']);
        $embeddedEntity->getEmbedded()->willReturn([]);
        $embeddedEntity->getDataItem('id', false)->willReturn(5);
        $embeddedEntity->getDataItem('id')->willReturn(5);
        $embeddedEntity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $embeddedEntity->getLinks()->willReturn([$self->reveal()]);
        $embeddedEntity->withoutDataItem('id')->will(function () use (&$embeddedEntity) {
            $embeddedEntity->getData()->willReturn(['name' => 'John']);
            return $embeddedEntity->reveal();
        });

        $entity = $this->prophesize(EntityInterface::class);
        $entity->getMetaData()->willReturn([
            'api' => [
                '@type' => 'Thing'
            ]
        ]);
        $entity->isError()->willReturn(false);
        $entity->getRel()->willReturn('Thing');
        $entity->getData()->willReturn(['id' => 5]);
        $entity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $entity->getLinks()->willReturn([$self->reveal()]);
        $entity->getDataItem('id', false)->willReturn(5);
        $entity->getDataItem('id')->willReturn(5);
        $entity->getEmbedded()->willReturn(['mock' => [$embeddedEntity->reveal()]]);
        $entity->withoutDataItem('id')->will(function () use (&$entity) {
            $entity->getData()->willReturn([]);
            return $entity->reveal();
        });

        // When the element is root
        $this->assertJsonStringEqualsJsonString(
            '{"data": {"links": {"self": "/{id}"}, ' .
            '"id": "5", ' .
            '"type": "Thing", "relationships": {"mock": [{"links": {"self": "/{id}"}, "data": [{"id": 5, "type": "Thing"}]}]}}, "included": [{"id": "5", "type": "Thing", "links": {"self": "/{id}"}, "attributes": {"name": "John"}}]}',
            (string) (new JsonApiResponse($entity->reveal()))->getBody()->getContents()
        );
    }

    public function testEntityErrorHandling()
    {
        $entity = $this->prophesize(EntityInterface::class);
        $entity->isError()->willReturn(true);
        $entity->getLinks()->willReturn([]);
        $entity->getDataItem('id')->willReturn('unique-id');
        $entity->getDataItem('source')->willReturn([]);
        $entity->getDataItem('title')->willReturn('Error');
        $entity->getDataItem('detail')->willReturn('Some error info');
        $entity->getMetaData()->willReturn([]);

        $this->assertJsonStringEqualsJsonString(<<<JSON
{
    "id": "unique-id",
    "status": 400,
    "title": "Error",
    "detail": "Some error info",
    "meta": [],
    "source": [],
    "links": []
}
JSON
, (new JsonApiResponse($entity->reveal(), 400))->getBody()->getContents());
    }
}
