<?php

session_start();

require_once __DIR__ . "/includes/db.php";


/*
|--------------------------------------------------------------------------
| AI: Attend Mo - Login Processing
|--------------------------------------------------------------------------
| MySQL-based authentication with role-based redirect
|--------------------------------------------------------------------------
*/


if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: login.php");
    exit;
}



$username = trim(
    $_POST["username"] ?? ""
);

$password = $_POST["password"] ?? "";



if ($username === "" || $password === "") {

    $_SESSION["login_error"] =
        "Please enter your username and password.";

    header("Location: login.php");
    exit;
}



try {


    /*
    |--------------------------------------------------------------------------
    | Find User Account
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare(
        "SELECT 
            user_id,
            full_name,
            username,
            password_hash,
            role,
            account_status
        FROM users
        WHERE username = ?
        LIMIT 1"
    );


    $stmt->execute([
        $username
    ]);


    $user = $stmt->fetch();



    /*
    |--------------------------------------------------------------------------
    | Check Username
    |--------------------------------------------------------------------------
    */

    if (!$user) {

        $_SESSION["login_error"] =
            "Invalid username or password.";

        header("Location: login.php");
        exit;
    }



    /*
    |--------------------------------------------------------------------------
    | Check Account Status
    |--------------------------------------------------------------------------
    */

    if ($user["account_status"] !== "Active") {

        $_SESSION["login_error"] =
            "Your account is inactive.";

        header("Location: login.php");
        exit;
    }



    /*
    |--------------------------------------------------------------------------
    | Verify Password
    |--------------------------------------------------------------------------
    */

    if (!password_verify(
        $password,
        $user["password_hash"]
    )) {

        $_SESSION["login_error"] =
            "Invalid username or password.";

        header("Location: login.php");
        exit;
    }



    /*
    |--------------------------------------------------------------------------
    | Create Session
    |--------------------------------------------------------------------------
    */

    $_SESSION["user_id"] =
        $user["user_id"];


    $_SESSION["full_name"] =
        $user["full_name"];


    $_SESSION["username"] =
        $user["username"];


    $_SESSION["role"] =
        $user["role"];



    /*
    |--------------------------------------------------------------------------
    | Update Last Login
    |--------------------------------------------------------------------------
    */

    $update = $pdo->prepare(
        "UPDATE users
         SET last_login = NOW()
         WHERE user_id = ?"
    );


    $update->execute([
        $user["user_id"]
    ]);



    /*
    |--------------------------------------------------------------------------
    | Redirect Based on User Role
    |--------------------------------------------------------------------------
    */

    if ($user["role"] === "Administrator") {

        header("Location: dashboard.php");

    } elseif ($user["role"] === "Faculty") {

        header("Location: faculty_dashboard.php");

    } elseif ($user["role"] === "Student") {

        header("Location: student_dashboard.php");

    } else {

        $_SESSION["login_error"] =
            "Invalid user role.";

        header("Location: login.php");
    }


    exit;



} catch (PDOException $e) {


    $_SESSION["login_error"] =
        "Database error occurred.";

    header("Location: login.php");
    exit;

}