<?php

class DB
{
    private $dbPath;

    function __construct($dbPath)
    {
        $this->dbPath = $dbPath;
    }

    public function getDBPath()
    {
        return $this->dbPath;
    }

    public function setDBPath($dbPath)
    {
        $this->dbPath = $dbPath;
    }

    public function write($obj)
    {
        $fp = fopen($this->dbPath, 'w');
        if (false == $fp) {
            return false;
        }
        //Lock to prevent parallel writes/reads
        if (flock($fp, LOCK_EX)) {
            fwrite($fp, json_encode($obj));
            flock($fp, LOCK_UN);
            fclose($fp);
            clearstatcache();
        } else {
            error_log("Unable to obtain lock on db!");
            return false;
        }
        return true;
    }

    public function read()
    {
        if (!file_exists($this->dbPath) || 0 == filesize($this->dbPath)) {
            return false;
        }
        $fp = fopen($this->dbPath, 'r');
        if (false == $fp) {
            return false;
        }
        //Lock to prevent parallel writes/reads
        if (flock($fp, LOCK_EX)) {
            $buffer = fread($fp, filesize($this->dbPath));
            $response = json_decode($buffer, true);
            flock($fp, LOCK_UN);
            fclose($fp);
            clearstatcache();
            return $response;
        } else {
            error_log("Unable to obtain lock on db!");
            return false;
        }
    }
}
