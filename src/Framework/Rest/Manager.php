<?php declare(strict_types = 1);
namespace Onion\Framework\Rest;

use Onion\Framework\Http\Header\Accept;
use Onion\Framework\Rest\Interfaces\SerializerInterface;
use Psr\Http\Message\ResponseInterface;

class Manager
{
    private $namespace = [];

    public function addNamespace(string $ns, string $directory)
    {
        $this->namespace[$ns] = $directory;
    }

    public function buildResponse(Accept $accept, ResponseInterface $response, Entity $entity): ResponseInterface
    {
        foreach ($this->namespace as $ns => $path) {
            $iterator = new \DirectoryIterator($path);
            while ($iterator->valid()) {
                if ($iterator->isDot() || $iterator->isDir()) {
                    continue;
                }

                $class = $ns . '\\' . substr($iterator->getFilename(), 0, -4);
                if (class_exists($class)) {
                    $object = new $class;
                    if ($object instanceof SerializerInterface) {
                        if ($object->supports($accept)) {
                            $response->getBody()->write(
                                $object->serialize($entity)
                            );
                            $response->withAddedHeader('Content-Type', $object->getContentType());

                            return $response;
                        }
                    }
                }

                $iterator->next();
            }
        }

        throw new \ErrorException(
            'Content negotiation failed. Server cannot generate acceptable response format.'
        );
    }
}
