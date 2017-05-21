<?php declare(strict_types=1);

namespace Onion\Framework\Rest\Interfaces;

use Onion\Framework\Hydrator\Interfaces\HydratableInterface as Hydratable;

/**
 * @deprecated
 *
 * Interface SerializableInterface
 *
 * @package Onion\Framework\Rest\Interfaces
 */
interface SerializableInterface extends Hydratable
{
    public function getMappings(): array;
}
