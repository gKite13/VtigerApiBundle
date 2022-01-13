<?php

namespace Gkite13\VtigerApiBundle;

use Gkite13\VtigerApiBundle\DependencyInjection\Gkite13VtigerApiExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class Gkite13VtigerBundle extends Bundle
{
    public function getContainerExtension()
    {
        if (null === $this->extension) {
            $this->extension = new Gkite13VtigerApiExtension();
        }

        return $this->extension;
    }
}
