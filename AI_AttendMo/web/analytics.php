<?php

require_once __DIR__ . "/includes/auth.php";
/*
|--------------------------------------------------------------------------
| AI: Attend Mo - Analytics Page
|--------------------------------------------------------------------------
| Descriptive, Diagnostic, and Inferential Analytics
| Current results use synthetic development data.
|--------------------------------------------------------------------------
*/

require_once __DIR__
    . "/includes/report_data.php";


// ============================================================
// 1. LOAD REPORTS
// ============================================================

$gradeRows = readCsvRows(
    $reportsPath . DIRECTORY_SEPARATOR . "attendance_by_grade.csv"
);

$sectionRows = readCsvRows(
    $reportsPath . DIRECTORY_SEPARATOR . "attendance_by_section.csv"
);

$subjectRows = readCsvRows(
    $reportsPath . DIRECTORY_SEPARATOR . "attendance_by_subject.csv"
);

$inferentialRows = readCsvRows(
    $reportsPath . DIRECTORY_SEPARATOR . "inferential_results.csv"
);

$posthocRows = readCsvRows(
    $reportsPath . DIRECTORY_SEPARATOR . "subject_posthoc_results.csv"
);

$diagnosticStudentRows = readCsvRows(
    $reportsPath . DIRECTORY_SEPARATOR . "diagnostic_student_patterns.csv"
);

$diagnosticGradeRows = readCsvRows(
    $reportsPath . DIRECTORY_SEPARATOR . "diagnostic_grade_patterns.csv"
);

$diagnosticSectionRows = readCsvRows(
    $reportsPath . DIRECTORY_SEPARATOR . "diagnostic_section_patterns.csv"
);

$diagnosticSubjectRows = readCsvRows(
    $reportsPath . DIRECTORY_SEPARATOR . "diagnostic_subject_patterns.csv"
);


// ============================================================
// 2. BASIC HELPERS
// ============================================================

function analyticsValue(
    array $row,
    $key,
    $default = "—"
) {
    return (
        isset($row[$key])
        &&
        $row[$key] !== ""
    )
        ? $row[$key]
        : $default;
}


function analyticsRate($value)
{
    if (!is_numeric($value)) {
        return "—";
    }

    $number = (float)$value;

    if ($number <= 1) {
        $number *= 100;
    }

    return number_format(
        $number,
        2
    ) . "%";
}


function analyticsNumber($value)
{
    if (!is_numeric($value)) {
        return "—";
    }

    return number_format(
        (float)$value,
        0
    );
}


function analyticsStatistic($value)
{
    if (!is_numeric($value)) {
        return "—";
    }

    return number_format(
        (float)$value,
        4
    );
}


function analyticsPValue($value)
{
    if (!is_numeric($value)) {
        return "—";
    }

    $value = (float)$value;

    if ($value < 0.001) {
        return "< 0.001";
    }

    return number_format(
        $value,
        6
    );
}


// ============================================================
// 3. CHART SCALE
// ============================================================

function analyticsChartScale(
    array $rows,
    $rateColumn = "Attendance_Rate"
) {
    $rates = [];

    foreach ($rows as $row) {

        $value =
            $row[$rateColumn]
            ?? null;

        if (!is_numeric($value)) {
            continue;
        }

        $rate = (float)$value;

        if ($rate <= 1) {
            $rate *= 100;
        }

        $rates[] = $rate;
    }


    if (empty($rates)) {
        return [
            "min" => 0,
            "max" => 100
        ];
    }


    $dataMin = min($rates);
    $dataMax = max($rates);


    /*
    |--------------------------------------------------------------------------
    | Give the chart some visual padding without inventing data.
    |--------------------------------------------------------------------------
    */

    $min =
        floor(
            ($dataMin - 1) / 2
        ) * 2;

    $max =
        ceil(
            ($dataMax + 1) / 2
        ) * 2;


    $min = max(
        0,
        $min
    );

    $max = min(
        100,
        $max
    );


    if ($max <= $min) {

        $min =
            max(
                0,
                $dataMin - 2
            );

        $max =
            min(
                100,
                $dataMax + 2
            );
    }


    return [
        "min" => $min,
        "max" => $max
    ];
}


