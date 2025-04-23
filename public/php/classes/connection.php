<?php

trait Connection{

    public function connect(){

        $config = parse_ini_file(__DIR__."/config/config.ini");

        $host = $config['host'];
        
        $user = $config['user'];

        $pass = $config['pass'];

        $db = $config['db'];

        try {
            
            $dbh = new PDO('mysql:host=' . $host . ';dbname=' . $db, $user, $pass,array(
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ));

            return $dbh;

        } catch (PDOException $e) {

            return false;

        }
        
    }
    
}