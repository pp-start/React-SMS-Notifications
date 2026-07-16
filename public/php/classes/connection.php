<?php

namespace PPStart\SMS;

trait Connection
{
    public function connect()
    {
        $config = parse_ini_file(__DIR__ . "/config/config.ini");
        $host = $config['host'];
        $user = $config['user'];
        $pass = $config['pass'];
        $db = $config['db'];

        try {
            $dsn = 'mysql:host=' . $host . ';dbname=' . $db . ';charset=utf8mb4';
            $dbh = new \PDO($dsn, $user, $pass, array(
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
            ));
            return $dbh;
        } catch (\PDOException $e) {
            return false;
        }
    }
}
