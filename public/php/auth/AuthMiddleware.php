<?php

class Auth extends JwtHandler
{
    use PPStart\SMS\Connection;

    protected array $headers;
    protected array $token;

    public function __construct(array $headers)
    {
        parent::__construct();
        $this->headers = $headers;
    }

    public function isValid()
    {

        if (
            array_key_exists('Authorization', $this->headers)
            && preg_match('/Bearer\s(\S+)/', $this->headers['Authorization'], $matches)
            || array_key_exists('authorization', $this->headers)
            && preg_match('/Bearer\s(\S+)/', $this->headers['authorization'], $matches)
        ) {
            $data = $this->jwtDecodeData($matches[1]);

            //var_dump(($data));

            if (isset($data['data']->userId) && $user = $this->fetchUser($data['data']->userId)) {
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

    protected function fetchUser(string $userId): array | null
    {

        try {
            $dbh = $this->connect();

            if (!($dbh instanceof PDO)) {
                return null;
            }

            if (array_key_exists('method', $this->headers)) {
                $method = $this->headers['method'];
            }

            if (array_key_exists('Method', $this->headers)) {
                $method = $this->headers['Method'];
            }

            if (empty($method)) {
                $method = '';
            }

            $stmt = $dbh->prepare("SELECT * FROM `users_$method` WHERE `user_id` = :userId");
            $stmt->execute(['userId' => $userId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!empty($result)) {
                $data = [
                    'userId' => $result['user_id'],
                    'username' => array_key_exists('username', $result) ? $result['username'] : '',
                    'role' => $result['role']
                ];
                return $data;
            } else {
                return null;
            }
        } catch (PDOException) {
            return null;
        }
    }
}
