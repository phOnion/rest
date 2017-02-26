<?php declare(strict_types = 1);
namespace Onion\Framework\Rest;

use Onion\Framework\Http\Header\Accept;
use Onion\Framework\Hydrator\Interfaces\HydratableInterface as Hydratable;
use Onion\Framework\Rest\Interfaces\SerializerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\SimpleCache\CacheInterface;

class Manager
{
    const CACHE_KEY = '__onion_rest_serializers';

    /**
     * The transformer object to with hydratable entities
     * @var Transformer
     */
    private $transformer;

    /**
     * A key => value list of namespace => directory
     * to check for available serializers
     *
     * @var array List of namespaces
     */
    private $namespace = [];

    /**
     * A cache to store the identified
     * @var CacheInterface
     */
    private $cache;

    public function __construct(Transformer $transformer, array $namespaces = [], CacheInterface $cache = null)
    {
        $this->transformer = $transformer;
        $this->namespace = $namespaces;
        $this->cache = $cache;
    }

    private function hasCache(): bool
    {
        return $this->cache !== null;
    }

    private function getCache(): CacheInterface
    {
        return $this->cache;
    }

    public function addNamespace(string $ns, string $directory)
    {
        $this->namespace[$ns] = $directory;
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
        if (!$this->hasCache() || !$this->getCache()->has(self::CACHE_KEY)) {
            $serializers = [];
            foreach ($this->namespace as $ns => $path) {
                $iterator = new \DirectoryIterator($path);
                while ($iterator->valid()) {
                    if (!$iterator->isDot() && !$iterator->isDir()) {
                        $class = $ns . '\\' . substr($iterator->getFilename(), 0, -4);
                        if (class_exists($class) && in_array(SerializerInterface::class, class_implements($class), true)) {
                            /** @var $object SerializerInterface */
                            $serializers[] = new $class;
                        }
                    }

                    $iterator->next();
                }
            }

            if (!$serializers === [] && $this->hasCache()) {
                $this->getCache()->set(self::CACHE_KEY, $serializers, 86400);
            }
        } else {
            $serializers = $this->cache->get(self::CACHE_KEY);
        }

        $serializer = $this->negotiateSerializer(new Accept($request->getHeaderLine('accept')), $serializers);
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
