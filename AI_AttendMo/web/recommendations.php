<?php

require_once __DIR__
    . "/includes/report_data.php";


/* ==========================================================
   FILTERS
   ========================================================== */

$searchStudent =
    trim($_GET["student"] ?? "");

$filterRisk =
    trim($_GET["risk"] ?? "");

$filterPriority =
    trim($_GET["priority"] ?? "");


/* ==========================================================
   FILTER RECORDS
   ========================================================== */

$filteredRecommendations = [];

foreach ($recommendationRows as $row) {

    $studentId =
        $row["Student_ID"] ?? "";

    $risk =
        $row["Predicted_Risk"] ?? "";

    $priority =
        $row["Recommendation_Priority"] ?? "";


    if (
        $searchStudent !== ""
        &&
        stripos(
            $studentId,
            $searchStudent
        ) === false
    ) {
        continue;
    }


    if (
        $filterRisk !== ""
        &&
        $risk !== $filterRisk
    ) {
        continue;
    }


    if (
        $filterPriority !== ""
        &&
        $priority !== $filterPriority
    ) {
        continue;
    }


    $filteredRecommendations[] =
        $row;
}


/* ==========================================================
   SUMMARY
   ========================================================== */

$totalFiltered =
    count($filteredRecommendations);


function recommendationPercent($value)
{
    if (!is_numeric($value)) {
        return "—";
    }

    $number =
        (float)$value;

    if ($number <= 1) {
        $number *= 100;
    }

    return number_format(
        $number,
        2
    ) . "%";
}


