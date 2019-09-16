<?php
namespace Onion\Framework\Rest\Transformers\Traits;

use Onion\Framework\Rest\Transformers\HalTransformer;

trait Hal
{
    private $transformer;

    private function getTransformer()
    {
        if (!$this->transformer) {
            $this->transformer = new HalTransformer();
        }

        return $this->transformer;
    }
}
