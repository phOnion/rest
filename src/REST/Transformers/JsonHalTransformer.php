<?php
declare(strict_types=1);

namespace Onion\REST\Transformers;

class JsonHalTransformer implements Interfaces\StrategyInterface
{
    public function transform(array $data): string
    {
        if (isset($data['_meta'])) {
            unset($data['_meta']);
        }

        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}