function analyticsScaledBar(
    $value,
    $min,
    $max
) {
    if (!is_numeric($value)) {
        return 0;
    }

    $value = (float)$value;

    if ($value <= 1) {
        $value *= 100;
    }

    if ($max <= $min) {
        return 0;
    }

    $position =
        (($value - $min)
        /
        ($max - $min))
        * 100;

    return max(
        4,
        min(
            100,
            $position
        )
    );
}


// ============================================================
// 4. VERTICAL BAR CHART
// ============================================================

function renderVerticalAttendanceChart(
    array $rows,
    $labelColumn
) {
    if (empty($rows)) {

        echo '
        <div class="analytics-chart-empty">
            No chart data available.
        </div>
        ';

        return;
    }


    $scale =
        analyticsChartScale(
            $rows
        );

    $min =
        $scale["min"];

    $max =
        $scale["max"];

    $middle =
        ($min + $max) / 2;


    ?>

    <div class="attendance-vbar-chart">

        <div class="attendance-vbar-axis">

            <span>
                <?php echo number_format($max, 1); ?>%
            </span>

            <span>
                <?php echo number_format($middle, 1); ?>%
            </span>

            <span>
                <?php echo number_format($min, 1); ?>%
            </span>

        </div>


        <div class="attendance-vbar-plot">


            <div class="attendance-chart-grid grid-top"></div>

            <div class="attendance-chart-grid grid-middle"></div>

            <div class="attendance-chart-grid grid-bottom"></div>


            <div class="attendance-vbar-bars">


                <?php foreach ($rows as $row): ?>

                    <?php

                    $rate =
                        analyticsValue(
                            $row,
                            "Attendance_Rate",
                            0
                        );

                    $height =
                        analyticsScaledBar(
                            $rate,
                            $min,
                            $max
                        );

                    $label =
                        analyticsValue(
                            $row,
                            $labelColumn
                        );

                    ?>


                    <div class="attendance-vbar-item">


                        <div class="attendance-vbar-column-area">

                            <div
                                class="attendance-vbar-column-group"
                                style="height:
                                <?php echo $height; ?>%;"
                            >

                                <span class="attendance-vbar-value">

                                    <?php
                                    echo analyticsRate(
                                        $rate
                                    );
                                    ?>

                                </span>


                                <div
                                    class="attendance-vbar-column"
                                    title="<?php
                                    echo htmlspecialchars(
                                        $label
                                    );
                                    ?>: <?php
                                    echo analyticsRate(
                                        $rate
                                    );
                                    ?>"
                                ></div>

                            </div>

                        </div>


                        <strong class="attendance-vbar-label">

                            <?php
                            echo htmlspecialchars(
                                $label
                            );
                            ?>

                        </strong>


                        <small>

                            <?php
                            echo analyticsNumber(
                                analyticsValue(
                                    $row,
                                    "Total_Sessions",
                                    0
                                )
                            );
                            ?>
                            sessions

                        </small>


                    </div>


                <?php endforeach; ?>


            </div>

        </div>

    </div>


    <div class="analytics-chart-scale-note">

        Chart scale:
        <?php echo number_format($min, 1); ?>%
        –
        <?php echo number_format($max, 1); ?>%.
        Exact percentages are shown above each bar.

    </div>

    <?php
}


// ============================================================
// 5. HORIZONTAL SECTION BAR CHART
// ============================================================

function renderSectionChart(
    array $rows
) {
    if (empty($rows)) {

        echo '
        <div class="analytics-chart-empty">
            No section data available.
        </div>
        ';

        return;
    }


    $scale =
        analyticsChartScale(
            $rows
        );

    $min =
        $scale["min"];

    $max =
        $scale["max"];

    ?>


    <div class="attendance-hbar-scale">

        <span>
            <?php echo number_format($min, 1); ?>%
        </span>

        <span>
            Attendance Rate
        </span>

        <span>
            <?php echo number_format($max, 1); ?>%
        </span>

    </div>


    <div class="attendance-hbar-list">


        <?php foreach ($rows as $row): ?>

            <?php

            $rate =
                analyticsValue(
                    $row,
                    "Attendance_Rate",
                    0
                );

            $width =
                analyticsScaledBar(
                    $rate,
                    $min,
                    $max
                );

            ?>


            <div class="attendance-hbar-row">


                <div class="attendance-hbar-name">

                    <strong>

                        <?php
                        echo htmlspecialchars(
                            analyticsValue(
                                $row,
                                "Grade_Level"
                            )
                        );
                        ?>

                        -

                        <?php
                        echo htmlspecialchars(
                            analyticsValue(
                                $row,
                                "Section"
                            )
                        );
                        ?>

                    </strong>


                    <small>

                        <?php
                        echo analyticsNumber(
                            analyticsValue(
                                $row,
                                "Total_Sessions",
                                0
                            )
                        );
                        ?>
                        sessions

                    </small>

                </div>


                <div class="attendance-hbar-track">

                    <div
                        class="attendance-hbar-fill"
                        style="width:
                        <?php echo $width; ?>%;"
                    ></div>

                </div>


                <strong class="attendance-hbar-value">

                    <?php
                    echo analyticsRate(
                        $rate
                    );
                    ?>

                </strong>


            </div>


        <?php endforeach; ?>


    </div>


    <div class="analytics-chart-scale-note">

        Horizontal scale automatically adjusts to the
        observed section attendance rates.

    </div>

    <?php
}


