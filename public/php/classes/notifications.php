<?php

namespace PPStart\SMS;

use Google\Auth\ApplicationDefaultCredentials;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Exception\RequestException;

class Notification
{
    use Connection;

    protected \PDO $dbh;

    public function __construct()
    {
        $dbh = $this->connect();
        $this->dbh = $dbh;
    }

    // Save device token(used to send notification via Google Firebase)

    public function saveToken(string $userId, string $firebaseToken): void
    {
        if (!($this->dbh instanceof \PDO)) {
            echo json_encode(['error' => 'Error connecting to DB. Please try again later.']);
            return;
        }

        $response = [];

        $stmt = $this->dbh->prepare("SELECT `firebase_token` FROM `users_phone` WHERE `user_id` = :userId");
        $stmt->execute(['userId' => $userId]);
        $oldToken = $stmt->fetch(\PDO::FETCH_COLUMN);
        $stmt->closeCursor();

        if ($oldToken === $firebaseToken) {
            $response['success'] = true;
        } else {
            if ($oldToken !== false) {
                $params = [
                    'userId' => $userId,
                    'firebaseToken' => $firebaseToken
                ];

                $stmt = $this->dbh->prepare("UPDATE `users_phone` SET `firebase_token` = :firebaseToken 
                                            WHERE `user_id` = :userId");
                $stmt->execute($params);
                $stmt->closeCursor();
                $response['success'] = true;
            } else {
                $response['error'] = 'User not found.';
            }
        }

        echo json_encode($response);
    }

    // Send notification

    public function sendNotification(
        string $firebaseToken,
        string $notificationTitle,
        string $notificationBody
    ): void {
        $response = [];
        $projectId = 'pp-start-451410';
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
                'token' => $firebaseToken,
                'data' => [
                    'title' => $notificationTitle,
                    'body' => $notificationBody,
                    'icon' => '/logo192.png'
                ]
            ]
        ];

        // Send request

        try {
            $requestResponse = $client->post(
                "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
                [
                    'json' => $message
                ]
            );

            $requestResponseBody = json_decode($requestResponse->getBody(), true);

            if (isset($requestResponseBody['name'])) {
                $response['success'] = true;
            } else {
                $response['message'] = "Unexpected response while sending notification. Please try again later";
            }
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $errorBody = (string) $e->getResponse()->getBody();
                $errorData = json_decode($errorBody, true);

                if (isset($errorData['error']['message'])) {
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
