<?php declare(strict_types=1);
namespace Onion\REST\Traits;

use \Onion\Framework\Http\Header\Accept;
use \Onion\Framework\Hydrator\Interfaces\HydratableInterface;
use \Onion\REST\Serializer;
use \Onion\REST\TransformerStrategyContainer as Strategies;
use \Interop\Http\ServerMiddleware\MiddlewareInterface as Middleware;
use \Psr\Http\Message\ResponseInterface;
use \Psr\Http\Message\RequestInterface;
use \Zend\Diactoros\CallbackStream;

abstract class RestController implements Middleware
{
    private $serializer;
    private $strategies;

    public function __construct(Serializer $serializer, Strategies $strategies)
    {
        $this->serializer = $serializer;
        $this->strategies = $strategies;
    }

    /**
     * A helper method removing the complexity from the
     * controller methods by containing it in the trait
     *
     * @param RequestInterface    $request The current request (Needed for content negotiation
     * @param HydratableInterface $data    Object to convert to a valid response
     * @param int                 $status  The HTTP status code
     * @param array               $headers Headers to add to the response
     */
    public function response(
        RequestInterface $request,
        HydratableInterface $data,
        int $status = 200,
        array $headers = []
    ): ResponseInterface {
        $strategy = null;

        if ($request->hasHeader('accept')) {
            $header = new Accept($request->getHeaderLine('accept'));

            $strategy = $this->strategy->negotiateTransformer($header);
        }

        if ($strategy === null) {
            $strategy = $this->strategies->getDefaultStrategy();
        }

        $data = $this->serializer->serialize($data);

        return new Response(
            new CallbackStream(
                function () use ($strategy, $data) {
                    return $strategy->format($data);
                }
            ),
            $status,
            $headers
        );
    }
}
