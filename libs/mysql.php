<?php
class ZeroDB
{
    private $mysql = null;
    private $host;
    private $port;
    private $dbname;
    private $user;
    private $pass;

    public function __construct($host, $port, $dbname, $user, $pass)
    {
        $this->host   = $host;
        $this->port   = $port;
        $this->dbname = $dbname;
        $this->user   = $user;
        $this->pass   = $pass;
    }

    public function getConnection()
    {
        if ($this->mysql === null) {
            $this->connect();
        }
        $this->check();
        return $this->mysql;
    }

    public function connect()
    {
        $this->mysql = new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s', $this->host, $this->port, $this->dbname), $this->user, $this->pass);
        $this->mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->mysql->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        try {
            $this->mysql->query('SELECT 1');
        } catch (Exception $e) {
            $this->mysql = null;
        }
        return $this->mysql !== null;
    }

    private function check()
    {
        try {
            $this->mysql->query('SELECT 1');
        } catch (Exception $e) {
            $this->connect();
        }
    }
}
