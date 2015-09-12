<?php

namespace Haigha\Persister;

use LinkORB\Component\Database\Database;
use Nelmio\Alice\PersisterInterface;
use RuntimeException;
use PDO;

class PdoPersister implements PersisterInterface
{
    private $pdo;

    /**
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * {@inheritdoc}
     */
    public function reset($objects)
    {
        foreach ($objects as $object) {
            $tablename = $object->__meta('tablename');
            $statement = $this->pdo->prepare(sprintf("TRUNCATE `%s`", $tablename));
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
}
