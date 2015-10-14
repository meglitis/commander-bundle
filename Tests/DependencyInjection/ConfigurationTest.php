<?php

namespace Guscware\CommanderBundle\Tests\DependencyInjection;

use Guscware\CommanderBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider configCaseProvider
     */
    public function testConfigTree($options, $results)
    {
        $processor     = new Processor();
        $configuration = new Configuration('some/test/path');
        $config        = $processor->processConfiguration($configuration, [$options]);

        $this->assertEquals($results, $config);
    }

    public function configCaseProvider()
    {
        return [
            [['lockfile_directory' => null], ['lockfile_directory' => 'some/test/path', 'auto_unlock_after' => 300]],
            [['lockfile_directory' => 'test'], ['lockfile_directory' => 'test', 'auto_unlock_after' => 300]],
            [['lockfile_directory' => 'test', 'auto_unlock_after' => 200], ['lockfile_directory' => 'test', 'auto_unlock_after' => 200]],
            [['lockfile_directory' => 'test', 'auto_unlock_after' => null], ['lockfile_directory' => 'test', 'auto_unlock_after' => 300]],
        ];
    }
}
