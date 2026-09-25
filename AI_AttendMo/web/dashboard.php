<?php

require_once __DIR__ . "/includes/auth.php";

$pageTitle = "Dashboard";

require_once __DIR__
    . "/includes/report_data.php";

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function formatDashboardNumber($value)
{
    if ($value === "—" || !is_numeric($value)) {
        return "—";
    }

    return number_format((float)$value, 0);
}


function dashboardNormalizeDate($value)
{
    $value = trim((string)$value);

    if ($value === "") {
        return "";
    }

    $timestamp = strtotime($value);

    if ($timestamp === false) {
        return "";
    }

    return date("Y-m-d", $timestamp);
}


function dashboardGradeLabel($grade)
{
    $grade = trim((string)$grade);

    if ($grade === "") {
        return "";
    }

    if (preg_match("/^grade\s+/i", $grade)) {
        return $grade;
    }

    if (is_numeric($grade)) {
        return "Grade " . $grade;
    }

    return $grade;
}


/*
|--------------------------------------------------------------------------
| ATTENDANCE TREND FILTERS
|--------------------------------------------------------------------------
*/

$selectedGrade =
    trim($_GET["grade"] ?? "");

$selectedSection =
    trim($_GET["section"] ?? "");

$selectedSubject =
    trim($_GET["subject"] ?? "");

$selectedDateFrom =
    dashboardNormalizeDate(
        $_GET["date_from"] ?? ""
    );

$selectedDateTo =
    dashboardNormalizeDate(
        $_GET["date_to"] ?? ""
    );


/*
|--------------------------------------------------------------------------
| FIX REVERSED DATE RANGE
|--------------------------------------------------------------------------
*/

if (
    $selectedDateFrom !== ""
    &&
    $selectedDateTo !== ""
    &&
    $selectedDateFrom > $selectedDateTo
) {

    $temporaryDate =
        $selectedDateFrom;

    $selectedDateFrom =
        $selectedDateTo;

    $selectedDateTo =
        $temporaryDate;
}


/*
|--------------------------------------------------------------------------
| READ CLEAN ATTENDANCE DATA
|--------------------------------------------------------------------------
*/

$attendanceFile =
    __DIR__
    . "/../data/attendance_cleaned.csv";


$gradeValues = [];
$sectionValues = [];
$subjectValues = [];

$sectionsByGrade = [];

$attendanceByDate = [];

$availableMinDate = "";
$availableMaxDate = "";

$filteredSessions = 0;
$filteredPresent = 0;
$filteredAbsent = 0;

$trendCsvError = "";

$usingRawAttendanceTrend = false;


