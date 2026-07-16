<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../classes/connection.php';
require __DIR__ . '/JwtHandler.php';
require __DIR__ . '/AuthMiddleware.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $allHeaders = getallheaders();

    $auth = new Auth($allHeaders);

    echo json_encode($auth->isValid());
}
