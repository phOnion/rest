<?php
declare(strict_types=1);

namespace Onion\REST\Transformers\Interfaces;

interface StrategyInterface
{
    public function transform(array $data): string;
}