if (is_file($attendanceFile)) {

    $handle =
        fopen(
            $attendanceFile,
            "r"
        );


    if ($handle !== false) {

        $headers =
            fgetcsv($handle);


        if ($headers !== false) {

            /*
            |--------------------------------------------------------------------------
            | REMOVE POSSIBLE UTF-8 BOM
            |--------------------------------------------------------------------------
            */

            if (isset($headers[0])) {

                $headers[0] =
                    preg_replace(
                        "/^\xEF\xBB\xBF/",
                        "",
                        $headers[0]
                    );
            }


            $headerIndex =
                array_flip(
                    $headers
                );


            $requiredColumns = [
                "Grade_Level",
                "Section",
                "Subject",
                "Date",
                "Attendance_Status"
            ];


            $columnsValid = true;


            foreach ($requiredColumns as $column) {

                if (
                    !isset(
                        $headerIndex[$column]
                    )
                ) {

                    $columnsValid = false;

                    break;
                }
            }


            if ($columnsValid) {

                $usingRawAttendanceTrend = true;


                while (
                    (
                        $row =
                            fgetcsv($handle)
                    )
                    !== false
                ) {

                    $grade =
                        trim(
                            $row[
                                $headerIndex[
                                    "Grade_Level"
                                ]
                            ]
                            ?? ""
                        );


                    $section =
                        trim(
                            $row[
                                $headerIndex[
                                    "Section"
                                ]
                            ]
                            ?? ""
                        );


                    $subject =
                        trim(
                            $row[
                                $headerIndex[
                                    "Subject"
                                ]
                            ]
                            ?? ""
                        );


                    $date =
                        dashboardNormalizeDate(
                            $row[
                                $headerIndex[
                                    "Date"
                                ]
                            ]
                            ?? ""
                        );


                    $status =
                        strtolower(
                            trim(
                                $row[
                                    $headerIndex[
                                        "Attendance_Status"
                                    ]
                                ]
                                ?? ""
                            )
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | FILTER OPTION VALUES
                    |--------------------------------------------------------------------------
                    */

                    if ($grade !== "") {

                        $gradeValues[
                            $grade
                        ] = true;
                    }


                    if ($section !== "") {

                        $sectionValues[
                            $section
                        ] = true;
                    }


                    if ($subject !== "") {

                        $subjectValues[
                            $subject
                        ] = true;
                    }


                    if (
                        $grade !== ""
                        &&
                        $section !== ""
                    ) {

                        if (
                            !isset(
                                $sectionsByGrade[
                                    $grade
                                ]
                            )
                        ) {

                            $sectionsByGrade[
                                $grade
                            ] = [];
                        }


                        $sectionsByGrade[
                            $grade
                        ][
                            $section
                        ] = true;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | AVAILABLE DATE RANGE
                    |--------------------------------------------------------------------------
                    */

                    if ($date !== "") {

                        if (
                            $availableMinDate === ""
                            ||
                            $date < $availableMinDate
                        ) {

                            $availableMinDate =
                                $date;
                        }


                        if (
                            $availableMaxDate === ""
                            ||
                            $date > $availableMaxDate
                        ) {

                            $availableMaxDate =
                                $date;
                        }
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | APPLY GRADE FILTER
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $selectedGrade !== ""
                        &&
                        $grade !== $selectedGrade
                    ) {

                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | APPLY SECTION FILTER
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $selectedSection !== ""
                        &&
                        $section !== $selectedSection
                    ) {

                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | APPLY SUBJECT FILTER
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $selectedSubject !== ""
                        &&
                        $subject !== $selectedSubject
                    ) {

                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | APPLY DATE FROM FILTER
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $selectedDateFrom !== ""
                        &&
                        (
                            $date === ""
                            ||
                            $date < $selectedDateFrom
                        )
                    ) {

                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | APPLY DATE TO FILTER
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $selectedDateTo !== ""
                        &&
                        (
                            $date === ""
                            ||
                            $date > $selectedDateTo
                        )
                    ) {

                        continue;
                    }


                    if ($date === "") {

                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | DAILY AGGREGATION
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !isset(
                            $attendanceByDate[
                                $date
                            ]
                        )
                    ) {

                        $attendanceByDate[
                            $date
                        ] = [
                            "total" => 0,
                            "present" => 0,
                            "absent" => 0
                        ];
                    }


                    $attendanceByDate[
                        $date
                    ]["total"]++;


                    $filteredSessions++;


                    if ($status === "present") {

                        $attendanceByDate[
                            $date
                        ]["present"]++;

                        $filteredPresent++;

                    } elseif ($status === "absent") {

                        $attendanceByDate[
                            $date
                        ]["absent"]++;

                        $filteredAbsent++;
                    }
                }

            } else {

                $trendCsvError =
                    "The cleaned attendance file does not contain all required trend columns.";
            }

        } else {

            $trendCsvError =
                "The cleaned attendance file is empty.";
        }


        fclose($handle);

    } else {

        $trendCsvError =
            "The cleaned attendance file could not be opened.";
    }

} else {

    $trendCsvError =
        "The cleaned attendance file is not available.";
}


/*
|--------------------------------------------------------------------------
| SORT FILTER OPTIONS
|--------------------------------------------------------------------------
*/

$gradeOptions =
    array_keys(
        $gradeValues
    );

natsort(
    $gradeOptions
);

$gradeOptions =
    array_values(
        $gradeOptions
    );


/*
|--------------------------------------------------------------------------
| SECTION OPTIONS
|--------------------------------------------------------------------------
*/

if (
    $selectedGrade !== ""
    &&
    isset(
        $sectionsByGrade[
            $selectedGrade
        ]
    )
) {

    $sectionOptions =
        array_keys(
            $sectionsByGrade[
                $selectedGrade
            ]
        );

} else {

    $sectionOptions =
        array_keys(
            $sectionValues
        );
}


natsort(
    $sectionOptions
);

$sectionOptions =
    array_values(
        $sectionOptions
    );


/*
|--------------------------------------------------------------------------
| SUBJECT OPTIONS
|--------------------------------------------------------------------------
*/

$subjectOptions =
    array_keys(
        $subjectValues
    );

natsort(
    $subjectOptions
);

$subjectOptions =
    array_values(
        $subjectOptions
    );


/*
|--------------------------------------------------------------------------
| BUILD TREND DATA
|--------------------------------------------------------------------------
*/

$trendData = [];


if ($usingRawAttendanceTrend) {

    ksort(
        $attendanceByDate
    );


    foreach (
        $attendanceByDate
        as $date => $counts
    ) {

        if (
            $counts["total"] <= 0
        ) {

            continue;
        }


        $rate =
            (
                $counts["present"]
                /
                $counts["total"]
            )
            * 100;


        $trendData[] = [

            "date" =>
                $date,

            "label" =>
                date(
                    "M j",
                    strtotime($date)
                ),

            "attendance_rate" =>
                round(
                    $rate,
                    4
                ),

            "total_sessions" =>
                $counts["total"],

            "present" =>
                $counts["present"],

            "absent" =>
                $counts["absent"]

        ];
    }

} else {

    /*
    |--------------------------------------------------------------------------
    | FALLBACK TO GENERATED REPORT
    |--------------------------------------------------------------------------
    */

    $trendData =
        $dailyTrend;
}


/*
|--------------------------------------------------------------------------
| FILTERED ATTENDANCE RATE
|--------------------------------------------------------------------------
*/

$filteredAttendanceRate = "—";


if (
    $filteredSessions > 0
) {

    $filteredAttendanceRate =
        number_format(
            (
                $filteredPresent
                /
                $filteredSessions
            )
            * 100,
            2
        )
        . "%";
}


/*
|--------------------------------------------------------------------------
| ACTIVE FILTER DESCRIPTION
|--------------------------------------------------------------------------
*/

$activeFilterParts = [];


if ($selectedGrade !== "") {

    $activeFilterParts[] =
        dashboardGradeLabel(
            $selectedGrade
        );
}


if ($selectedSection !== "") {

    $activeFilterParts[] =
        "Section "
        . $selectedSection;
}


if ($selectedSubject !== "") {

    $activeFilterParts[] =
        $selectedSubject;
}


if (
    $selectedDateFrom !== ""
    ||
    $selectedDateTo !== ""
) {

    $dateDescription =
        "Date: ";


    if ($selectedDateFrom !== "") {

        $dateDescription .=
            date(
                "M j, Y",
                strtotime(
                    $selectedDateFrom
                )
            );

    } else {

        $dateDescription .=
            "Beginning";
    }


    $dateDescription .=
        " – ";


    if ($selectedDateTo !== "") {

        $dateDescription .=
            date(
                "M j, Y",
                strtotime(
                    $selectedDateTo
                )
            );

    } else {

        $dateDescription .=
            "Latest";
    }


    $activeFilterParts[] =
        $dateDescription;
}


$activeFilterText =
    empty(
        $activeFilterParts
    )
        ? "All Grades • All Sections • All Subjects • Full Date Range"
        : implode(
            " • ",
            $activeFilterParts
        );


/*
|--------------------------------------------------------------------------
| ATTENDANCE TREND SVG DATA
|--------------------------------------------------------------------------
*/

$chartWidth = 1000;
$chartHeight = 330;

$chartLeft = 65;
$chartRight = 25;
$chartTop = 25;
$chartBottom = 55;

$plotWidth =
    $chartWidth
    - $chartLeft
    - $chartRight;

$plotHeight =
    $chartHeight
    - $chartTop
    - $chartBottom;


$trendRates =
    array_column(
        $trendData,
        "attendance_rate"
    );


$trendPoints = [];

$yMin = 0;
$yMax = 100;


if (
    !empty(
        $trendRates
    )
) {

    $dataMin =
        min(
            $trendRates
        );

    $dataMax =
        max(
            $trendRates
        );


    $yMin =
        max(
            0,
            floor(
                ($dataMin - 2)
                / 5
            )
            * 5
        );


    $yMax =
        min(
            100,
            ceil(
                ($dataMax + 2)
                / 5
            )
            * 5
        );


    if (
        $yMax <= $yMin
    ) {

        $yMax =
            min(
                100,
                $yMin + 5
            );


        if (
            $yMax <= $yMin
        ) {

            $yMin =
                max(
                    0,
                    $yMax - 5
                );
        }
    }


    $count =
        count(
            $trendData
        );


    foreach (
        $trendData
        as $index => $item
    ) {

        if ($count > 1) {

            $x =
                $chartLeft
                +
                (
                    $index
                    /
                    ($count - 1)
                )
                * $plotWidth;

        } else {

            $x =
                $chartLeft
                +
                (
                    $plotWidth
                    / 2
                );
        }


        $rate =
            (float)
            $item[
                "attendance_rate"
            ];


        $range =
            $yMax - $yMin;


        if ($range <= 0) {

            $range = 1;
        }


        $y =
            $chartTop
            +
            (
                (
                    $yMax - $rate
                )
                /
                $range
            )
            * $plotHeight;


        $trendPoints[] = [

            "x" =>
                round(
                    $x,
                    2
                ),

            "y" =>
                round(
                    $y,
                    2
                ),

            "rate" =>
                $rate,

            "label" =>
                $item["label"],

            "date" =>
                $item["date"]
                ?? $item["label"]

        ];
    }
}


/*
|--------------------------------------------------------------------------
| POLYLINE
|--------------------------------------------------------------------------
*/

$polylinePoints =
    implode(

        " ",

        array_map(

            function ($point) {

                return
                    $point["x"]
                    . ","
                    . $point["y"];
            },

            $trendPoints
        )
    );


/*
|--------------------------------------------------------------------------
| GRADIENT AREA
|--------------------------------------------------------------------------
*/

$areaPoints = "";


if (
    !empty(
        $trendPoints
    )
) {

    $firstPoint =
        $trendPoints[0];


    $lastPoint =
        $trendPoints[
            count(
                $trendPoints
            ) - 1
        ];


    $chartBottomY =
        $chartTop
        + $plotHeight;


    $areaPoints =
        $firstPoint["x"]
        . ","
        . $chartBottomY
        . " "
        . $polylinePoints
        . " "
        . $lastPoint["x"]
        . ","
        . $chartBottomY;
}


/*
|--------------------------------------------------------------------------
| X-AXIS LABELS
|--------------------------------------------------------------------------
*/

$xLabelIndexes = [];


if (
    count(
        $trendData
    ) > 0
) {

    $lastIndex =
        count(
            $trendData
        ) - 1;


    $xLabelIndexes =
        array_unique([
            0,
            (int)round(
                $lastIndex
                * 0.25
            ),
            (int)round(
                $lastIndex
                * 0.50
            ),
            (int)round(
                $lastIndex
                * 0.75
            ),
            $lastIndex
        ]);
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
        AI: Attend Mo | Dashboard
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
                class="nav-link active"
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


    <!-- MAIN CONTENT -->

    <main class="main-content">


        <!-- TOP BAR -->

        <header class="topbar">

            <div>

                <p class="page-label">
                    OVERVIEW
                </p>

                <h1>
                    Dashboard
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
                Development Mode
            </strong>

            <p>
                Dashboard values are automatically loaded
                from generated analytics reports.
                Current records and results are based on
                synthetic development data only.
            </p>

        </section>


        <!-- SUMMARY CARDS -->

        <section class="summary-grid">

            <article class="summary-card">

                <p>
                    Total Students
                </p>

                <h2>
                    <?php
                    echo htmlspecialchars(
                        formatDashboardNumber(
                            $totalStudents
                        )
                    );
                    ?>
                </h2>

                <span>
                    Students analyzed
                </span>

            </article>


            <article class="summary-card">

                <p>
                    Overall Attendance
                </p>

                <h2>
                    <?php
                    echo htmlspecialchars(
                        $attendanceRate
                    );
                    ?>
                </h2>

                <span>
                    Overall attendance rate
                </span>

            </article>


            <article class="summary-card">

                <p>
                    Total Present
                </p>

                <h2>
                    <?php
                    echo htmlspecialchars(
                        formatDashboardNumber(
                            $totalPresent
                        )
                    );
                    ?>
                </h2>

                <span>
                    Present records
                </span>

            </article>


            <article class="summary-card">

                <p>
                    Total Absent
                </p>

                <h2>
                    <?php
                    echo htmlspecialchars(
                        formatDashboardNumber(
                            $totalAbsent
                        )
                    );
                    ?>
                </h2>

                <span>
                    Absent records
                </span>

            </article>

        </section>


        <!-- DASHBOARD CONTENT -->

        <section class="dashboard-grid">


            <!-- ATTENDANCE TREND -->

            <article
                class="dashboard-panel large-panel"
            >

                <div class="panel-header">

                    <div>

                        <p class="panel-label">
                            DESCRIPTIVE ANALYTICS
                        </p>

                        <h3>
                            Attendance Trend
                        </h3>

                    </div>


                    <a href="analytics.php">
                        View Analytics
                    </a>

                </div>


                <!-- AUTOMATIC TREND FILTERS -->

                <form
                    method="GET"
                    action="dashboard.php"
                    class="trend-filter-form"
                    id="trendFilterForm"
                >

                    <div class="trend-filter-grid">


                        <!-- GRADE -->

                        <div class="trend-filter-control">

                            <label for="grade">
                                Grade Level
                            </label>

                            <select
                                name="grade"
                                id="grade"
                            >

                                <option value="">
                                    All Grades
                                </option>

                                <?php foreach ($gradeOptions as $grade): ?>

                                    <option
                                        value="<?php
                                        echo htmlspecialchars(
                                            $grade
                                        );
                                        ?>"
                                        <?php
                                        echo
                                            $selectedGrade === $grade
                                                ? "selected"
                                                : "";
                                        ?>
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            dashboardGradeLabel(
                                                $grade
                                            )
                                        );
                                        ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <!-- SECTION -->

                        <div class="trend-filter-control">

                            <label for="section">
                                Section
                            </label>

                            <select
                                name="section"
                                id="section"
                            >

                                <option value="">
                                    All Sections
                                </option>

                                <?php foreach ($sectionOptions as $section): ?>

                                    <option
                                        value="<?php
                                        echo htmlspecialchars(
                                            $section
                                        );
                                        ?>"
                                        <?php
                                        echo
                                            $selectedSection === $section
                                                ? "selected"
                                                : "";
                                        ?>
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $section
                                        );
                                        ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <!-- SUBJECT -->

                        <div class="trend-filter-control">

                            <label for="subject">
                                Subject
                            </label>

                            <select
                                name="subject"
                                id="subject"
                            >

                                <option value="">
                                    All Subjects
                                </option>

                                <?php foreach ($subjectOptions as $subject): ?>

                                    <option
                                        value="<?php
                                        echo htmlspecialchars(
                                            $subject
                                        );
                                        ?>"
                                        <?php
                                        echo
                                            $selectedSubject === $subject
                                                ? "selected"
                                                : "";
                                        ?>
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $subject
                                        );
                                        ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <!-- DATE FROM -->

                        <div class="trend-filter-control">

                            <label for="date_from">
                                Date From
                            </label>

                            <input
                                type="date"
                                name="date_from"
                                id="date_from"

                                value="<?php
                                echo htmlspecialchars(
                                    $selectedDateFrom
                                );
                                ?>"

                                <?php if ($availableMinDate !== ""): ?>

                                    min="<?php
                                    echo htmlspecialchars(
                                        $availableMinDate
                                    );
                                    ?>"

                                <?php endif; ?>

                                <?php if ($availableMaxDate !== ""): ?>

                                    max="<?php
                                    echo htmlspecialchars(
                                        $availableMaxDate
                                    );
                                    ?>"

                                <?php endif; ?>
                            >

                        </div>


                        <!-- DATE TO -->

                        <div class="trend-filter-control">

                            <label for="date_to">
                                Date To
                            </label>

                            <input
                                type="date"
                                name="date_to"
                                id="date_to"

                                value="<?php
                                echo htmlspecialchars(
                                    $selectedDateTo
                                );
                                ?>"

                                <?php if ($availableMinDate !== ""): ?>

                                    min="<?php
                                    echo htmlspecialchars(
                                        $availableMinDate
                                    );
                                    ?>"

                                <?php endif; ?>

                                <?php if ($availableMaxDate !== ""): ?>

                                    max="<?php
                                    echo htmlspecialchars(
                                        $availableMaxDate
                                    );
                                    ?>"

                                <?php endif; ?>
                            >

                        </div>


                        <!-- CLEAR FILTER -->

                        <div class="trend-filter-actions">

                            <a
                                href="dashboard.php"
                                class="trend-filter-clear"
                            >
                                Clear Filters
                            </a>

                        </div>

                    </div>

                </form>


                <!-- ACTIVE FILTER STATUS -->

                <div class="trend-filter-status">

                    <div>

                        <span>
                            Showing
                        </span>

                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $activeFilterText
                            );
                            ?>
                        </strong>

                    </div>


                    <?php if ($usingRawAttendanceTrend): ?>

                        <div class="trend-filter-results">

                            <span>

                                <?php
                                echo number_format(
                                    $filteredSessions
                                );
                                ?>

                                records

                            </span>

                            <span>
                                •
                            </span>

                            <span>

                                Attendance:

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $filteredAttendanceRate
                                    );
                                    ?>
                                </strong>

                            </span>

                        </div>

                    <?php endif; ?>

                </div>


                <?php if ($trendCsvError !== ""): ?>

                    <div class="trend-filter-warning">

                        <?php
                        echo htmlspecialchars(
                            $trendCsvError
                        );
                        ?>

                        Showing the available generated
                        overall trend instead.

                    </div>

                <?php endif; ?>


                <!-- LINE CHART -->

                <div class="trend-chart-container">


                    <?php if (!empty($trendPoints)): ?>

                        <svg
                            class="trend-chart"
                            viewBox="0 0 1000 330"
                            role="img"
                            aria-label="Filtered daily attendance rate trend"
                        >

                            <defs>

                                <linearGradient
                                    id="attendanceGradient"
                                    x1="0"
                                    y1="0"
                                    x2="0"
                                    y2="1"
                                >

                                    <stop
                                        offset="0%"
                                        stop-color="#155EEF"
                                        stop-opacity="0.28"
                                    />

                                    <stop
                                        offset="55%"
                                        stop-color="#4F8EF7"
                                        stop-opacity="0.12"
                                    />

                                    <stop
                                        offset="100%"
                                        stop-color="#EAF1FF"
                                        stop-opacity="0.02"
                                    />

                                </linearGradient>

                            </defs>


                            <!-- Y GRID + LABELS -->

                            <?php for ($i = 0; $i <= 4; $i++): ?>

                                <?php

                                $gridY =
                                    $chartTop
                                    +
                                    (
                                        $plotHeight
                                        *
                                        ($i / 4)
                                    );


                                $gridValue =
                                    $yMax
                                    -
                                    (
                                        ($yMax - $yMin)
                                        *
                                        ($i / 4)
                                    );

                                ?>

                                <line
                                    x1="<?php
                                    echo $chartLeft;
                                    ?>"
                                    y1="<?php
                                    echo $gridY;
                                    ?>"
                                    x2="<?php
                                    echo
                                        $chartWidth
                                        - $chartRight;
                                    ?>"
                                    y2="<?php
                                    echo $gridY;
                                    ?>"
                                    class="chart-grid-line"
                                />


                                <text
                                    x="<?php
                                    echo
                                        $chartLeft
                                        - 12;
                                    ?>"
                                    y="<?php
                                    echo
                                        $gridY
                                        + 4;
                                    ?>"
                                    text-anchor="end"
                                    class="chart-axis-text"
                                >

                                    <?php
                                    echo number_format(
                                        $gridValue,
                                        1
                                    );
                                    ?>%

                                </text>

                            <?php endfor; ?>


                            <!-- X LABELS -->

                            <?php foreach ($xLabelIndexes as $index): ?>

                                <?php

                                $point =
                                    $trendPoints[
                                        $index
                                    ];

                                ?>

                                <text
                                    x="<?php
                                    echo
                                        $point["x"];
                                    ?>"
                                    y="<?php
                                    echo
                                        $chartHeight
                                        - 20;
                                    ?>"
                                    text-anchor="middle"
                                    class="chart-axis-text"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $point["label"]
                                    );
                                    ?>

                                </text>

                            <?php endforeach; ?>


                            <!-- GRADIENT AREA -->

                            <polygon
                                points="<?php
                                echo
                                    $areaPoints;
                                ?>"
                                class="chart-trend-area"
                            />


                            <!-- TREND LINE -->

                            <polyline
                                points="<?php
                                echo
                                    $polylinePoints;
                                ?>"
                                class="chart-trend-line"
                            />


                            <!-- DATA POINTS -->

                            <?php foreach ($trendPoints as $point): ?>

                                <circle
                                    cx="<?php
                                    echo
                                        $point["x"];
                                    ?>"
                                    cy="<?php
                                    echo
                                        $point["y"];
                                    ?>"
                                    r="3.5"
                                    class="chart-data-point"
                                >

                                    <title><?php
                                        echo htmlspecialchars(
                                            $point["label"]
                                        );
                                        ?> - <?php
                                        echo number_format(
                                            $point["rate"],
                                            2
                                        );
                                        ?>%</title>

                                </circle>

                            <?php endforeach; ?>

                        </svg>


                        <div class="chart-note">

                            Daily attendance rate based on
                            filtered synthetic development
                            records.

                        </div>


                    <?php else: ?>

                        <div class="chart-no-data">

                            <strong>
                                No attendance data found.
                            </strong>

                            <span>
                                Try changing or clearing
                                the selected filters.
                            </span>

                        </div>

                    <?php endif; ?>


                </div>

            </article>


            <!-- RISK PANEL -->

            <article class="dashboard-panel">

                <div class="panel-header">

                    <div>

                        <p class="panel-label">
                            PREDICTIVE ANALYTICS
                        </p>

                        <h3>
                            Absenteeism Risk
                        </h3>

                    </div>


                    <a href="prediction.php">
                        View Prediction
                    </a>

                </div>


                <div class="risk-preview">

                    <div>

                        <span>
                            High Risk
                        </span>

                        <strong>
                            <?php
                            echo $highRiskCount;
                            ?>
                        </strong>

                    </div>


                    <div>

                        <span>
                            Moderate Risk
                        </span>

                        <strong>
                            <?php
                            echo $moderateRiskCount;
                            ?>
                        </strong>

                    </div>


                    <div>

                        <span>
                            Low Risk
                        </span>

                        <strong>
                            <?php
                            echo $lowRiskCount;
                            ?>
                        </strong>

                    </div>

                </div>

            </article>


            <!-- RECOMMENDATIONS PANEL -->

            <article class="dashboard-panel">

                <div class="panel-header">

                    <div>

                        <p class="panel-label">
                            DECISION SUPPORT
                        </p>

                        <h3>
                            Recommendations
                        </h3>

                    </div>

                </div>


                <div class="recommendation-preview">

                    <p>

                        Decision-support recommendations
                        are currently available for

                        <strong>
                            <?php
                            echo
                                $totalRecommendationStudents;
                            ?>
                        </strong>

                        test student records.

                    </p>


                    <p>

                        Recommendations are based on
                        predicted absenteeism risk and
                        historical attendance trends.

                    </p>


                    <a href="recommendations.php">
                        Open Recommendations
                    </a>

                </div>

            </article>

        </section>

    </main>

