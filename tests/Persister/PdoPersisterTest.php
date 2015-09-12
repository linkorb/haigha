<?php

namespace Haigha\Tests\Persister;

use Haigha\Persister\PdoPersister;

/**
 * Test class for PdoPersister
 *
 * @author Igor Mukhin <igor.mukhin@gmail.com>
 */
class PdoPersisterTest extends \PHPUnit_Framework_TestCase
{
    private $persister;

    public function setUp()
    {
        $pdo = $this->getMockBuilder('PDO')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->persister = new PdoPersister($pdo);
    }

    public function testBuildSql()
    {
        $this->assertEquals("INSERT INTO `a` (`b`, `c`) VALUES (:b, :c)", $this->persister->buildSql('a', array('b'=>1,'c'=>2)));
    }
}
