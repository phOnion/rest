<?php
declare(strict_types=1);

namespace Onion\REST\Transformers;

class JsonTransformer implements Interfaces\StrategyInterface
{
    public function transform(array $data): string
    {
        if (isset($data['_links'])) {
            unset($data['_links']);
        }

        if (isset($data['_embedded'])) {
            unset($data['_embedded']);
        }

        if (isset($data['_meta'])) {
            unset($data['_meta']);
        }

        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
