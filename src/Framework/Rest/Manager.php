<?php declare(strict_types = 1);
namespace Onion\Framework\Rest;

use Onion\Framework\Http\Header\Accept;
use Onion\Framework\Hydrator\Interfaces\HydratableInterface as Hydratable;
use Onion\Framework\Rest\Interfaces\SerializerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Manager
{
    /**
     * The transformer object to with hydratable entities
     * @var Transformer
     */
    private $transformer;

    /**
     * A key => value list of namespace => directory
     * to check for available serializers
     *
     * @var SerializerInterface[] List of namespaces
     */
    private $serializers = [];

    public function __construct(Transformer $transformer, array $serializers = [])
    {
        $this->transformer = $transformer;
        $this->serializers = $serializers;
    }

    /**
     * @param Accept                $accept
     * @param SerializerInterface[] $serializers
     *
     * @return SerializerInterface
     * @throws \ErrorException
     */
    private function negotiateSerializer(Accept $accept, array $serializers): SerializerInterface
    {
        foreach ($serializers as $serializer) {
            if ($serializer->supports($accept)) {
                return $serializer;
            }
        }

        throw new \ErrorException(
            'Content negotiation failed. Server cannot generate acceptable response format.'
        );
    }

    public function response(Request $request, Response $response, Hydratable $entity): Response
    {
        $serializer = $this->negotiateSerializer(new Accept($request->getHeaderLine('accept')), $this->serializers);
        $payload = $this->transformer->transform(
            $entity,
            array_map(function ($value) {
                return explode(',', $value);
            }, $request->getQueryParams()['include'] ?? []),
            array_map(function ($value) {
                return explode(',', $value);
            }, $request->getQueryParams()['fields'] ?? [])
        );
        $response->getBody()->write($serializer->serialize($payload));

        return $response->withAddedHeader('Content-type', $serializer->getContentType())
            ->withStatus($payload->isError() ? (int) $payload->getDataItem('code', 400) : 200);
    }
}