// ============================================================
// 6. DIAGNOSTIC TABLE
// ============================================================

function diagnosticDisplayValue(
    $column,
    $value
) {
    if (
        $value === ""
        ||
        $value === null
    ) {
        return "—";
    }


    $columnLower =
        strtolower(
            $column
        );


    if (
        is_numeric($value)
        &&
        (
            str_contains(
                $columnLower,
                "rate"
            )
            ||
            str_contains(
                $columnLower,
                "frequency"
            )
            ||
            str_contains(
                $columnLower,
                "proportion"
            )
        )
    ) {

        $number =
            (float)$value;

        if ($number <= 1) {

            return number_format(
                $number * 100,
                2
            ) . "%";
        }
    }


    return (string)$value;
}


function renderDiagnosticTable(
    array $rows,
    array $preferredColumns,
    $limit = 10
) {
    if (empty($rows)) {

        echo '
        <div class="analytics-chart-empty">
            No diagnostic data available.
        </div>
        ';

        return;
    }


    $available =
        array_keys(
            $rows[0]
        );

    $columns = [];


    foreach ($preferredColumns as $column) {

        if (
            in_array(
                $column,
                $available,
                true
            )
        ) {

            $columns[] =
                $column;
        }
    }


    if (empty($columns)) {

        $columns =
            array_slice(
                $available,
                0,
                6
            );
    }


    $columns =
        array_slice(
            $columns,
            0,
            7
        );


    $rows =
        array_slice(
            $rows,
            0,
            $limit
        );


    ?>

    <div class="analytics-v2-table-scroll">

        <table class="analytics-v2-table">

            <thead>

                <tr>

                    <?php foreach ($columns as $column): ?>

                        <th>

                            <?php
                            echo htmlspecialchars(
                                str_replace(
                                    "_",
                                    " ",
                                    $column
                                )
                            );
                            ?>

                        </th>

                    <?php endforeach; ?>

                </tr>

            </thead>


            <tbody>


                <?php foreach ($rows as $row): ?>

                    <tr>


                        <?php foreach ($columns as $column): ?>

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    diagnosticDisplayValue(
                                        $column,
                                        $row[$column]
                                        ?? "—"
                                    )
                                );
                                ?>

                            </td>

                        <?php endforeach; ?>


                    </tr>

                <?php endforeach; ?>


            </tbody>

        </table>

    </div>

    <?php
}


// ============================================================
// 7. HIGHEST VALUES
// ============================================================

$highestGrade = null;
$highestSubject = null;


if (!empty($gradeRows)) {

    $gradeCopy =
        $gradeRows;

    usort(
        $gradeCopy,
        function ($a, $b) {

            return
                (float)$b["Attendance_Rate"]
                <=>
                (float)$a["Attendance_Rate"];
        }
    );

    $highestGrade =
        $gradeCopy[0];
}


