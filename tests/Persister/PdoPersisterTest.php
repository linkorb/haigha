<?php

namespace Haigha\Tests\Persister;

use Haigha\Persister\PdoPersister;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Test class for PdoPersister
 *
 * @author Igor Mukhin <igor.mukhin@gmail.com>
 */
class PdoPersisterTest extends \PHPUnit_Framework_TestCase
{
    private $persister;
    private $output;

    public function setUp()
    {
        $pdo = $this->getMockBuilder('PDO')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->output = new BufferedOutput();
        $this->persister = new PdoPersister($pdo, $this->output);
    }

    public function testBuildSql()
    {
        $this->assertEquals("INSERT INTO `a` (`b`, `c`) VALUES (:b, :c)", $this->persister->buildSql('a', array('b'=>1,'c'=>2)));
    }
}
