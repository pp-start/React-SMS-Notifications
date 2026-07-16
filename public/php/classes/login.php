<?php

namespace PPStart\SMS;

class Login
{
    use Connection;

    protected \PDO $dbh;
    protected int $length;
    protected string $token;
    protected string $url;
    protected string $website;
    protected string $siteTitle;
    protected string $appLocation;
    protected string $headers;

    public function __construct()
    {
        $this->dbh = $this->connect(); // Connection with DB
        $this->length = 10; // Auth string partial length
        $this->url = "https://api.smsapi.pl/sms.do"; // External SMS operator API endpoint
        $this->token = 'your_token'; // Access token for external SMS operator
        $this->website = "http://localhost:5173"; // Full URL on which the app is running
        $this->siteTitle = "Localhost"; // Site title
        $this->appLocation = 'your_domain'; // Like aplikacja.pp-start.pl

        $headers = [
            'From' => 'Serwis PP Start <pawelpok81@gmail.com>',
            'Reply-To' => 'pawelpok81@gmail.com',
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/html; charset=utf-8',
            'X-Mailer' => 'PHP/' . phpversion()
        ];

        $headers_string = "";

        foreach ($headers as $key => $value) {
            $headers_string .= "{$key}: {$value}\r\n";
        }

        $this->headers = $headers_string;

        /*

        $this->headers = "Reply-To: Serwis PP Start <serwis@pp-start.pl>\r\n";
        $this->headers .= "Return-Path: Serwis PP Start <serwis@pp-start.pl>\r\n";
        $this->headers .= "From: Serwis PP Start <serwis@pp-start.pl>\r\n";
        $this->headers .= "Organization: Sender Organization\r\n";
        $this->headers .= "MIME-Version: 1.0\r\n";
        $this->headers .= "X-Priority: 3\r\n";
        $this->headers .= "X-Mailer: PHP" . phpversion() . "\r\n" ;
        $this->headers .= "Content-Type: text/html; charset=utf-8\r\n";

        */
    }

    // Check if there is connection to the DB

    public function checkConnection()
    {
        if (($this->dbh instanceof \PDO)) {
            $tables = ['users_mail', 'users_phone'];
            $error = 0;

            foreach ($tables as $table) {
                $stmt = $this->dbh->prepare("SHOW TABLES LIKE :table");
                $stmt->execute([':table' => $table]);

                if ($stmt->rowCount() === 0) {
                    $error++;
                }
            }

            if ($error > 0) {
                $response = ["message" => "Tables not found in Database"];
                echo json_encode($response);
                return false;
            } else {
                return true;
            }
        } else {
            $response = ['message' => 'No connection to DB'];
            echo json_encode($response);
            return false;
        }
    }

    // METHOD - MAIL
    // Remove emoji from string

    private function removeEmoji(string $text): string
    {
        return preg_replace('/[^\x00-\x7F]&&\p{Emoji}|\p{So}/u', '', $text);
    }

    // Validate password strength

