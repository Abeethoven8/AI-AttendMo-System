<?php

require_once __DIR__
    . "/includes/db.php";


$message = "";
$messageType = "";


/*
|--------------------------------------------------------------------------
| LOCAL SETUP ONLY
|--------------------------------------------------------------------------
*/

$remoteAddress =
    $_SERVER["REMOTE_ADDR"]
    ?? "";


if (
    $remoteAddress !== "127.0.0.1"
    &&
    $remoteAddress !== "::1"
) {

    http_response_code(403);

    exit(
        "Administrator setup is available only on the local computer."
    );
}


/*
|--------------------------------------------------------------------------
| CHECK EXISTING ADMINISTRATOR
|--------------------------------------------------------------------------
*/

$checkAdmin =
    $pdo->prepare(
        "
        SELECT COUNT(*)
        FROM users
        WHERE role = ?
        "
    );


$checkAdmin->execute([
    "Administrator"
]);


$administratorExists =
    (int)$checkAdmin->fetchColumn()
    > 0;


/*
|--------------------------------------------------------------------------
| CREATE ADMINISTRATOR
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    !$administratorExists
) {

    $fullName =
        trim(
            $_POST["full_name"]
            ?? ""
        );


    $username =
        trim(
            $_POST["username"]
            ?? ""
        );


    $password =
        $_POST["password"]
        ?? "";


    $confirmPassword =
        $_POST["confirm_password"]
        ?? "";


    if (
        $fullName === ""
        ||
        $username === ""
        ||
        $password === ""
        ||
        $confirmPassword === ""
    ) {

        $message =
            "Please complete all fields.";

        $messageType =
            "error";

    } elseif (
        strlen($username) < 4
    ) {

        $message =
            "Username must contain at least 4 characters.";

        $messageType =
            "error";

    } elseif (
        strlen($password) < 8
    ) {

        $message =
            "Password must contain at least 8 characters.";

        $messageType =
            "error";

    } elseif (
        $password !== $confirmPassword
    ) {

        $message =
            "Passwords do not match.";

        $messageType =
            "error";

    } else {

        /*
        |--------------------------------------------------------------------------
        | CHECK USERNAME
        |--------------------------------------------------------------------------
        */

        $checkUsername =
            $pdo->prepare(
                "
                SELECT user_id
                FROM users
                WHERE username = ?
                LIMIT 1
                "
            );


        $checkUsername->execute([
            $username
        ]);


        if (
            $checkUsername->fetch()
        ) {

            $message =
                "Username already exists.";

            $messageType =
                "error";

        } else {

            /*
            |--------------------------------------------------------------------------
            | SECURE PASSWORD HASH
            |--------------------------------------------------------------------------
            */

            $passwordHash =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


            /*
            |--------------------------------------------------------------------------
            | INSERT ADMIN
            |--------------------------------------------------------------------------
            */

            $insert =
                $pdo->prepare(
                    "
                    INSERT INTO users
                    (
                        full_name,
                        username,
                        password_hash,
                        role,
                        account_status
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?
                    )
                    "
                );


            $insert->execute([
                $fullName,
                $username,
                $passwordHash,
                "Administrator",
                "Active"
            ]);


            $message =
                "Administrator account created successfully.";

            $messageType =
                "success";


            $administratorExists =
                true;
        }
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        AI: Attend Mo | Administrator Setup
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 30px;

            background: #f4f7fb;

            font-family:
                Arial,
                sans-serif;

            color: #172b4d;
        }

        .setup-card {
            width: 100%;
            max-width: 460px;

            padding: 32px;

            background: #ffffff;

            border: 1px solid #dfe5ef;
            border-radius: 16px;
        }

        .setup-label {
            margin-bottom: 7px;

            color: #155eef;

            font-size: 12px;
            font-weight: 800;

            text-transform: uppercase;
        }

        h1 {
            margin:
                0
                0
                8px;

            font-size: 26px;
        }

        .description {
            margin-bottom: 25px;

            color: #667085;

            font-size: 13px;
            line-height: 1.6;
        }

        .field {
            margin-bottom: 16px;
        }

        label {
            display: block;

            margin-bottom: 6px;

            font-size: 12px;
            font-weight: 700;
        }

        input {
            width: 100%;
            height: 43px;

            padding:
                0
                12px;

            border:
                1px solid
                #d8e0ec;

            border-radius: 8px;

            font-size: 13px;
        }

        input:focus {
            outline: none;

            border-color: #155eef;
        }

        button {
            width: 100%;
            height: 44px;

            margin-top: 5px;

            border: none;
            border-radius: 8px;

            background: #155eef;
            color: #ffffff;

            font-size: 13px;
            font-weight: 700;

            cursor: pointer;
        }

        button:hover {
            background: #1047b9;
        }

        .message {
            margin-bottom: 18px;

            padding: 12px;

            border-radius: 8px;

            font-size: 12px;
            line-height: 1.5;
        }

        .message.success {
            background: #ecfdf3;
            color: #067647;

            border: 1px solid #abefc6;
        }

        .message.error {
            background: #fef3f2;
            color: #b42318;

            border: 1px solid #fecdca;
        }

        .complete-box {
            padding: 20px;

            background: #ecfdf3;

            border:
                1px solid
                #abefc6;

            border-radius: 10px;

            color: #067647;

            font-size: 13px;
            line-height: 1.6;
        }

        .complete-box a {
            display: inline-block;

            margin-top: 15px;

            color: #155eef;

            font-weight: 700;

            text-decoration: none;
        }

    </style>

</head>


<body>


<div class="setup-card">


    <p class="setup-label">
        Initial System Setup
    </p>


    <h1>
        AI: Attend Mo
    </h1>


    <p class="description">
        Create the first Administrator account.
        The password will be stored as a secure hash
        and will not be saved as plain text.
    </p>


    <?php if ($message !== ""): ?>

        <div
            class="message <?php
            echo htmlspecialchars(
                $messageType
            );
            ?>"
        >

            <?php
            echo htmlspecialchars(
                $message
            );
            ?>

        </div>

    <?php endif; ?>


    <?php if ($administratorExists): ?>


        <div class="complete-box">

            <strong>
                Administrator account is configured.
            </strong>

            <br><br>

            Initial administrator setup is now complete.

            <br>

            You may proceed to the login system.

            <br>

            <a href="login.php">
                Go to Login
            </a>

        </div>


    <?php else: ?>


        <form method="POST">


            <div class="field">

                <label for="full_name">
                    Full Name
                </label>

                <input
                    type="text"
                    name="full_name"
                    id="full_name"
                    required
                    autocomplete="name"
                >

            </div>


            <div class="field">

                <label for="username">
                    Username
                </label>

                <input
                    type="text"
                    name="username"
                    id="username"
                    required
                    autocomplete="username"
                >

            </div>


            <div class="field">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    name="password"
                    id="password"
                    required
                    minlength="8"
                    autocomplete="new-password"
                >

            </div>


            <div class="field">

                <label for="confirm_password">
                    Confirm Password
                </label>

                <input
                    type="password"
                    name="confirm_password"
                    id="confirm_password"
                    required
                    minlength="8"
                    autocomplete="new-password"
                >

            </div>


            <button type="submit">
                Create Administrator
            </button>


        </form>


    <?php endif; ?>


</div>


</body>

</html>