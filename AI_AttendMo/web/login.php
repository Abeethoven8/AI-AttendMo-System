<?php
session_start();

/*
|--------------------------------------------------------------------------
| AI: Attend Mo - Login Page
|--------------------------------------------------------------------------
| Authentication will be connected to MySQL in the next steps.
| For now, this page provides the official system login interface.
|--------------------------------------------------------------------------
*/

if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit;
}

$error = $_SESSION["login_error"] ?? "";
unset($_SESSION["login_error"]);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>AI: Attend Mo | Login</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >
</head>

<body class="login-body">

    <main class="login-page">

        <!-- LEFT SIDE -->
        <section class="login-brand-panel">

            <div class="brand-content">

                <h1>
                    AI: Attend Mo
                </h1>

                <p class="brand-subtitle">
                    Attendance Monitoring and Analytics System
                </p>

                <div class="brand-divider"></div>

                <p class="school-name">
                    Echague National High School
                </p>

                <p class="brand-description">
                    A decision-support system for managing,
                    analyzing, and visualizing attendance data.
                </p>

            </div>

            <div class="brand-footer">
                Capstone Project
            </div>

        </section>


        <!-- RIGHT SIDE -->
        <section class="login-form-panel">

            <div class="login-card">

                <div class="login-header">

                    <span class="login-label">
                        SECURE ACCESS
                    </span>

                    <h2>
                        Welcome Back
                    </h2>

                    <p>
                        Sign in to access the AI: Attend Mo system.
                    </p>

                </div>


                <?php if (!empty($error)): ?>

                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>

                <?php endif; ?>


                <form
                    action="login_process.php"
                    method="POST"
                    class="login-form"
                    autocomplete="off"
                >

                    <div class="form-group">

                        <label for="username">
                            Username
                        </label>

                        <input
                            type="text"
                            id="username"
                            name="username"
                            placeholder="Enter your username"
                            required
                            autocomplete="username"
                        >

                    </div>


                    <div class="form-group">

                        <label for="password">
                            Password
                        </label>

                        <div class="password-wrapper">

                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                id="passwordToggle"
                                aria-label="Show password"
                            >
                                Show
                            </button>

                        </div>

                    </div>


                    <button
                        type="submit"
                        class="login-button"
                    >
                        Sign In
                    </button>

                </form>


                <div class="login-security-note">

                    <span class="security-icon">
                        🔒
                    </span>

                    <p>
                        Access is restricted to authorized
                        school personnel.
                    </p>

                </div>

            </div>


            <p class="system-footer">
                AI: Attend Mo &copy;
                <?php echo date("Y"); ?>
                &nbsp;•&nbsp;
                Attendance Analytics and Decision Support
            </p>

        </section>

    </main>


    <script>

        const passwordInput =
            document.getElementById("password");

        const passwordToggle =
            document.getElementById("passwordToggle");


        passwordToggle.addEventListener(
            "click",
            function () {

                const isPassword =
                    passwordInput.type === "password";


                passwordInput.type =
                    isPassword
                        ? "text"
                        : "password";


                passwordToggle.textContent =
                    isPassword
                        ? "Hide"
                        : "Show";


                passwordToggle.setAttribute(
                    "aria-label",
                    isPassword
                        ? "Hide password"
                        : "Show password"
                );
            }
        );

    </script>

</body>

</html>