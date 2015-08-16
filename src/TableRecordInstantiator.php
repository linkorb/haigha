<?php

namespace Haigha;

use Nelmio\Alice\Instances\Instantiator\Methods\MethodInterface;
use Nelmio\Alice\Fixtures\Fixture;
use Haigha\TableRecord;
use Rhumsaa\Uuid\Uuid;

class TableRecordInstantiator implements MethodInterface
{
    private $ids = array();
    private $auto_uuid_column = null;

    /**
     * Use this method to define a specific column to automatically receive a generated UUID.
     */
    public function setAutoUuidColumn($colum_name)
    {
        $this->auto_uuid_column = $colum_name;
    }

    /**
    * {@inheritDoc}
    */
    public function canInstantiate(Fixture $fixture)
    {
        if (substr($fixture->getClass(), 0, 6)=='table.') {
            return true;
        }
    }

    /**
    * {@inheritDoc}
    */
    public function instantiate(Fixture $fixture)
    {
        $tablename = substr($fixture->getClass(), 6);
        $r = new TableRecord($tablename);

        if (!isset($this->ids[$tablename])) {
            $this->ids[$tablename] = 1;
        }
        $r->setId($this->ids[$tablename]);
        $this->ids[$tablename]++;

        if ($this->auto_uuid_column) {
            $uuid = (string)Uuid::uuid4();
            $r->setR_uuid($uuid);
        }

        return $r;
    }
}