    public function validatePasswordStrength(string $password): bool
    {
        return preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $password);
    }

    // Generate authentication hash for password reset link

    public function encryptAuth(string $hash): string
    {
        $random1 = rand(5, 15);
        $random2 = rand(20, 30);
        $random3 = rand(35, 45);
        $length = $this->length;
        $part1 = substr($hash, $random1, $length);
        $part2 = substr($hash, $random2, $length);
        $part3 = substr($hash, $random3, $length);
        $code = $part1 . $part2 . $part3;
        return $code;
    }

    // Verify authentication hash for password reset link

    public function decryptAuth(string $hash, string $auth): bool
    {
        $sub1 = substr($hash, 5, 20);
        $sub2 = substr($hash, 20, 20);
        $sub3 = substr($hash, 35, 20);
        $chunks = str_split($auth, 10);

        if (str_contains($sub1, $chunks[0]) && str_contains($sub2, $chunks[1]) && str_contains($sub3, $chunks[2])) {
            return true;
        } else {
            return false;
        }
    }

    // Verify login credentials

    public function checkCredentials(string $usernameEmail, string $password): void
    {
        if (!$this->checkConnection()) {
            return;
        }

        $response = [];

        if (filter_var($usernameEmail, FILTER_VALIDATE_EMAIL)) {
            $email = ['email' => $usernameEmail];
            $stmt = $this->dbh->prepare("SELECT * FROM `users_mail` WHERE email = :email");
            $stmt->execute($email);
        } else {
            $username = ['username' => $usernameEmail];
            $stmt = $this->dbh->prepare("SELECT * FROM `users_mail` WHERE username = :username");
            $stmt->execute($username);
        }

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (!$result) {
            $response['message'] = "User doesn't exist";
        } else {
            $verified = $result['verified'];

            if (!$verified) {
                $response['message'] = "You need to activate your account before you will be able to sign in.\n 
                                        If you want to resend activation link please click below.";
                $response['resend'] = true;
            } else {
                $hash = $result['password'];

                if (password_verify($password, $hash)) {
                    require __DIR__ . '/../auth/JwtHandler.php';
                    $jwt = new \JwtHandler();
                    $token = $jwt->jwtEncodeData('php_auth_api/', ["userId" => $result['user_id']]);
                    $userId = $result['user_id'];
                    $username = $result['username'];
                    $email = $result['email'];
                    $role = $result['role'];
                    $message = "Redirecting...";
                    $response = [
                        'token' => $token,
                        'username' => $username,
                        'userId' => $userId,
                        'role' => $role,
                        'email' => $email,
                        'message' => $message
                    ];
                } else {
                    $response['message'] = 'Password is incorrect';
                }
            }
        }

        echo json_encode($response);
    }

    // Handle password reset request

    public function initiatePasswordReset(string $emailRemind): void
    {
        if (!$this->checkConnection()) {
            return;
        }

        if (!filter_var($emailRemind, FILTER_VALIDATE_EMAIL)) {
            $response = ['message' => 'Invalid email address entered.'];
            echo json_encode($response);
            return;
        }

        $email = ['email' => $emailRemind];
        $stmt = $this->dbh->prepare("SELECT * FROM `users_mail` WHERE email = :email");
        $stmt->execute($email);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (!$result) {
            $response = ['message' => 'Email address is not registered.'];
            echo json_encode($response);
        } else {
            $verified = $result['verified'];

            if (!$verified) {
                $response = ['message' => "You need to activate your account before you will be able 
                    to change password.\n If you want to resend activation link please click below.", 'resend' => true];
                echo json_encode($response);
            } else {
                $userId = $result['user_id'];
                $email = $result['email'];
                $username = $result['username'];
                $hash = $result['password'];
                $auth = $this->encryptAuth($hash);
                $this->sendPasswordResetMessage($email, $username, $auth, $userId);
            }
        }
    }

    // Send mail with password reset link

    public function sendPasswordResetMessage(string $email, string $username, string $auth, string $userId): void
    {
        $response = [];

        $emailSubject = "Restore access to " . $this->siteTitle;

        $website = $this->website;
        $siteTitle = $this->siteTitle;

        ob_start();
        include 'templates/reset_password.php';
        $emailMessage = ob_get_clean();

        // Sending message

        $sendMail = mail($email, $emailSubject, $emailMessage, $this->headers);

        if ($sendMail === true) {
            $response['success'] = true;
            $response['message'] = 'A message has been sent to your inbox. 
                                    Use it to set new password for your account.';
        } else {
            $response['message'] = 'There was an error when sending message. Please try again later.';
        }

        echo json_encode($response);
    }

    // Set new password

    public function resetPassword(string $userId, string $auth, string $newPassword): void
    {
        if (!$this->checkConnection()) {
            return;
        }

        $userId = ['userId' => $userId];
        $stmt = $this->dbh->prepare("SELECT * FROM `users_mail` WHERE `user_id` = :userId");
        $stmt->execute($userId);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (!empty($result)) {
            $hash = $result['password'];

            if ($this->decryptAuth($hash, $auth)) {
                if ($this->validatePasswordStrength($newPassword)) {
                    $password = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $this->dbh->prepare("UPDATE `users_mail` SET `password` = '$password' 
                                                WHERE `user_id` = :userId");
                    $stmt->execute($userId);
                    $response['success'] = true;
                    $response['redirect'] = true;
                    $response['message'] = 'Password was changed. You can now log in.';
                } else {
                    $response['message'] = 'Password must have at least 8 characters, one capital letter, 
                                            one digit and a special character.';
                }
            } else {
                $response['redirect'] = true;
                $response['message'] = 'This link is not longer active. If you still 
                                        need to change password please request it again.';
            }
        } else {
            $response['message'] = "Can't change password. User doesn't exist.";
        }

        echo json_encode($response);
    }

    // Check if username is free

    public function checkUsername(string $username): bool | null
    {
        if (!$this->checkConnection()) {
            return null;
        }

        $username = ['username' => $username];
        $stmt = $this->dbh->prepare("SELECT * FROM `users_mail` WHERE `username` = :username");
        $stmt->execute($username);
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (empty($result)) {
            return true;
        } else {
            return false;
        }
    }

    // Register new user

    public function registerUser(
        string $registerUsername,
        string $registerEmail,
        string $registerPassword,
        bool $policyAcceptation
    ): void {
        if (!$this->checkConnection()) {
            return;
        }

        $error = "";
        $response = [];
        $username = trim($this->removeEmoji($registerUsername));
        $usernameLength = strlen($username);

        if ($usernameLength === 0) {
            $error .= "Username can't be empty.\n";
        } elseif ($usernameLength < 4) {
            $error .= "Username must have at least 4 characters.\n";
        }

        if (!filter_var($registerEmail, FILTER_VALIDATE_EMAIL)) {
            $error .= "Invalid email address entered.\n";
        }

        if (!$this->validatePasswordStrength($registerPassword)) {
            $error .= "Password must have at least 8 characters, one capital letter, 
                        one digit and a special character.\n";
        }

        if ($policyAcceptation !== true) {
            $error .= "You need to accept data processing and privacy policy.\n";
        }

        if (!empty($error)) {
            $response['message'] = $error;
            echo json_encode($response);
        } else {
            $usernameCheck = $this->checkUsername($username);

            if (!$usernameCheck) {
                $response['message'] = "Username already exists. Please choose a different username.";
                echo json_encode($response);
                return;
            }

            $email = ['email' => $registerEmail];
            $stmt = $this->dbh->prepare("SELECT * FROM `users_mail` WHERE `email` = :email");
            $stmt->execute($email);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if (!empty($result)) {
                $response['message'] = "Email is already registered. If you don't remember your password please 
                                        reset it by clicking 'Forgot password' button on sign-in screen.";
                echo json_encode($response);
                return;
            }

            $userId = $this->getUserId('users_mail');
            $password = password_hash($registerPassword, PASSWORD_DEFAULT);

            $params = [
                'user_id' => $userId,
                'username' => $username,
                'email' => $registerEmail,
                'password' => $password,
                'role' => 'user',
                'verified' => 0
            ];

            $columns = implode(", ", array_keys($params));
            $placeholders = ":" . implode(", :", array_keys($params));
            $stmt = $this->dbh->prepare("INSERT INTO `users_mail` ($columns) VALUES ($placeholders)");
            $stmt->execute($params);

            if ($stmt->rowCount() !== 1) {
                $response['message'] = 'Database connection error. Please try again later.';
            } else {
                $response['success'] = true;
                $response['message'] = 'Registration was successful. Before you will be able to log in you need to 
                                    activate account by clicking activation link in the message sent to your inbox.';

                $data = [
                    'userId' => $userId,
                    'username' => $username,
                    'email' => $registerEmail,
                    'password' => $password
                ];
                $this->sendActivationMessage($data);
            }

            echo json_encode($response);
        }
    }

    // Resend account activation link

    public function resendActivationLink(array $data): void
    {
        if (!$this->checkConnection()) {
            return;
        }

        $response = [];

        if (array_key_exists('userId', $data)) {
            $stmt = $this->dbh->prepare("SELECT * FROM `users_mail` WHERE user_id = :userId");
            $stmt->execute(['userId' => $data['userId']]);
        } elseif (array_key_exists('usernameEmail', $data)) {
            if (filter_var($data['usernameEmail'], FILTER_VALIDATE_EMAIL)) {
                $email = ['email' => $data['usernameEmail']];
                $stmt = $this->dbh->prepare("SELECT * FROM `users_mail` WHERE email = :email");
                $stmt->execute($email);
            } else {
                $username = ['username' => $data['usernameEmail']];
                $stmt = $this->dbh->prepare("SELECT * FROM `users_mail` WHERE username = :username");
                $stmt->execute($username);
            }
        } else {
            $response['message'] = 'Activation message has been re-send to your email.';
            echo json_encode($response);
            return;
        }

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (!empty($result)) {
            $verified = $result['verified'];

            if ($verified) {
                $response['message'] = 'Account has already been activated.';
            } else {
                $data = [
                    'userId' => $result['user_id'],
                    'username' => $result['username'],
                    'email' => $result['email'],
                    'password' => $result['password']
                ];

                $this->sendActivationMessage($data);
                $response['success'] = true;
                $response['message'] = 'Activation message has been re-send to your email.';
            }
        } else {
            $response['message'] = "Email address is not registered.";
        }

        echo json_encode($response);
    }

    // Send message with activation link

    public function sendActivationMessage(array $data): void
    {
        $userId = $data['userId'];
        $username = $data['username'];
        $email = $data['email'];
        $passwordHash = $data['password'];
        $authReg = $this->encryptAuth($passwordHash);
        $website = $this->website;
        $siteTitle = $this->siteTitle;
        $emailSubject = "Activate your account - " . $siteTitle;

        ob_start();
        include 'templates/activate_account.php';
        $emailMessage = ob_get_clean();

        // Sending message

        mail($email, $emailSubject, $emailMessage, $this->headers);
    }

    // Verification of registered email

    public function activateRegisteredUser(string $activateUserId, string $authReg): void
    {
        if (!$this->checkConnection()) {
            return;
        }

        $response = [];
        $userId = ['userId' => $activateUserId];
        $stmt = $this->dbh->prepare("SELECT * FROM `users_mail` WHERE user_id = :userId");
        $stmt->execute($userId);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (!empty($result)) {
            $verified = $result['verified'];

            if (!$verified) {
                $hash = $result['password'];

                if ($this->decryptAuth($hash, $authReg)) {
                    $stmt = $this->dbh->prepare("UPDATE `users_mail` SET verified = 1 WHERE user_id = :userId");
                    $stmt->execute($userId);

                    if ($stmt->rowCount() === 1) {
                        $response['success'] = true;
                        $response['message'] = "Your account has been activated. You can now log in.";
                    } else {
                        $response['message'] = "Database update problem. Please try again later.";
                    }
                } else {
                    $response['message'] = "Invalid activation link. You can get new link by clicking below:";
                    $response['resend'] = true;
                }
            } else {
                $response['message'] = "Your account has been activated before.";
            }
        } else {
            $response['message'] = "Can't activate, user doesn't exist.";
        }

        echo json_encode($response);
    }

    // METHOD - PHONE

    // Login with phone number

    public function generateOTP(string $phoneNumber, bool $policyAcceptation, string $verificationCode): void
    {
        if (!$this->checkConnection()) {
            return;
        }

        $response = [];
        $error = "";

        if (!ctype_digit($phoneNumber)) {
            $error .= "Invalid phone number format.\n";
        }

        if ($policyAcceptation !== true) {
            $error .= "You need to accept data processing and privacy policy.\n";
        }

        if (!$verificationCode) {
            $error .= "Your request is corrupted. Please refresh page and try again.\n";
        }

        if (!empty($error)) {
            $response['message'] = $error;
            echo json_encode($response);
        } else {
            // Checking if phone number exists in DB
            $phone = ['phoneNumber' => $phoneNumber];
            $stmt = $this->dbh->prepare("SELECT * FROM `users_phone` WHERE `phone_number` = :phoneNumber");
            $stmt->execute($phone);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            // Creating new user if phone number was not found
            if (empty($result)) {
                $userId = $this->getUserId('users_phone');

                $stmt = $this->dbh->prepare("INSERT INTO `users_phone` (user_id, phone_number, `role`) 
                                                    VALUES (:userId, :phoneNumber, :userRole)");
                $params = [
                    'userId' => $userId,
                    'phoneNumber' => $phoneNumber,
                    'userRole' => 'user'
                ];
                $stmt->execute($params);
                $stmt->closeCursor();
            } else {
                $userId = $result['user_id'];
                $currentTime = date('Y-m-d H:i:s');
                $prevOtpTime = $result['otp_time'];

                if (!empty($prevOtpTime)) {
                    $date1 = new \DateTime($currentTime);
                    $date2 = new \DateTime($prevOtpTime);
                    $seconds = $date1->getTimestamp() - $date2->getTimestamp();

                    if ($seconds < 120) {
                        $response['time'] = $prevOtpTime;
                        $response['message'] = 'New verification SMS can be only send after 
                                                2 minutes has passed since previous request.';
                        echo json_encode($response);
                        return;
                    }
                }
            }

            // Generating one time password

            $oneTimePassword = $this->generateCode();
            $otpTime = date('Y-m-d H:i:s');
            $stmt = $this->dbh->prepare("UPDATE `users_phone` SET `verification_code` = :verificationCode, 
                                            `one_time_password` = :oneTimePassword, `otp_time` = :otpTime 
                                            WHERE `user_id` = :userId AND `phone_number` = :phoneNumber");
            $params = [
                'verificationCode' => $verificationCode,
                'oneTimePassword' => $oneTimePassword,
                'otpTime' => $otpTime,
                'userId' => $userId,
                'phoneNumber' => $phoneNumber
            ];

            $stmt->execute($params);
            $stmt->closeCursor();

            if ($stmt->rowCount() !== 1) {
                $response['message'] = 'Database connection error. Please try again later.';
                echo json_encode($response);
                return;
            }

            /*

            // Uncomment if using smsapi.pl provider and want to test live SMS sending

            $message = 'Your verification code: ' . $oneTimePassword . '. @' . $this->appLocation .
                                                ' #' . $oneTimePassword . ' ' . $verificationCode;

            $data = [
                'to' => $phoneNumber,
                'message' => $message,
                'from' => 'PP-Start',
                'format' => 'json',
                'encoding' => 'utf-8',
                'access_token' => $this->token
            ];

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $this->url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $result = curl_exec($ch);

            */

            // Simulating correct result(SMS sent, no errors)

            $result = '{"count":1,
                        "list":
                            [
                                {
                                    "id":"67A4747337303292412C498D",
                                    "points":0.17,
                                    "number":"48662754739",
                                    "date_sent":1738830963,
                                    "submitted_number":"+48662754739",
                                    "status":"QUEUE",
                                    "error":null,
                                    "idx":null,
                                    "parts":1
                                }
                            ]
                        }';

            $output = json_decode($result, true);

            if (array_key_exists('error', $output)) {
                $response['message'] = 'Unable to send verification message. Please try again later.';
            } else {
                $response['success'] = true;
            }

            echo json_encode($response);
        }
    }

    // Generate One Time Password

    public function generateCode(): string
    {
        $code = '';

        for ($i = 1; $i <= 6; $i++) {
            $code .= strval(mt_rand(0, 9));
        }

        return $code;
    }

    // Verify One Time Password

    public function verifyOTP(string $phoneNumber, string $oneTimePassword, string $verificationCode): void
    {
        if (!$this->checkConnection()) {
            return;
        }

        $response = [];

        // Getting data from DB

        $stmt = $this->dbh->prepare("SELECT * FROM `users_phone` WHERE `phone_number` = :phoneNumber");
        $stmt->execute(['phoneNumber' => $phoneNumber]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (empty($result)) {
            $response['message'] = 'Incorrect phone number. Please log again.';
            $response['return'] = true;
            echo json_encode($response);
            return;
        }

        // Checking password data

        $userId = $result['user_id'];
        $prevOneTimePassword = $result['one_time_password'];
        $prevVerificationCode = $result['verification_code'];
        $prevOtpTime = $result['otp_time'];

        if (empty($prevOneTimePassword) || empty($prevVerificationCode) || empty($prevOtpTime)) {
            $response['message'] = 'Error reading data. Please log again.';
            $response['return'] = true;
            echo json_encode($response);
            return;
        }

        // Checking if password matches:

        if ($prevOneTimePassword !== $oneTimePassword) {
            $response['message'] = 'Password is incorrect. Please try again.';
            echo json_encode($response);
            return;
        }

        // Checking time difference(300 seconds). You can set your own password expiration time modifying the 300 number

        $currentTime = date('Y-m-d H:i:s');
        $date1 = new \DateTime($currentTime);
        $date2 = new \DateTime($prevOtpTime);
        $seconds = $date1->getTimestamp() - $date2->getTimestamp();

        if ($seconds > 300) {
            $response['message'] = 'Password expired. Please log again.';
            $response['return'] = true;
            echo json_encode($response);
            return;
        }

        // Checking verification code(invisible for user)

        if ($prevVerificationCode !== $verificationCode) {
            $response['message'] = 'Invalid verification code. Please log again.';
            $response['return'] = true;
            echo json_encode($response);
            return;
        }

        require __DIR__ . '/../auth/JwtHandler.php';
        $jwt = new \JwtHandler();

        $token = $jwt->jwtEncodeData('php_auth_api/', ["userId" => $userId]);

        $response = [
            'token' => $token,
            'userId' => $userId,
            'phoneNumber' => $phoneNumber,
            'role' => 'user',
            'success' => true
        ];

        echo json_encode($response);
    }

    // HELPERS

    private function getUserId(string $table): string
    {
        $stmt = $this->dbh->prepare("SELECT MAX(CAST(SUBSTRING(user_id, 2) AS UNSIGNED)) AS max_user_id 
                                            FROM $table WHERE user_id != '-'");
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $maxId = $result['max_user_id'];
        $userId = 'U' . str_pad($maxId + 1, 3, '0', STR_PAD_LEFT);

        return $userId;
    }
}
