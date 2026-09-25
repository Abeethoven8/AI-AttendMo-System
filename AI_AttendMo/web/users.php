<?php

require_once __DIR__ . "/includes/auth.php";
/*
|--------------------------------------------------------------------------
| AI: Attend Mo - User Access Management
|--------------------------------------------------------------------------
| Development interface for Role-Based Access Control (RBAC).
| Actual user accounts will be stored in MySQL.
|--------------------------------------------------------------------------
*/


$rolePermissions = [

    "Administrator" => [
        "Dashboard" => true,
        "Attendance" => true,
        "Analytics" => true,
        "Risk Prediction" => true,
        "Recommendations" => true,
        "Reports" => true,
        "User Management" => true
    ],

    "Authorized Staff" => [
        "Dashboard" => true,
        "Attendance" => true,
        "Analytics" => true,
        "Risk Prediction" => true,
        "Recommendations" => true,
        "Reports" => true,
        "User Management" => false
    ]

];

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
        AI: Attend Mo | Users
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>


<body class="dashboard-body">

<div class="app-layout">


    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="sidebar-brand">

            <h2>
                AI: Attend Mo
            </h2>

            <p>
                Attendance Analytics
            </p>

        </div>


        <nav class="sidebar-nav">

            <a
                href="dashboard.php"
                class="nav-link"
            >
                Dashboard
            </a>

            <a
                href="attendance.php"
                class="nav-link"
            >
                Attendance
            </a>

            <a
                href="analytics.php"
                class="nav-link"
            >
                Analytics
            </a>

            <a
                href="prediction.php"
                class="nav-link"
            >
                Risk Prediction
            </a>

            <a
                href="recommendations.php"
                class="nav-link"
            >
                Recommendations
            </a>

            <a
                href="reports.php"
                class="nav-link"
            >
                Reports
            </a>

            <a
                href="users.php"
                class="nav-link active"
            >
                Users
            </a>
            <a
                href="logout.php"
                class="nav-link"
            >
                Logout
            </a>

        </nav>


        <div class="sidebar-footer">

            <p>
                Echague National High School
            </p>

        </div>

    </aside>


    <!-- MAIN -->

    <main class="main-content">


        <header class="topbar">

            <div>

                <p class="page-label">
                    ACCESS CONTROL
                </p>

                <h1>
                    User Management
                </h1>

            </div>


            <div class="topbar-user">

                <div class="user-avatar">
                    A
                </div>

                <div>

                    <strong>
                        Authorized User
                    </strong>

                    <span>
                        System Access
                    </span>

                </div>

            </div>

        </header>


        <!-- NOTICE -->

        <section class="development-notice">

            <strong>
                Role-Based Access Control
            </strong>

            <p>
                This development interface defines system
                access roles and permissions. Actual users,
                passwords, and authentication will be stored
                securely in the MySQL database during the
                database integration step.
            </p>

        </section>


        <!-- SUMMARY -->

        <section class="user-summary-grid">


            <article class="user-summary-card">

                <span>
                    Defined Roles
                </span>

                <strong>
                    <?php
                    echo count(
                        $rolePermissions
                    );
                    ?>
                </strong>

                <small>
                    Current access roles
                </small>

            </article>


            <article class="user-summary-card">

                <span>
                    Registered Users
                </span>

                <strong>
                    —
                </strong>

                <small>
                    Available after MySQL integration
                </small>

            </article>


            <article class="user-summary-card">

                <span>
                    Active Accounts
                </span>

                <strong>
                    —
                </strong>

                <small>
                    Available after authentication setup
                </small>

            </article>


        </section>


        <!-- ROLE CARDS -->

        <section class="users-section">

            <div class="users-section-header">

                <div>

                    <p class="panel-label">
                        SYSTEM ROLES
                    </p>

                    <h2>
                        Access Roles
                    </h2>

                    <p>
                        Roles determine which system modules
                        an authorized user can access.
                    </p>

                </div>

            </div>


            <div class="role-card-grid">


                <?php foreach ($rolePermissions as $role => $permissions): ?>

                    <article class="role-card">

                        <div class="role-card-header">

                            <div class="role-icon">
                                <?php
                                echo strtoupper(
                                    substr(
                                        $role,
                                        0,
                                        1
                                    )
                                );
                                ?>
                            </div>

                            <div>

                                <h3>
                                    <?php
                                    echo htmlspecialchars(
                                        $role
                                    );
                                    ?>
                                </h3>

                                <p>
                                    Authorized system role
                                </p>

                            </div>

                        </div>


                        <div class="role-permission-list">


                            <?php foreach ($permissions as $module => $allowed): ?>

                                <div class="role-permission-row">

                                    <span>
                                        <?php
                                        echo htmlspecialchars(
                                            $module
                                        );
                                        ?>
                                    </span>


                                    <strong
                                        class="<?php
                                        echo
                                            $allowed
                                                ? "permission-allowed"
                                                : "permission-denied";
                                        ?>"
                                    >

                                        <?php
                                        echo
                                            $allowed
                                                ? "Allowed"
                                                : "Restricted";
                                        ?>

                                    </strong>

                                </div>

                            <?php endforeach; ?>


                        </div>

                    </article>

                <?php endforeach; ?>


            </div>

        </section>


        <!-- ACCESS MATRIX -->

        <section class="users-table-card">

            <div class="users-section-header">

                <div>

                    <p class="panel-label">
                        ACCESS MATRIX
                    </p>

                    <h2>
                        Module Permissions
                    </h2>

                </div>

            </div>


            <div class="users-table-wrapper">

                <table class="users-access-table">

                    <thead>

                        <tr>

                            <th>
                                Module
                            </th>


                            <?php foreach ($rolePermissions as $role => $permissions): ?>

                                <th>
                                    <?php
                                    echo htmlspecialchars(
                                        $role
                                    );
                                    ?>
                                </th>

                            <?php endforeach; ?>

                        </tr>

                    </thead>


                    <tbody>

                        <?php

                        $modules =
                            array_keys(
                                reset(
                                    $rolePermissions
                                )
                            );

                        ?>


                        <?php foreach ($modules as $module): ?>

                            <tr>

                                <td>
                                    <strong>
                                        <?php
                                        echo htmlspecialchars(
                                            $module
                                        );
                                        ?>
                                    </strong>
                                </td>


                                <?php foreach ($rolePermissions as $permissions): ?>

                                    <?php

                                    $allowed =
                                        $permissions[
                                            $module
                                        ]
                                        ?? false;

                                    ?>

                                    <td>

                                        <span
                                            class="<?php
                                            echo
                                                $allowed
                                                    ? "access-yes"
                                                    : "access-no";
                                            ?>"
                                        >

                                            <?php
                                            echo
                                                $allowed
                                                    ? "✓"
                                                    : "—";
                                            ?>

                                        </span>

                                    </td>

                                <?php endforeach; ?>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </section>


        <!-- ACCOUNT PLACEHOLDER -->

        <section class="users-table-card">

            <div class="users-section-header">

                <div>

                    <p class="panel-label">
                        USER ACCOUNTS
                    </p>

                    <h2>
                        Registered Users
                    </h2>

                    <p>
                        User records will appear here once
                        MySQL authentication is connected.
                    </p>

                </div>

            </div>


            <div class="users-empty-state">

                <div class="users-empty-icon">
                    U
                </div>

                <h3>
                    Database Connection Required
                </h3>

                <p>
                    No temporary or hard-coded user accounts
                    are being created. User accounts will use
                    secure password hashing and role-based
                    permissions after MySQL integration.
                </p>

            </div>

        </section>


    </main>

</div>

</body>

</html>