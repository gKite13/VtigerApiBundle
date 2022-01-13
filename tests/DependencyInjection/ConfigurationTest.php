<?php

namespace Gkite13\VtigerApiBundle\Tests\DependencyInjection;

use Gkite13\VtigerApiBundle\DependencyInjection\Configuration;
use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    protected function getConfiguration():Configuration
    {
        return new Configuration();
    }

    public function testMinimalApiConfiguration(): void
    {
        $this->assertConfigurationIsValid(
            [[
                'api' => [
                    'site_url' => 'test.test',
                    'user' => 'test',
                    'access_key' => 'test',
                ]
            ]]
        );
    }
}
