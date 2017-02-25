<?php declare(strict_types=1);
namespace Onion\Framework\Rest;

use Fig\Link\EvolvableLinkProviderTrait;

class Entity
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
     * @var Entity[][]
     */
    private $embedded = [];

    private $meta = [];

    private $error = false;

    public function isError()
    {
        return $this->error;
    }

    public function __construct(string $rel, bool $isError = false)
    {
        $this->rel = $rel;
    }

    public function getRel(): string
    {
        return $this->rel;
    }

    public function withMeta(array $meta): Entity
    {
        $self = clone $this;
        $self->meta = $meta;

        return $self;
    }

    public function getMeta()
    {
        return $this->meta;
    }

    public function withDataItem(string $name, $value): Entity
    {
        $self = clone $this;
        $self->data[$name] = $value;

        return $self;
    }

    public function withoutDataItem($name): Entity
    {
        $self = clone $this;
        unset($self->data[$name]);

        return $self;
    }

    public function getDataItem(string $name, $default = null)
    {
        return $this->data[$name] ?? $default;
    }

    public function withData(array $data): Entity
    {
        $self = clone $this;
        $self->data = array_merge($self->data, $data);

        return $self;
    }

    public function withoutData(array $keys): Entity
    {
        $self = clone $this;
        $self->data = array_filter($self->data, function ($index) use ($keys) {
            return !in_array($index, $keys, true);
        }, ARRAY_FILTER_USE_KEY);

        return $self;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function addEmbedded(Entity $entity): Entity
    {
        $self = clone $this;
        if (!array_key_exists($entity->getRel(), $self->embedded)) {
            $self->embedded[$entity->getRel()] = [];
        }

        $self->embedded[$entity->getRel()][] = $entity;

        return $self;
    }

    /**
     * @return array
     */
    public function getEmbedded(): array
    {
        return $this->embedded;
    }

    public function addMeta(string $name, $value): Entity
    {
        $self = clone $this;
        $self->$name = $value;

        return $self;
    }
}
