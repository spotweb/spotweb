<?php

class Dto_TmdbCrew extends Dto_TmdbCredits {
    private $internalCrewId = null;
    private $department = null;
    private $job = null;

    /**
     * @param mixed $department
     */
    public function setDepartment($department) {
        $this->department = $department;
    }

    /**
     * @return mixed
     */
    public function getDepartment() {
        return $this->department;
    }

    /**
     * @param mixed $id
     */
    public function setInternalCrewId($id) {
        $this->internalCrewId = $id;
    }

    /**
     * @return mixed
     */
    public function getInternalCrewId() {
        return $this->internalCrewId;
    }

    /**
     * @param mixed $job
     */
    public function setJob($job) {
        $this->job = $job;
    }

    /**
     * @return mixed
     */
    public function getJob() {
        return $this->job;
    }


}