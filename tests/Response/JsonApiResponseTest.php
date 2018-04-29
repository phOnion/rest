<?php

namespace Tests\Response;

use JsonSchema\Validator;
use Onion\Framework\Rest\Interfaces\EntityInterface;
use Onion\Framework\Rest\Response\JsonApiResponse;
use Psr\Link\EvolvableLinkInterface;

class JsonApiResponseTest extends \PHPUnit_Framework_TestCase
{
    /** @var Validator */
    private $validator;

    public function setUp()
    {
        $this->validator = new Validator();
    }
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
        $entity->hasEmbedded()->willReturn(false);
        $this->validator->check(
            (new JsonApiResponse($entity->reveal()))->getBody()->getContents(),
            (object) ['$ref' => 'http://jsonapi.org/schema']
        );
        $this->assertTrue($this->validator->isValid());
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
        $entity->hasEmbedded()->willReturn(false);
        $entity->getRel()->willReturn('user');
        $entity->getData()->willReturn(['id' => 5]);
        $entity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $entity->getLinks()->willReturn([$self->reveal()]);
        $entity->getDataItem('id', false)->willReturn(false);
        $entity->withoutDataItem('id')->will(function () use (&$entity) {
            $entity->getData()->willReturn([]);
            return $entity->reveal();
        });


        $this->validator->check(
            (new JsonApiResponse($entity->reveal()))->getBody()->getContents(),
            (object) ['$ref' => 'http://jsonapi.org/schema']
        );
        $this->assertTrue($this->validator->isValid());
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
        $entity->getData()->willReturn(['id' => 5]);
        $entity->getDataItem('id', false)->willReturn(5);
        $entity->getDataItem('id')->willReturn(5);
        $entity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $entity->getLinks()->willReturn([$self->reveal()]);
        $entity->withoutDataItem('id')->will(function () use (&$entity) {
            $entity->getData()->willReturn([]);
            return $entity->reveal();
        });
        $entity->hasEmbedded()->willReturn(false);


        $this->validator->check(
            (new JsonApiResponse($entity->reveal()))->getBody()->getContents(),
            (object) ['$ref' => 'http://jsonapi.org/schema']
        );
        $this->assertTrue($this->validator->isValid());
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
        $entity->hasEmbedded()->willReturn(false);



        $this->validator->check(
            (new JsonApiResponse($entity->reveal()))->getBody()->getContents(),
            (object) ['$ref' => 'http://jsonapi.org/schema']
        );
        $this->assertTrue($this->validator->isValid());
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
            'api' => [
                '@type' => 'Thing'
            ]
        ]);
        $embeddedEntity->getRel()->willReturn('relative');
        $embeddedEntity->getData()->willReturn(['id' => 5, 'name' => 'John']);
        $embeddedEntity->hasEmbedded()->willReturn(false);
        $embeddedEntity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $embeddedEntity->getLinks()->willReturn([$self->reveal()]);
        $embeddedEntity->getDataItem('id', false)->willReturn(5);
        $embeddedEntity->getDataItem('id')->willReturn(5);
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
        $entity->getRel()->willReturn('master');
        $entity->getData()->willReturn(['id' => 5]);
        $entity->getDataItem('id', false)->willReturn(5);
        $entity->getDataItem('id')->willReturn(5);
        $entity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $entity->getLinks()->willReturn([$self->reveal()]);
        $entity->hasEmbedded()->willReturn(true);
        $entity->getEmbedded()->willReturn([$embeddedEntity->reveal()]);
        $entity->withoutDataItem('id')->will(function () use (&$entity) {
            $entity->getData()->willReturn([]);
            return $entity->reveal();
        });

        $this->validator->check(
            (new JsonApiResponse($entity->reveal()))->getBody()->getContents(),
            (object) ['$ref' => 'http://jsonapi.org/schema']
        );
        $this->assertTrue($this->validator->isValid());
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
            'api' => [
                '@type' => 'Thing'
            ]
        ]);
        $embeddedEntity->getRel()->willReturn('Thing');
        $embeddedEntity->getData()->willReturn(['id' => 5, 'name' => 'John']);
        $embeddedEntity->hasEmbedded()->willReturn(false);
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
        $entity->getRel()->willReturn('Thing');
        $entity->getData()->willReturn(['id' => 5]);
        $entity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $entity->getLinks()->willReturn([$self->reveal()]);
        $entity->getDataItem('id', false)->willReturn(5);
        $entity->getDataItem('id')->willReturn(5);
        $entity->hasEmbedded()->willReturn(true);
        $entity->getEmbedded()->willReturn([$embeddedEntity->reveal()]);
        $entity->withoutDataItem('id')->will(function () use (&$entity) {
            $entity->getData()->willReturn([]);
            return $entity->reveal();
        });

        $this->validator->check(
            (new JsonApiResponse($entity->reveal()))->getBody()->getContents(),
            (object) ['$ref' => 'http://jsonapi.org/schema']
        );
        $this->assertTrue($this->validator->isValid());
    }

    public function testErrorResponse()
    {
        $error = $this->prophesize(EntityInterface::class);
        $error->getData()->willReturn([
            'id' => 'c6e29260-b3ad-47bd-9f02-367e366dda3f',
            'title' => 'Not Found',
            'detail' => 'The page you requested could not be found',
            'code' => '1234',
        ]);
        $error->getMetaData()->willReturn([]);
        $error->getLinksByRel('self')->willReturn([]);
        $error->getLinks()->willReturn([]);
        $error->getRel()->wilLReturn('error');

        $entity = $this->prophesize(EntityInterface::class);
        $entity->getEmbedded()->willReturn([$error->reveal()]);
        $entity->getMetaData()->willReturn([]);
        $entity->getLinks()->willReturn([]);

        $response = new JsonApiResponse(
            $entity->reveal(),
            404,
            [],
            JsonApiResponse::RESPONSE_TYPE_ERROR
        );

        $this->validator->check(
            $response->getBody()->getContents(),
            (object) ['$ref' => 'http://jsonapi.org/schema']
        );
        $this->assertTrue($this->validator->isValid());
    }

    public function testInfoResponse()
    {
        $self = $this->prophesize(EvolvableLinkInterface::class);
        $self->getHref()->willReturn('/{id}');
        $self->getAttributes()->willReturn([]);
        $self->isTemplated()->willReturn(false);
        $self->getRels()->willReturn(['self']);

        $entity = $this->prophesize(EntityInterface::class);
        $entity->getMetaData()->willReturn([
            'api' => [
                'author' => 'Dimitar Dimitrov',
            ]
        ]);
        $entity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $entity->getLinks()->willReturn([$self->reveal()]);


        $response = new JsonApiResponse(
            $entity->reveal(),
            404,
            [],
            JsonApiResponse::RESPONSE_TYPE_INFO
        );

        $this->validator->check(
            $response->getBody()->getContents(),
            (object) ['$ref' => 'http://jsonapi.org/schema']
        );
        $this->assertTrue($this->validator->isValid());
    }
}
