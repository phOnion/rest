<?php declare(strict_types=1);
namespace Onion\Framework\Rest\Response;

trait JsonResponse
{
    protected function encode(array $data, $pretty = false): string
    {
        $options = JSON_UNESCAPED_SLASHES;
        if ($pretty) {
            $options |= JSON_PRETTY_PRINT;
        }

        $data =  json_encode($data, $options);
        if (!$data) {
            throw new \RuntimeException(json_last_error_msg());
        }
    }

    protected function decode(string $data): array
    {
        return json_decode($data, true);
    }
}
