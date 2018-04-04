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
     * @var Entity[][]
     */
    private $embedded = [];

    private $meta = [];

    private $error = false;

    public function __construct(string $rel, bool $isError = false)
    {
        $this->rel = $rel;
    }

    public function isError(): bool
    {
        return $this->error;
    }

    public function getRel(): string
    {
        return $this->rel;
    }

    public function withAddedMetaData(array $meta): EntityInterface
    {
        $self = clone $this;
        $self->meta = array_merge($self->meta, $meta);

        return $self;
    }

    public function withMetaData(array $meta): EntityInterface
    {
        $self = clone $this;
        $self->meta = $meta;

        return $self;
    }

    public function getMetaData(): array
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

    public function withData(array $data): EntityInterface
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

    public function getData(): array
    {
        return $this->data;
    }

    public function withAddedEmbedded(string $type, EntityInterface $entity, bool $collection = true): EntityInterface
    {
        $self = clone $this;
        if (!$collection) {
            $self->embedded[$type] = $entity;
        } else {
            if (!isset($self->embedded[$type])) {
                $self->embedded[$type] = [$entity];
            } else {
                $self->embedded[$type][] = $entity;
            }
        }

        return $self;
    }

    /**
     * @return EntityInterface[][]|EntityInterface[]
     */
    public function getEmbedded(): array
    {
        return $this->embedded;
    }
}