if (!empty($subjectRows)) {

    $subjectCopy =
        $subjectRows;

    usort(
        $subjectCopy,
        function ($a, $b) {

            return
                (float)$b["Attendance_Rate"]
                <=>
                (float)$a["Attendance_Rate"];
        }
    );

    $highestSubject =
        $subjectCopy[0];
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
        AI: Attend Mo | Analytics
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

            <a href="dashboard.php" class="nav-link">
                Dashboard
            </a>

            <a href="attendance.php" class="nav-link">
                Attendance
            </a>

            <a href="analytics.php" class="nav-link active">
                Analytics
            </a>

            <a href="prediction.php" class="nav-link">
                Risk Prediction
            </a>

            <a href="recommendations.php" class="nav-link">
                Recommendations
            </a>

            <a href="reports.php" class="nav-link">
                Reports
            </a>

            <a href="users.php" class="nav-link">
                Users
            </a>

            <a href="logout.php" class="nav-link">
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
                    ATTENDANCE ANALYTICS
                </p>

                <h1>
                    Analytics
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


        <!-- DEVELOPMENT NOTICE -->

        <section class="development-notice">

            <strong>
                Synthetic Development Results
            </strong>

            <p>
                All analytical results displayed on this page
                currently come from synthetic development data
                and are not actual findings from Echague
                National High School.
            </p>

        </section>


        <!-- TABS -->

        <nav class="analytics-v2-tabs">

            <a href="#descriptive">
                Descriptive
            </a>

            <a href="#diagnostic">
                Diagnostic
            </a>

            <a href="#inferential">
                Inferential
            </a>

        </nav>


        <!-- =================================================
             DESCRIPTIVE
             ================================================= -->

        <section
            id="descriptive"
            class="analytics-v2-section"
        >


            <div class="analytics-v2-heading">

                <div>

                    <p class="panel-label">
                        DESCRIPTIVE ANALYTICS
                    </p>

                    <h2>
                        Attendance Summary
                    </h2>

                    <p>
                        Visual comparison of historical
                        attendance according to grade level,
                        section, and subject.
                    </p>

                </div>

            </div>


            <!-- SUMMARY CARDS -->

            <div class="analytics-v2-summary-grid">


                <article class="analytics-v2-summary">

                    <span>
                        Overall Attendance
                    </span>

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $attendanceRate
                        );
                        ?>
                    </strong>

                    <small>
                        All attendance records
                    </small>

                </article>


                <article class="analytics-v2-summary">

                    <span>
                        Overall Absence
                    </span>

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $absenceRate
                        );
                        ?>
                    </strong>

                    <small>
                        All attendance records
                    </small>

                </article>


                <article class="analytics-v2-summary">

                    <span>
                        Highest Grade Attendance
                    </span>

                    <strong>

                        <?php
                        echo
                            $highestGrade
                                ? analyticsRate(
                                    $highestGrade[
                                        "Attendance_Rate"
                                    ]
                                )
                                : "—";
                        ?>

                    </strong>

                    <small>

                        <?php
                        echo
                            $highestGrade
                                ? htmlspecialchars(
                                    $highestGrade[
                                        "Grade_Level"
                                    ]
                                )
                                : "No data";
                        ?>

                    </small>

                </article>


                <article class="analytics-v2-summary">

                    <span>
                        Highest Subject Attendance
                    </span>

                    <strong>

                        <?php
                        echo
                            $highestSubject
                                ? analyticsRate(
                                    $highestSubject[
                                        "Attendance_Rate"
                                    ]
                                )
                                : "—";
                        ?>

                    </strong>

                    <small>

                        <?php
                        echo
                            $highestSubject
                                ? htmlspecialchars(
                                    $highestSubject[
                                        "Subject"
                                    ]
                                )
                                : "No data";
                        ?>

                    </small>

                </article>


            </div>


            <!-- GRADE + SUBJECT CHARTS -->

            <div class="analytics-v2-two-column">


                <article class="analytics-v2-card">

                    <div class="analytics-v2-card-header">

                        <div>

                            <h3>
                                Attendance by Grade Level
                            </h3>

                            <p>
                                Bar chart comparison of
                                grade-level attendance rates.
                            </p>

                        </div>

                    </div>


                    <?php

                    renderVerticalAttendanceChart(
                        $gradeRows,
                        "Grade_Level"
                    );

                    ?>


                </article>


                <article class="analytics-v2-card">

                    <div class="analytics-v2-card-header">

                        <div>

                            <h3>
                                Attendance by Subject
                            </h3>

                            <p>
                                Bar chart comparison of
                                subject attendance rates.
                            </p>

                        </div>

                    </div>


                    <?php

                    renderVerticalAttendanceChart(
                        $subjectRows,
                        "Subject"
                    );

                    ?>


                </article>


            </div>


            <!-- SECTION CHART -->

            <article class="analytics-v2-card">

                <div class="analytics-v2-card-header">

                    <div>

                        <h3>
                            Attendance by Section
                        </h3>

                        <p>
                            Horizontal bar chart comparing
                            each grade-section group.
                        </p>

                    </div>


                    <span class="analytics-v2-count">

                        <?php
                        echo count(
                            $sectionRows
                        );
                        ?>
                        sections

                    </span>

                </div>


                <?php
                renderSectionChart(
                    $sectionRows
                );
                ?>


            </article>


        </section>


        <!-- =================================================
             DIAGNOSTIC
             ================================================= -->

        <section
            id="diagnostic"
            class="analytics-v2-section"
        >


            <div class="analytics-v2-heading">

                <div>

                    <p class="panel-label">
                        DIAGNOSTIC ANALYTICS
                    </p>

                    <h2>
                        Absence Patterns
                    </h2>

                    <p>
                        Attendance patterns associated with
                        frequent and recurring absences.
                    </p>

                </div>

            </div>


            <article class="analytics-v2-card">

                <div class="analytics-v2-card-header">

                    <div>

                        <h3>
                            Frequently Absent Students
                        </h3>

                        <p>
                            Top student-level diagnostic
                            attendance patterns.
                        </p>

                    </div>

                    <span class="analytics-v2-count">
                        Top 10
                    </span>

                </div>


                <?php

                renderDiagnosticTable(

                    $diagnosticStudentRows,

                    [
                        "Student_ID",
                        "Grade_Level",
                        "Section",
                        "Total_Absences",
                        "Absence_Count",
                        "Attendance_Rate",
                        "Weeks_With_Absence",
                        "Longest_Consecutive_Days_With_Any_Absence",
                        "Lowest_Attendance_Subject",
                        "Highest_Absence_Weekday"
                    ],

                    10
                );

                ?>

            </article>


            <div class="analytics-v2-two-column">


                <article class="analytics-v2-card">

                    <div class="analytics-v2-card-header">

                        <div>

                            <h3>
                                Grade Patterns
                            </h3>

                            <p>
                                Diagnostic summary by grade.
                            </p>

                        </div>

                    </div>


                    <?php

                    renderDiagnosticTable(

                        $diagnosticGradeRows,

                        [
                            "Grade_Level",
                            "Total_Sessions",
                            "Present_Count",
                            "Absence_Count",
                            "Attendance_Rate",
                            "Absence_Rate"
                        ],

                        10
                    );

                    ?>

                </article>


                <article class="analytics-v2-card">

                    <div class="analytics-v2-card-header">

                        <div>

                            <h3>
                                Subject Patterns
                            </h3>

                            <p>
                                Diagnostic summary by subject.
                            </p>

                        </div>

                    </div>


                    <?php

                    renderDiagnosticTable(

                        $diagnosticSubjectRows,

                        [
                            "Subject",
                            "Total_Sessions",
                            "Present_Count",
                            "Absence_Count",
                            "Attendance_Rate",
                            "Absence_Rate"
                        ],

                        10
                    );

                    ?>

                </article>


            </div>


            <article class="analytics-v2-card">

                <div class="analytics-v2-card-header">

                    <div>

                        <h3>
                            Section Patterns
                        </h3>

                        <p>
                            Diagnostic summary by grade
                            and section.
                        </p>

                    </div>

                </div>


                <?php

                renderDiagnosticTable(

                    $diagnosticSectionRows,

                    [
                        "Grade_Level",
                        "Section",
                        "Total_Sessions",
                        "Present_Count",
                        "Absence_Count",
                        "Attendance_Rate",
                        "Absence_Rate"
                    ],

                    15
                );

                ?>

            </article>


        </section>


        <!-- =================================================
             INFERENTIAL
             ================================================= -->

        <section
            id="inferential"
            class="analytics-v2-section"
        >


            <div class="analytics-v2-heading">

                <div>

                    <p class="panel-label">
                        INFERENTIAL ANALYTICS
                    </p>

                    <h2>
                        Statistical Analysis
                    </h2>

                    <p>
                        Statistical comparisons using
                        α = 0.05.
                    </p>

                </div>

            </div>


            <div class="analytics-v2-test-grid">


                <?php foreach ($inferentialRows as $row): ?>

                    <?php

                    $decision =
                        analyticsValue(
                            $row,
                            "Decision"
                        );

                    $significant =
                        $decision
                        ===
                        "Reject H0";

                    ?>


                    <article class="analytics-v2-test-card">


                        <div class="analytics-v2-test-status">

                            <span
                                class="<?php
                                echo
                                    $significant
                                        ? "analytics-v2-significant"
                                        : "analytics-v2-not-significant";
                                ?>"
                            >

                                <?php
                                echo
                                    $significant
                                        ? "Significant"
                                        : "Not Significant";
                                ?>

                            </span>

                        </div>


                        <h3>

                            <?php
                            echo htmlspecialchars(
                                analyticsValue(
                                    $row,
                                    "Comparison"
                                )
                            );
                            ?>

                        </h3>


                        <div class="analytics-v2-test-details">


                            <div>

                                <span>
                                    Test
                                </span>

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        analyticsValue(
                                            $row,
                                            "Test_Used"
                                        )
                                    );
                                    ?>
                                </strong>

                            </div>


                            <div>

                                <span>
                                    Statistic
                                </span>

                                <strong>
                                    <?php
                                    echo analyticsStatistic(
                                        analyticsValue(
                                            $row,
                                            "Statistic"
                                        )
                                    );
                                    ?>
                                </strong>

                            </div>


                            <div>

                                <span>
                                    p-value
                                </span>

                                <strong>
                                    <?php
                                    echo analyticsPValue(
                                        analyticsValue(
                                            $row,
                                            "P_Value"
                                        )
                                    );
                                    ?>
                                </strong>

                            </div>


                            <div>

                                <span>
                                    Decision
                                </span>

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $decision
                                    );
                                    ?>
                                </strong>

                            </div>


                        </div>


                        <p class="analytics-v2-test-interpretation">

                            <?php
                            echo htmlspecialchars(
                                analyticsValue(
                                    $row,
                                    "Interpretation"
                                )
                            );
                            ?>

                        </p>


                    </article>


                <?php endforeach; ?>


            </div>


            <!-- POST-HOC -->

            <article class="analytics-v2-card">

                <div class="analytics-v2-card-header">

                    <div>

                        <h3>
                            Subject Post-hoc Analysis
                        </h3>

                        <p>
                            Pairwise Wilcoxon signed-rank tests
                            with Holm correction following the
                            significant Friedman test.
                        </p>

                    </div>


                    <span class="analytics-v2-count">

                        <?php
                        echo count(
                            $posthocRows
                        );
                        ?>
                        comparisons

                    </span>

                </div>


                <div class="analytics-v2-table-scroll">

                    <table class="analytics-v2-table">

                        <thead>

                            <tr>
                                <th>Subject A</th>
                                <th>Subject B</th>
                                <th>Attendance A</th>
                                <th>Attendance B</th>
                                <th>Adjusted p-value</th>
                                <th>Decision</th>
                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach ($posthocRows as $row): ?>

                                <?php

                                $posthocDecision =
                                    analyticsValue(
                                        $row,
                                        "Decision"
                                    );

                                ?>


                                <tr>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            analyticsValue(
                                                $row,
                                                "Subject_A"
                                            )
                                        );
                                        ?>
                                    </td>


                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            analyticsValue(
                                                $row,
                                                "Subject_B"
                                            )
                                        );
                                        ?>
                                    </td>


                                    <td>
                                        <?php
                                        echo analyticsRate(
                                            analyticsValue(
                                                $row,
                                                "Mean_Attendance_A",
                                                0
                                            )
                                        );
                                        ?>
                                    </td>


                                    <td>
                                        <?php
                                        echo analyticsRate(
                                            analyticsValue(
                                                $row,
                                                "Mean_Attendance_B",
                                                0
                                            )
                                        );
                                        ?>
                                    </td>


                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            analyticsValue(
                                                $row,
                                                "Adjusted_P_Value_Display"
                                            )
                                        );
                                        ?>
                                    </td>


                                    <td>

                                        <span
                                            class="<?php
                                            echo
                                                $posthocDecision
                                                ===
                                                "Significant"
                                                    ? "analytics-v2-significant"
                                                    : "analytics-v2-not-significant";
                                            ?>"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $posthocDecision
                                            );
                                            ?>

                                        </span>

                                    </td>

                                </tr>


                            <?php endforeach; ?>


                        </tbody>

                    </table>

                </div>

            </article>


            <div class="analytics-v2-footnote">

                <strong>
                    Development Note:
                </strong>

                These statistical results are based on
                synthetic development data and are not
                actual ENHS research findings.

            </div>


        </section>


    </main>

</div>

</body>

</html>