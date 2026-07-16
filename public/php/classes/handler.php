<?php

require __DIR__ . '/../vendor/autoload.php';
require 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    $requestType = $data['requestType'];
    $handler = $data['handler'];

    if ($handler === 'login') {
        require 'login.php';
        $form = $data['formFields'];
        $login = new PPStart\SMS\Login();

        switch ($requestType) {
            // METHOD - MAIL
            // Verify login credentials
            case 'mailLogin':
                $usernameEmail = $form['usernameEmail'];
                $password = $form['password'];
                $login->checkCredentials($usernameEmail, $password);
                break;

            // Handle password reset request
            case "initiatePasswordReset":
                $emailRemind = $form['emailRemind'];
                $login->initiatePasswordReset($emailRemind);
                break;

            // Setting new password
            case 'resetPassword':
                $newPassword = $form['reset1'];
                $userId = $form['userId'];
                $auth = $form['auth'];
                $login->resetPassword($userId, $auth, $newPassword);
                break;

            // Check if username is free
            case 'usernameCheck':
                $username = $form['usernameCheck'];
                $check = $login->checkUsername($username);
                $response = ['result' => $check];
                echo json_encode($response);
                break;

            // Register new user
            case 'registerUser':
                $usernameRegister = $form['usernameRegister'];
                $emailRegister = $form['emailRegister'];
                $passwordRegister = $form['passwordRegister1'];
                $policyAcceptation = $form['policyAcceptation'];
                $login->registerUser(
                    $usernameRegister,
                    $emailRegister,
                    $passwordRegister,
                    $policyAcceptation
                );
                break;

            // Resend account activation link
            case 'resendActivation':
                $inputData = $form['reactivationData'];
                $login->resendActivationLink($inputData);
                break;

            // Verification of registered email
            case 'verifyUser':
                $userId = $form['userId'];
                $authReg = $form['authReg'];
                $login->activateRegisteredUser($userId, $authReg);
                break;

            // METHOD - PHONE
            // Creation of one time password
            case 'generateOtp':
                $phoneNumber = $form['phoneNumber'];
                $policyAcceptation = $form['policyAcceptation'];
                $verificationCode = $form['verificationCode'];
                $login->generateOTP($phoneNumber, $policyAcceptation, $verificationCode);
                break;

            // Verification of one time password
            case 'verifyOtp':
                $phoneNumber = $form['phoneNumber'];
                $oneTimePassword = $form['oneTimePassword'];
                $verificationCode = $form['verificationCode'];
                $login->verifyOTP($phoneNumber, $oneTimePassword, $verificationCode);
                break;
        }
    }

    if ($handler === 'notifications') {
        require 'notifications.php';
        $notification = new PPStart\SMS\Notification();

        switch ($requestType) {
            // Save device token
            case 'saveToken':
                $userId = $data['userId'];
                $firebaseToken = $data['firebaseToken'];
                $notification->saveToken($userId, $firebaseToken);
                break;

            // Send notification
            case 'sendNotification':
                $firebaseToken = $data['firebaseToken'];
                $notificationTitle = $data['notificationTitle'];
                $notificationBody = $data['notificationBody'];
                $notification->sendNotification($firebaseToken, $notificationTitle, $notificationBody);
                break;
        }
    }
}
