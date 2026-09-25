<?php

require_once __DIR__ . "/config.php";
/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

try {

    $dsn =
        "mysql:host="
        . DB_HOST
        . ";port="
        . DB_PORT
        . ";dbname="
        . DB_NAME
        . ";charset=utf8mb4";


    $pdo =
        new PDO(
            $dsn,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE =>
                    PDO::ERRMODE_EXCEPTION,

                PDO::ATTR_DEFAULT_FETCH_MODE =>
                    PDO::FETCH_ASSOC,

                PDO::ATTR_EMULATE_PREPARES =>
                    false
            ]
        );


} catch (PDOException $exception) {

    http_response_code(500);

    exit(
        "Database connection failed."
    );
}