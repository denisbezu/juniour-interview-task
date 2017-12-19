<?php
/**
 * Created by PhpStorm.
 * User: denys
 * Date: 19.12.17
 * Time: 13:06
 */

namespace WorkerBundle\Forms\Models;


use WorkerBundle\Entity\Position;

class PositionModel
{
    private $positionName;

    /**
     * @return mixed
     */
    public function getPositionName()
    {
        return $this->positionName;
    }

    /**
     * @param mixed $positionName
     */
    public function setPositionName($positionName)
    {
        $this->positionName = $positionName;
    }

    public function getPositionHandler()
    {
        $position = new Position();
        $position->setPositionName($this->positionName);
        return $position;
    }
}