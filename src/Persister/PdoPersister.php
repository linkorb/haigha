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
        foreach ($objects as $object) {
            $tablename = $object->__meta('tablename');
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
        foreach ($objects as $object) {
            $tablename = $object->__meta('tablename');
            $fields = get_object_vars($object);

            $sql = $this->buildSql($tablename, $fields);

            if ($this->dryRun) {
                $this->output->writeln(sprintf(
                    "Will be executed: %s",
                    $this->getExpectedSqlQuery($sql, $fields)
                ));
                continue;
            }

            $this->output->writeln(sprintf(
                "Executing: %s",
                $this->getExpectedSqlQuery($sql, $fields)
            ));

            $statement = $this->pdo->prepare($sql);
            $res = $statement->execute($fields);

            if (!$res) {
                $err = $statement->errorInfo();
                throw new RuntimeException(sprintf(
                    "Error: '%s' on query '%s'",
                    $err[2],
                    $sql
                ));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function find($class, $id)
    {
        throw new RuntimeException('find not implemented');
    }

    /**
     * @return string
     */
    public function buildSql($tablename, $fields)
    {
        return sprintf(
            "INSERT INTO `%s` (%s) VALUES (%s)",
            $tablename,
            $this->implodeFieldsNames($fields),
            $this->implodeBindNames($fields)
        );
    }

    /**
     * @param  array $fields
     * @return string
     */
    private function implodeFieldsNames($fields)
    {
        $fields_names = array_keys($fields);
        return "`" . implode($fields_names, "`, `") . "`";
    }

    /**
     * @param  array $fields
     * @return string
     */
    private function implodeBindNames($fields)
    {
        $fields_names = array_keys($fields);
        return ":" . implode($fields_names, ", :");
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
