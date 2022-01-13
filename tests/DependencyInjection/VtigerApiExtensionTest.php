<?php

namespace Gkite13\VtigerApiBundle\Tests\DependencyInjection;

use Gkite13\VtigerApiBundle\DependencyInjection\Gkite13VtigerApiExtension;
use Gkite13\VtigerApiBundle\VtigerApi;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

class VtigerApiExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        return [
            new Gkite13VtigerApiExtension(),
        ];
    }

    protected function getMinimalConfiguration(): array
    {
        return [
            'api' => [
                'site_url' => 'test.test',
                'user' => 'test',
                'access_key' => 'test',
            ],
        ];
    }

    public function testContainerBuildsWithMinimalConfiguration(): void
    {
        $this->load();

        $this->assertContainerBuilderHasService(VtigerApi::class);
    }
}
