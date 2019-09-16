<?php
namespace Onion\Framework\Rest\Transformers;

trait Ld
{
    private $transformer;

    private function getTransformer()
    {
        if (!$this->transformer) {
            $this->transformer = new LdTransformer();
        }

        return $this->transformer;
    }
}
