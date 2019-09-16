<?php
namespace Onion\Framework\Rest\Transformers;

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
