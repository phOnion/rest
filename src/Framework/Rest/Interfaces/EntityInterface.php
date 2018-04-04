<?php
declare(strict_types=1);
namespace Onion\Framework\RestInterfaces;

interface EntityInterface
{
    public function isError(): bool;
    public function getRel(): string;
    public function withAddedMetaData(array $meta): EntityInterface;
    public function withMetaData(array $meta): EntityInterface;
    public function getMetaData(): array;
    public function withDataItem(string $name, $value): EntityInterface;
    public function withoutDataItem(string $name): EntityInterface;
    public function getDataItem(string $name, $default = null);
    public function withData(array $data): EntityInterface;
    public function withoutData(): EntityInterface;
    public function getData(): array;
    public function withAddedEmbedded(string $type, EntityInterface $entity, bool $collection = true): EntityInterface;
    public function getEmbedded(): array;
}
