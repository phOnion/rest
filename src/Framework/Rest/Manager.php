<?php declare(strict_types=1);
namespace Onion\Framework\Rest;

use Onion\Framework\Http\Header\Interfaces\AcceptInterface as Accept;
use Onion\Framework\Hydrator\Interfaces\HydratableInterface as Hydratable;
use Onion\Framework\Rest\Interfaces\SerializerInterface;
use Onion\Framework\Rest\Interfaces\TransformerInterface as Transformer;
use Psr\Http\Message\ResponseInterface as Response;

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
     *
     * @return SerializerInterface
     * @throws \ErrorException
     */
    private function negotiateSerializer(Accept $accept): SerializerInterface
    {
        foreach ($this->serializers as $serializer) {
            if ($serializer->supports($accept)) {
                return $serializer;
            }
        }

        throw new \ErrorException(
            'Content negotiation failed. Server cannot generate acceptable response format.'
        );
    }

    /**
     * Produces the appropriate response applying transformation on $entity
     * as well as serializing the response in a client-supported way or
     * return the response with appropriate HTTP status code + headers
     * indicating what went wrong (what is supported by the server
     *
     * @param Accept     $accept The current HTTP request to perform content negotiation
     * @param Response   $response The boilerplate of the response to return
     * @param Hydratable $entity The entity to transform and serialize as response body
     * @param array      $filter Assoc array with 'include' and 'fields' keys
     *
     * @return Response The manipulated response
     *
     * @throws \ErrorException if content negotiation is impossible
     */
    public function response(Accept $accept, Response $response, Hydratable $entity, array $filter = []): Response
    {
        try {
            $serializer = $this->negotiateSerializer($accept);

            $payload = $this->transformer->transform(
                $entity,
                array_map(function ($value) {
                    return explode(',', $value);
                }, $filter['include'] ?? []),
                array_map(function ($value) {
                    return explode(',', $value);
                }, $filter['fields'] ?? [])
            );

            $response->getBody()->write($serializer->serialize($payload));

            return $response->withAddedHeader('Content-type', $serializer->getContentType())
                ->withStatus($payload->isError() ? (int) $payload->getDataItem('code', 400) : 200);
        } catch (\ErrorException $ex) {
            $response = $response->withStatus(406);
        }

        return $response->withAddedHeader('Vary', 'Accept');
    }
}