</div>


<!-- =========================================================
     AUTOMATIC ATTENDANCE TREND FILTERING
     ========================================================= -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const form =
            document.getElementById(
                "trendFilterForm"
            );


        if (!form) {
            return;
        }


        const grade =
            document.getElementById(
                "grade"
            );


        const section =
            document.getElementById(
                "section"
            );


        const subject =
            document.getElementById(
                "subject"
            );


        const dateFrom =
            document.getElementById(
                "date_from"
            );


        const dateTo =
            document.getElementById(
                "date_to"
            );


        /*
        |--------------------------------------------------------------------------
        | SUBMIT FILTERS
        |--------------------------------------------------------------------------
        */

        function submitTrendFilters()
        {
            form.submit();
        }


        /*
        |--------------------------------------------------------------------------
        | GRADE
        |--------------------------------------------------------------------------
        |
        | When Grade changes, Section is reset first because
        | the available Section options depend on the Grade.
        |
        */

        if (grade) {

            grade.addEventListener(
                "change",
                function () {

                    if (section) {

                        section.value = "";
                    }


                    submitTrendFilters();
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | SECTION
        |--------------------------------------------------------------------------
        */

        if (section) {

            section.addEventListener(
                "change",
                function () {

                    submitTrendFilters();
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | SUBJECT
        |--------------------------------------------------------------------------
        */

        if (subject) {

            subject.addEventListener(
                "change",
                function () {

                    submitTrendFilters();
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | DATE FROM
        |--------------------------------------------------------------------------
        */

        if (dateFrom) {

            dateFrom.addEventListener(
                "change",
                function () {

                    submitTrendFilters();
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | DATE TO
        |--------------------------------------------------------------------------
        */

        if (dateTo) {

            dateTo.addEventListener(
                "change",
                function () {

                    submitTrendFilters();
                }
            );
        }

    }
);

</script>


</body>

</html>