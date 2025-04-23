<?php

include 'connection.php';

class CreateDB {

    use Connection;

    protected $dbh;

    public function __construct(){

        $dbh = $this->connect();

        $this->dbh = $dbh;

        $this->createDB();
        
    }

    public function createDB(){

        if(!($this->dbh instanceof PDO)){

            return;

        }

        try {

            $sql = file_get_contents('sql/db.sql');
        
            $this->dbh->exec($sql);

            echo "SQL file executed successfully.";

        } catch (PDOException $e){

            echo "Error: " . $e->getMessage();

        }

    }

}

$createDB = new CreateDB();