<?php

namespace Haigha\Persister;

use LinkORB\Component\Database\Database;
use Nelmio\Alice\PersisterInterface;
use RuntimeException;
use PDO;

class PdoPersister implements PersisterInterface
{
    private $pdo;
    private $output;
    private $dryRun;

    /**
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo, $output, $dryRun = false)
    {
        $this->pdo = $pdo;
        $this->output = $output;
        $this->dryRun = $dryRun;
    }

    /**
     * {@inheritdoc}
     */
    public function reset($objects)
    {
        $truncated = array();
        foreach ($objects as $object) {
            $tablename = $object->__meta('tablename');
            if (in_array($tablename, $truncated, true)) {
                continue;
            }
            $truncated[] = $tablename;

            $sql = sprintf("TRUNCATE `%s`", $tablename);

            if ($this->dryRun) {
                $this->output->writeln(sprintf("Will be executed: %s", $sql));
                continue;
            }

            $this->output->writeln(sprintf("Executing: %s", $sql));
            $statement = $this->pdo->prepare($sql);
            $statement->execute();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function persist(array $objects)
    {
        $tasks = array();
        $count = 0;

        // collect all records
        foreach ($objects as $object) {
            $tablename = $object->__meta('tablename');
            $fields = get_object_vars($object);

            if (!isset($tasks[$tablename])) {
                $tasks[$tablename] = array(
                    'fields' => $this->implodeFieldsNames($fields),
                    'rawfields' => $fields,
                    'sql' => array(),
                    'params' => array(),
                );
            }

            $sql = array();
            $params = array();
            // insert known fields in the right order
            foreach ($tasks[$tablename]['rawfields'] as $field => $dummy) {
                if (isset($fields[$field])) {
                    $params[$field.$count] = $fields[$field];
                    $sql[] = ':'.$field.$count;
                } else {
                    $sql[] = 'DEFAULT';
                }
            }

            // add newly found fields for this table if any
            foreach (array_diff_key($fields, $tasks[$tablename]['rawfields']) as $newKey => $value) {
                $params[$newKey.$count] = $value;
                $sql[] = ':' . $newKey.$count;

                // add DEFAULT value for the new fields to the previous records
                foreach ($tasks[$tablename]['sql'] as $index => $dummy) {
                    $tasks[$tablename]['sql'][$index] = substr($tasks[$tablename]['sql'][$index], 0, -1) . ', DEFAULT)';
                }

                // define the new field in the known ones
                $tasks[$tablename]['fields'] .= ', `' . $newKey . '`';
                $tasks[$tablename]['rawfields'][$newKey] = true;
            }
            $count++;

            $tasks[$tablename]['sql'][] = '('.implode(', ', $sql).')';
            $tasks[$tablename]['params'] = array_merge($tasks[$tablename]['params'], $params);
        }

        // insert records
        $this->pdo->beginTransaction();
        try {
            foreach ($tasks as $table => $task) {
                $sql = 'INSERT INTO `' . $table . '` (' . $task['fields'] . ') VALUES ' . implode(",\n", $task['sql']);
                $params = $task['params'];

                if ($this->dryRun) {
                    $this->output->writeln(sprintf(
                        "Will be executed: %s",
                        $this->getExpectedSqlQuery($sql, $params)
                    ));
                    continue;
                }

                $this->output->writeln(sprintf(
                    "Executing: %s",
                    $this->getExpectedSqlQuery($sql, $params)
                ));

                $statement = $this->pdo->prepare($sql);
                $res = $statement->execute($params);

                if (!$res) {
                    $err = $statement->errorInfo();
                    throw new RuntimeException(sprintf(
                        "Error: '%s' on query '%s'",
                        $err[2],
                        $sql
                    ));
                }
            }
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
        $this->pdo->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function find($class, $id)
    {
        throw new RuntimeException('find not implemented');
    }

    /**
     * @param  array $fields
     * @return string
     */
    private function implodeFieldsNames($fields)
    {
        $fields_names = array_keys($fields);
        return "`" . implode("`, `", $fields_names) . "`";
    }

    public function getExpectedSqlQuery($sql, $fields)
    {
        foreach ($fields as $key=>$value) {
            $key = preg_quote($key);
            $sql = preg_replace("/:$key/", $value, $sql);
        }
        return $sql;
    }
}
