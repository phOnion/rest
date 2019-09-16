<?php declare(strict_types=1);
namespace Onion\Framework\Rest\Responses\Json;

trait Json
{
    protected function encode($data, bool $pretty = false): string
    {
        $options = JSON_UNESCAPED_SLASHES;
        if ($pretty) {
            $options |= JSON_PRETTY_PRINT;
        }

        $data = json_encode($data, $options);
        if (!$data) {
            throw new \RuntimeException(json_last_error_msg());
        }

        return $data;
    }

    protected function decode(string $data): array
    {
        return json_decode($data, true);
    }
}
