<?php

namespace Haigha\Tests\Persister;

use Haigha\Persister\PdoPersister;
use Haigha\TableRecord;
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
        $this->persister = new PdoPersister($pdo, $this->output, true);
    }

    public function testPersist()
    {
        $records = array(
            $this->makeRecord('table1', array('a' => 'foo')),
            $this->makeRecord('table1', array('b' => 'bar')),
            $this->makeRecord('table1', array('b' => 'baz', 'a' => 'qux')),
            $this->makeRecord('table2', array('x' => 'y')),
        );

        $this->persister->persist($records);
        $expected = "Will be executed: INSERT INTO `table1` (`a`, `b`) VALUES (foo, DEFAULT),\n".
            "(DEFAULT, bar),\n".
            "(qux, baz)\n".
            "Will be executed: INSERT INTO `table2` (`x`) VALUES (y)\n";

        $this->assertEquals($expected, $this->output->fetch());
    }

    public function testReset()
    {
        $records = array(
            $this->makeRecord('table1', array('a' => 'foo')),
            $this->makeRecord('table1', array('b' => 'bar')),
            $this->makeRecord('table2', array('x' => 'y')),
        );

        $this->persister->reset($records);
        $expected = "Will be executed: TRUNCATE `table1`\n".
            "Will be executed: TRUNCATE `table2`\n";

        $this->assertEquals($expected, $this->output->fetch());
    }

    private function makeRecord($table, $fields)
    {
        $record = new TableRecord($table);
        foreach ($fields as $field => $val) {
            $record->{$field} = $val;
        }

        return $record;
    }
}
