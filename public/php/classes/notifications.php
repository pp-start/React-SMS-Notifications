<?php

require 'connection.php';

require __DIR__.'/../vendor/autoload.php';

use Google\Auth\ApplicationDefaultCredentials;

use GuzzleHttp\Client;

use GuzzleHttp\HandlerStack;

class Notification {

    use Connection;

    protected $dbh;

    public function __construct(){

        $dbh = $this->connect();

        $this->dbh = $dbh;

    }

    // Save device token(used to send notification via Google Firebase)

    public function saveToken($user_id, $firebase_token){

        if(!($this->dbh instanceof PDO)){

            return;

        }

        $response = array();

        $params = array(
            'user_id' => $user_id,
            'firebase_token' => $firebase_token
        );

        $stmt = $this->dbh->prepare("UPDATE `users_phone` SET `firebase_token` = :firebase_token WHERE `user_id` = :user_id");

        $stmt->execute($params);

        $stmt->closeCursor();

        if($stmt->rowCount() !== 1){

            $stmt = $this->dbh->prepare("SELECT `firebase_token` FROM `users_phone` WHERE `user_id` = :user_id");

            $stmt->bindParam(':user_id', $user_id);

            $stmt->execute();

            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $stmt->closeCursor();

            if(!empty($result)){

                $old_token = $result[0]['firebase_token'];

                if($old_token === $firebase_token){

                    $response['success'] = true;

                } else {

                    $response['error'] = 'Error connecting to DB. Please try again later.'; 

                }

            }

        } else {

            $response['success'] = true;

        }

        echo json_encode($response);

    }

    // Send notification

    public function sendNotification($firebase_token, $notification_title, $notification_body){

        $response = array();

        $project_id = 'pp-start-451410';

        putenv('GOOGLE_APPLICATION_CREDENTIALS=config/pp-start-451410.json');

        // Authenticate with Google OAuth 2.0

        $auth = ApplicationDefaultCredentials::getMiddleware([
            "https://www.googleapis.com/auth/firebase.messaging"
        ]);

        $stack = HandlerStack::create();

        $stack->push($auth);

        $client = new Client([
            'handler' => $stack,
            'auth' => 'google_auth'
        ]);

        // Notification payload

        $message = [
            'message' => [
                'token' => $firebase_token,
                'data' => [
                    'title' => $notification_title,
                    'body' => $notification_body,
                    'icon' => '/logo192.png'
                ]
            ]
        ];

        // Send request

        try {

            $request_response = $client->post(
                "https://fcm.googleapis.com/v1/projects/{$project_id}/messages:send",
                [
                    'json' => $message
                ]
            );
        
            $request_response_body = json_decode($request_response->getBody(), true);
        
            if(isset($request_response_body['name'])){

                $response['success'] = true;

            } else {

                $response['message'] = "Unexpected response while sending notification. Please try again later";

            }
        
        } catch (RequestException $e) {

            if($e->hasResponse()){

                $errorBody = (string) $e->getResponse()->getBody();

                $errorData = json_decode($errorBody, true);
        
                if(isset($errorData['error']['message'])){

                    $response['message'] = "Error: " . $errorData['error']['message'];

                } else {

                    $response['message'] = "Unknown error: " . $errorBody;

                }

            } else {

                $response['message'] = "Request error: " . $e->getMessage();

            }

        }

        echo json_encode($response);

    }

}

if($_SERVER['REQUEST_METHOD'] === "POST"){

    $data = json_decode(file_get_contents("php://input"), true);

    $request_type = $data['request_type'];

    $notification = new Notification();

    switch ($request_type){

        // Save device token

        case 'save token':

            $user_id = $data['user_id'];

            $firebase_token = $data['firebase_token'];

            $notification->saveToken($user_id, $firebase_token);

            break;

        // Send notification

        case 'send notification':

            $firebase_token = $data['firebase_token'];

            $notification_title = $data['notification_title'];

            $notification_body = $data['notification_body'];

            $notification->sendNotification($firebase_token, $notification_title, $notification_body);

            break;

    }

}