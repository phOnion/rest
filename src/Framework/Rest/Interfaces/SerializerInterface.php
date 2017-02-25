<?php declare(strict_types = 1);
namespace Onion\Framework\Rest\Interfaces;

use Onion\Framework\Http\Header\Accept;
use Onion\Framework\Rest\Entity;

interface SerializerInterface
{
    public function getContentType(): string;
    public function supports(Accept $accept): bool;
    public function serialize(Entity $entity): string;
}
