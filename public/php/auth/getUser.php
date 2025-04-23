<?php

require __DIR__.'/AuthMiddleware.php';

if($_SERVER['REQUEST_METHOD'] === 'GET'){

    $allHeaders = getallheaders();

    $auth = new Auth($allHeaders);

    echo json_encode($auth->isValid());

}