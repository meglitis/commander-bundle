<?php

namespace Guscware\CommanderBundle\Tests\Component;

use Guscware\CommanderBundle\Component\CommanderUtilities;

class CommanderUtilitiesTest extends \PHPUnit_Framework_TestCase
{
    public function testHashedClassName()
    {
        $classNameWithHash = CommanderUtilities::getClassNameWithHash(new HashableTestClass());

        $this->assertSame("HashableTestClass_abc4500", $classNameWithHash);
    }
}


final class HashableTestClass
{
}