function recommendationTrend($value)
{
    if (!is_numeric($value)) {
        return "—";
    }

    $number =
        (float)$value;

    $percent =
        $number * 100;

    if ($percent > 0) {
        return "+"
            . number_format(
                $percent,
                2
            )
            . " pp";
    }

    return number_format(
        $percent,
        2
    ) . " pp";
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
        AI: Attend Mo | Recommendations
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
                class="nav-link active"
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
                class="nav-link"
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
                    PRESCRIPTIVE ANALYTICS
                </p>

                <h1>
                    Recommendations
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
                Decision-Support Recommendations
            </strong>

            <p>
                Recommendations shown here are based on
                synthetic development data. The system
                suggests possible actions only and does not
                automatically perform interventions.
            </p>

        </section>


        <!-- SUMMARY -->

        <section class="recommendation-summary-grid">


            <article
                class="recommendation-summary-card high"
            >

                <span>
                    High Priority
                </span>

                <strong>
                    <?php
                    echo $highPriorityCount;
                    ?>
                </strong>

                <small>
                    Appropriate intervention
                </small>

            </article>


            <article
                class="recommendation-summary-card medium"
            >

                <span>
                    Medium Priority
                </span>

                <strong>
                    <?php
                    echo $mediumPriorityCount;
                    ?>
                </strong>

                <small>
                    Closer monitoring
                </small>

            </article>


            <article
                class="recommendation-summary-card routine"
            >

                <span>
                    Routine
                </span>

                <strong>
                    <?php
                    echo $routinePriorityCount;
                    ?>
                </strong>

                <small>
                    Regular monitoring
                </small>

            </article>


            <article
                class="recommendation-summary-card total"
            >

                <span>
                    Total Recommendations
                </span>

                <strong>
                    <?php
                    echo count(
                        $recommendationRows
                    );
                    ?>
                </strong>

                <small>
                    Test student records
                </small>

            </article>


        </section>


        <!-- FILTERS -->

        <section class="recommendation-filter-card">


            <div class="recommendation-card-header">

                <div>

                    <p class="panel-label">
                        FILTER RESULTS
                    </p>

                    <h3>
                        Recommendation Worklist
                    </h3>

                </div>


                <a
                    href="recommendations.php"
                    class="clear-filter-link"
                >
                    Clear Filters
                </a>

            </div>


            <form
                method="GET"
                action="recommendations.php"
                class="recommendation-filter-form"
            >


                <div class="attendance-filter-group">

                    <label for="student">
                        Student ID
                    </label>

                    <input
                        type="text"
                        id="student"
                        name="student"
                        placeholder="Search Student ID"
                        value="<?php
                        echo htmlspecialchars(
                            $searchStudent
                        );
                        ?>"
                    >

                </div>


                <div class="attendance-filter-group">

                    <label for="risk">
                        Predicted Risk
                    </label>

                    <select
                        id="risk"
                        name="risk"
                    >

                        <option value="">
                            All Risk Levels
                        </option>

                        <option
                            value="High Risk"
                            <?php
                            echo
                                $filterRisk === "High Risk"
                                    ? "selected"
                                    : "";
                            ?>
                        >
                            High Risk
                        </option>

                        <option
                            value="Moderate Risk"
                            <?php
                            echo
                                $filterRisk === "Moderate Risk"
                                    ? "selected"
                                    : "";
                            ?>
                        >
                            Moderate Risk
                        </option>

                        <option
                            value="Low Risk"
                            <?php
                            echo
                                $filterRisk === "Low Risk"
                                    ? "selected"
                                    : "";
                            ?>
                        >
                            Low Risk
                        </option>

                    </select>

                </div>


                <div class="attendance-filter-group">

                    <label for="priority">
                        Priority
                    </label>

                    <select
                        id="priority"
                        name="priority"
                    >

                        <option value="">
                            All Priorities
                        </option>

                        <option
                            value="High"
                            <?php
                            echo
                                $filterPriority === "High"
                                    ? "selected"
                                    : "";
                            ?>
                        >
                            High
                        </option>

                        <option
                            value="Medium"
                            <?php
                            echo
                                $filterPriority === "Medium"
                                    ? "selected"
                                    : "";
                            ?>
                        >
                            Medium
                        </option>

                        <option
                            value="Routine"
                            <?php
                            echo
                                $filterPriority === "Routine"
                                    ? "selected"
                                    : "";
                            ?>
                        >
                            Routine
                        </option>

                    </select>

                </div>


                <div class="recommendation-filter-action">

                    <button
                        type="submit"
                        class="attendance-filter-button"
                    >
                        Apply Filters
                    </button>

                </div>


            </form>


        </section>


        <!-- TABLE -->

        <section class="recommendation-table-card">


            <div class="recommendation-card-header">

                <div>

                    <p class="panel-label">
                        DECISION SUPPORT
                    </p>

                    <h3>
                        Student Recommendations
                    </h3>

                </div>


                <span class="analytics-v2-count">

                    <?php
                    echo number_format(
                        $totalFiltered
                    );
                    ?>

                    records

                </span>

            </div>


            <?php if (empty($filteredRecommendations)): ?>


                <div class="analytics-v2-empty">

                    No recommendations match the
                    selected filters.

                </div>


            <?php else: ?>


                <div class="recommendation-table-wrapper">

                    <table class="recommendation-table">


                        <thead>

                            <tr>

                                <th>
                                    Student
                                </th>

                                <th>
                                    Grade / Section
                                </th>

                                <th>
                                    Attendance
                                </th>

                                <th>
                                    Trend
                                </th>

                                <th>
                                    Risk
                                </th>

                                <th>
                                    Priority
                                </th>

                                <th>
                                    Recommendation
                                </th>

                                <th>
                                    Basis
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach ($filteredRecommendations as $row): ?>

                                <?php

                                $risk =
                                    $row["Predicted_Risk"]
                                    ?? "";

                                $priority =
                                    $row[
                                        "Recommendation_Priority"
                                    ]
                                    ?? "";

                                $trend =
                                    $row[
                                        "Attendance_Trend_Change"
                                    ]
                                    ?? null;

                                ?>


                                <tr>


                                    <td>

                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $row[
                                                    "Student_ID"
                                                ]
                                                ?? "—"
                                            );
                                            ?>
                                        </strong>

                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $row[
                                                "Grade_Level"
                                            ]
                                            ?? "—"
                                        );
                                        ?>

                                        <br>

                                        <small>
                                            <?php
                                            echo htmlspecialchars(
                                                $row[
                                                    "Section"
                                                ]
                                                ?? "—"
                                            );
                                            ?>
                                        </small>

                                    </td>


                                    <td>

                                        <strong>
                                            <?php
                                            echo recommendationPercent(
                                                $row[
                                                    "Historical_Attendance_Rate"
                                                ]
                                                ?? null
                                            );
                                            ?>
                                        </strong>

                                    </td>


                                    <td>

                                        <span
                                            class="<?php
                                            echo
                                                is_numeric($trend)
                                                &&
                                                (float)$trend < 0
                                                    ? "trend-negative"
                                                    : "trend-stable";
                                            ?>"
                                        >

                                            <?php
                                            echo recommendationTrend(
                                                $trend
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <td>

                                        <span
                                            class="risk-badge
                                            <?php
                                            echo htmlspecialchars(
                                                strtolower(
                                                    str_replace(
                                                        " ",
                                                        "-",
                                                        $risk
                                                    )
                                                )
                                            );
                                            ?>"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $risk
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <td>

                                        <span
                                            class="priority-badge
                                            <?php
                                            echo htmlspecialchars(
                                                strtolower(
                                                    $priority
                                                )
                                            );
                                            ?>"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $priority
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <td class="recommendation-text-cell">

                                        <?php
                                        echo htmlspecialchars(
                                            $row[
                                                "Recommendation"
                                            ]
                                            ?? "—"
                                        );
                                        ?>

                                    </td>


                                    <td class="recommendation-basis-cell">

                                        <?php
                                        echo htmlspecialchars(
                                            $row[
                                                "Recommendation_Basis"
                                            ]
                                            ?? "—"
                                        );
                                        ?>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        </tbody>


                    </table>

                </div>


            <?php endif; ?>


        </section>


        <div class="analytics-v2-footnote">

            <strong>
                Important:
            </strong>

            These recommendations support human
            decision-making. The system does not automatically
            contact students, parents, teachers, or other
            school personnel.

        </div>


    </main>

</div>

</body>

</html>