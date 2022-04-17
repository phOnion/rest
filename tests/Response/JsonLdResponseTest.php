<?php

namespace Response;

use JsonSchema\Validator;
use Onion\Framework\Rest\Interfaces\EntityInterface;
use Onion\Framework\Rest\Responses\Json\LinkedDataResponse;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Link\EvolvableLinkInterface;

class JsonLdResponseTest extends TestCase
{
    // http://json-schema.org/draft-04/schema
    use ProphecyTrait;

    private $validator;

    protected function setUp(): void
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
            '@removed' => true,
            '@base' => 'http://example.com',
            'visible' => true
        ]);
        $entity->getRel()->willReturn('Thing');
        $entity->getData()->willReturn(['id' => 5]);
        $entity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $entity->getLinks()->willReturn([$self->reveal()]);
        $entity->withoutDataItem('id')->will(function () use (&$entity) {
            $entity->getData()->willReturn([]);
            return $entity->reveal();
        });
        $entity->hasEmbedded()->willReturn(false);

        $this->validator->check(
            (new LinkedDataResponse(200, [], $entity->reveal()))->getBody()->getContents(),
            (object) ['$ref' => 'http://json-schema.org/draft-04/schema']
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
            '@vocab' => 'http://schema.org',
        ]);
        $entity->getRel()->willReturn('Thing');
        $entity->getData()->willReturn(['id' => 5]);
        $entity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $entity->getLinks()->willReturn([$self->reveal()]);
        $entity->withoutDataItem('id')->will(function () use (&$entity) {
            $entity->getData()->willReturn([]);
            return $entity->reveal();
        });
        $entity->hasEmbedded()->willReturn(false);


        $this->validator->check(
            (new LinkedDataResponse(200, [], $entity->reveal()))->getBody()->getContents(),
            (object) ['$ref' => 'http://json-schema.org/draft-04/schema']
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
            '@base' => 'http://example.com'
        ]);
        $entity->getRel()->willReturn('Thing');
        $entity->getData()->willReturn(['id' => 5, 'spouse' => 25]);
        $entity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $entity->getLinks()->willReturn([$self->reveal(), $c1->reveal(), $c2->reveal()]);
        $entity->withoutDataItem('id')->will(function () use (&$entity) {
            $entity->getData()->willReturn(['spouse' => 25]);
            return $entity->reveal();
        });
        $entity->hasEmbedded()->willReturn(false);



        $this->validator->check(
            (new LinkedDataResponse(200, [], $entity->reveal()))->getBody()->getContents(),
            (object) ['$ref' => 'http://json-schema.org/draft-04/schema']
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
            '@base' => 'http://example.com'
        ]);
        $embeddedEntity->getRel()->willReturn('Thing');
        $embeddedEntity->getData()->willReturn(['id' => 5, 'name' => 'John']);
        $embeddedEntity->hasEmbedded()->willReturn(false);
        $embeddedEntity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $embeddedEntity->getLinks()->willReturn([$self->reveal()]);
        $embeddedEntity->withoutDataItem('id')->will(function () use (&$embeddedEntity) {
            $embeddedEntity->getData()->willReturn(['name' => 'John']);
            return $embeddedEntity->reveal();
        });

        $entity = $this->prophesize(EntityInterface::class);
        $entity->getMetaData()->willReturn([
            '@base' => 'http://example.com'
        ]);
        $entity->getRel()->willReturn('Thing');
        $entity->getData()->willReturn(['id' => 5]);
        $entity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $entity->getLinks()->willReturn([$self->reveal()]);
        $entity->hasEmbedded()->willReturn(true);
        $entity->getEmbedded()->willReturn(['mock' => $embeddedEntity->reveal()]);
        $entity->withoutDataItem('id')->will(function () use (&$entity) {
            $entity->getData()->willReturn([]);
            return $entity->reveal();
        });

        $this->validator->check(
            (new LinkedDataResponse(200, [], $entity->reveal()))->getBody()->getContents(),
            (object) ['$ref' => 'http://json-schema.org/draft-04/schema']
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
            '@base' => 'http://example.com'
        ]);
        $embeddedEntity->getRel()->willReturn('Thing');
        $embeddedEntity->getData()->willReturn(['id' => 5, 'name' => 'John']);
        $embeddedEntity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $embeddedEntity->getLinks()->willReturn([$self->reveal()]);
        $embeddedEntity->hasEmbedded()->willReturn(false);
        $embeddedEntity->withoutDataItem('id')->will(function () use (&$embeddedEntity) {
            $embeddedEntity->getData()->willReturn(['name' => 'John']);
            return $embeddedEntity->reveal();
        });

        $entity = $this->prophesize(EntityInterface::class);
        $entity->getMetaData()->willReturn([
            '@base' => 'http://example.com'
        ]);
        $entity->getRel()->willReturn('Thing');
        $entity->getData()->willReturn(['id' => 5]);
        $entity->getLinksByRel('self')->willReturn([$self->reveal()]);
        $entity->getLinks()->willReturn([$self->reveal()]);
        $entity->hasEmbedded()->willReturn(true);
        $entity->getEmbedded()->willReturn(['mock' => $embeddedEntity->reveal()]);
        $entity->withoutDataItem('id')->will(function () use (&$entity) {
            $entity->getData()->willReturn([]);
            return $entity->reveal();
        });

        $this->validator->check(
            (new LinkedDataResponse(200, [], $entity->reveal()))->getBody()->getContents(),
            (object) ['$ref' => 'http://json-schema.org/draft-04/schema']
        );
        $this->assertTrue($this->validator->isValid());
    }
}
