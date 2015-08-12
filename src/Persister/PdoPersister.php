<?php

namespace Haigha\Persister;

use LinkORB\Component\Database\Database;
use Nelmio\Alice\PersisterInterface;
use RuntimeException;
use PDO;

class PdoPersister implements PersisterInterface
{
    private $pdo;
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    public function persist(array $objects)
    {
        $tables = array();
        
        foreach ($objects as $object) {
            $tables[$object->__meta('tablename')] = true;
        }
        
        foreach ($tables as $tablename => $value) {
            $statement = $this->pdo->prepare("TRUNCATE " . $tablename);
            $statement->execute();
        }
        
        foreach ($objects as $object) {
            $tablename = $object->__meta('tablename');
            $properties = get_object_vars($object);
            $fields = array();
            $values = array();
            $bind = array();
            foreach ($properties as $field => $value) {
                $fields[] = $field;
                $values[] = $value;
                $bind[':' . $field] = $value;
            }
            $sql = "INSERT INTO " . $tablename . " (" . implode($fields, ', ');
            $sql .= ") VALUES (";

            $first = true;
            foreach ($properties as $key => $value) {
                if (!$first) {
                    $sql .= ", ";
                }
                $sql .= ":" . $key . "";
                $first = false;
            }
            $sql .= ");";

            $statement = $this->pdo->prepare($sql);
            $res = $statement->execute($bind);
            if (!$res) {
                $err = $statement->errorInfo();
                $errorMessage = $err[2];
                throw new RuntimeException("Error: [" . $errorMessage . '] on query [' . $sql . ']');
            }
        }
    }
    
    public function find($class, $id)
    {
        throw new RuntimeException('find not implemented');
    }
}
