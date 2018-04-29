<?php declare(strict_types=1);
namespace Onion\Framework\Rest;

use Fig\Link\EvolvableLinkProviderTrait;
use Onion\Framework\Rest\Interfaces\EntityInterface;

/**
 * Class Entity
 *
 * @package Onion\Framework\Rest
 *
 * @codeCoverageIgnore
 */
class Entity implements EntityInterface
{
    use EvolvableLinkProviderTrait;

    /**
     * @var string The rel of the element
     */
    private $rel;

    /**
     * @var array
     */
    private $data = [];

    /**
     * @var \SplObjectStorage
     */
    private $embedded = [];

    private $meta = [];

    public function __construct(string $rel)
    {
        $this->rel = $rel;
        $this->embedded = new \SplObjectStorage();
    }

    public function getRel(): string
    {
        return $this->rel;
    }

    public function withAddedMetaData(iterable $meta): EntityInterface
    {
        $self = clone $this;
        $self->meta = array_merge($self->meta, $meta);

        return $self;
    }

    public function withMetaData(iterable $meta): EntityInterface
    {
        $self = clone $this;
        $self->meta = $meta;

        return $self;
    }

    public function getMetaData(): iterable
    {
        return $this->meta;
    }

    public function withDataItem(string $name, $value): EntityInterface
    {
        $self = clone $this;
        $self->data[$name] = $value;

        return $self;
    }

    public function withoutDataItem(string $name): EntityInterface
    {
        $self = clone $this;
        unset($self->data[$name]);

        return $self;
    }

    public function getDataItem(string $name, $default = null)
    {
        return $this->data[$name] ?? $default;
    }

    public function withData(iterable $data): EntityInterface
    {
        $self = clone $this;
        $self->data = array_merge($self->data, $data);

        return $self;
    }

    public function withoutData(): EntityInterface
    {
        $self = clone $this;
        $self->data = [];

        return $self;
    }

    public function getData(): iterable
    {
        return $this->data;
    }

    public function withEmbedded(EntityInterface $entity): EntityInterface
    {
        $self = clone $this;
        if ($self->embedded->contains($entity)) {
            throw new \InvalidArgumentException('Attempting to insert duplicate entity');
        }
        $this->embedded->attach($entity);

        return $self;
    }

    public function withoutEmbedded(EntityInterface $entity): EntityInterface
    {
        $self = clone $this;
        if ($this->embedded->contains($entity)) {
            $self->embedded->detach($entity);
        }

        return $self;
    }

    public function getEmbedded(): iterable
    {
        return $this->embedded;
    }

    public function hasEmbedded(): bool
    {
        return !empty($this->embedded);
    }
}
