<?php

require __DIR__.'/../classes/connection.php';

require 'JwtHandler.php';

class Auth extends JwtHandler {

    use Connection;

    protected $db;

    protected $headers;
    
    protected $token;

    public function __construct($headers){

        parent::__construct();

        $this->headers = $headers;

    }

    public function isValid(){

        if (array_key_exists('Authorization', $this->headers) && preg_match('/Bearer\s(\S+)/', $this->headers['Authorization'], $matches) || array_key_exists('authorization', $this->headers) && preg_match('/Bearer\s(\S+)/', $this->headers['authorization'], $matches)) {

            $data = $this->jwtDecodeData($matches[1]);

            if(isset($data['data']->user_id) && $user = $this->fetchUser($data['data']->user_id)){

                return [
                    "success" => true,
                    "user" => $user
                ];

            } else {

                return [
                    "success" => false,
                    "message" => "User not found",
                ];

            }
            
        } else {

            return [
                "success" => false,
                "message" => "Token not found in request"
            ];

        }

    }

    protected function fetchUser($user_id){

        try {

            $dbh = $this->connect();

            if(!($dbh instanceof PDO)){

                return null;
                
            }

            if(array_key_exists('method', $this->headers)){

                $method = $this->headers['method'];

            }

            if(array_key_exists('Method', $this->headers)){

                $method = $this->headers['Method'];

            }

            if(empty($method)){

                $method = '';

            }

            $user_id = array('user_id' => $user_id);

            $stmt = $dbh->prepare("SELECT * FROM `users_$method` WHERE `user_id` = :user_id");

            $stmt->execute($user_id);

            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if(!empty($result)){

                unset($result[0]['password']);

                unset($result[0]['one_time_password']);

                unset($result[0]['verification_code']);

                unset($result[0]['otp_time']);

                return $result;

            } else {

                return false;

            }

        } catch (PDOException $e){

            return null;

        }

    }

}