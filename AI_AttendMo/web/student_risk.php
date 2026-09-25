<?php

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/db.php";


/*
|--------------------------------------------------------------------------
| STUDENT ACCESS ONLY
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Student") {

    header("Location: login.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| GET LOGGED-IN STUDENT
|--------------------------------------------------------------------------
*/

$user_id = $_SESSION["user_id"];


$stmt = $pdo->prepare("
    SELECT student_id
    FROM students
    WHERE user_id = ?
");

$stmt->execute([$user_id]);

$student = $stmt->fetch();


if (!$student) {

    die("Student record not found.");

}


$student_id = $student["student_id"];


/*
|--------------------------------------------------------------------------
| GET ATTENDANCE RECORDS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT status
    FROM attendance
    WHERE student_id = ?
");

$stmt->execute([$student_id]);

$attendance_records = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| ATTENDANCE SUMMARY
|--------------------------------------------------------------------------
*/

$total_records = count($attendance_records);

$present_days = 0;
$absent_days = 0;


foreach ($attendance_records as $record) {

    if ($record["status"] === "Present") {

        $present_days++;

    }
    elseif ($record["status"] === "Absent") {

        $absent_days++;

    }

}


if ($total_records > 0) {

    $attendance_rate = round(
        ($present_days / $total_records) * 100,
        2
    );

}
else {

    $attendance_rate = 0;

}


/*
|--------------------------------------------------------------------------
| TEMPORARY ATTENDANCE-BASED RISK CLASSIFICATION
|--------------------------------------------------------------------------
|
| IMPORTANT:
| These thresholds are temporary for system development.
| Replace this section later with the approved Decision Tree prediction.
|
*/

if ($attendance_rate >= 90) {

    $risk_status = "Low";
    $risk_color = "#067647";
    $risk_background = "#ECFDF3";

}
elseif ($attendance_rate >= 75) {

    $risk_status = "Medium";
    $risk_color = "#B54708";
    $risk_background = "#FFFAEB";

}
else {

    $risk_status = "High";
    $risk_color = "#B42318";
    $risk_background = "#FEF3F2";

}


/*
|--------------------------------------------------------------------------
| ATTENDANCE RATE FACTOR
|--------------------------------------------------------------------------
*/

if ($attendance_rate >= 90) {

    $attendance_factor = "Good";
    $attendance_description =
        "The student's attendance rate is currently high.";

}
elseif ($attendance_rate >= 75) {

    $attendance_factor = "Needs Monitoring";
    $attendance_description =
        "The student's attendance rate should be monitored.";

}
else {

    $attendance_factor = "At Risk";
    $attendance_description =
        "The student's attendance rate requires closer attention.";

}


/*
|--------------------------------------------------------------------------
| ABSENCE FACTOR
|--------------------------------------------------------------------------
*/

if ($absent_days == 0) {

    $absence_factor = "No Absences";
    $absence_description =
        "No absence has been recorded.";

}
elseif ($absent_days <= 2) {

    $absence_factor = "Monitor";
    $absence_description =
        "A small number of absences has been recorded.";

}
else {

    $absence_factor = "Needs Attention";
    $absence_description =
        "Multiple absences have been recorded and should be monitored.";

}


/*
|--------------------------------------------------------------------------
| TEMPORARY RECOMMENDATION
|--------------------------------------------------------------------------
*/

if ($risk_status === "High") {

    $recommendation =
        "Attendance requires closer monitoring. The student may be referred for appropriate intervention and attendance follow-up.";

}
elseif ($risk_status === "Medium") {

    $recommendation =
        "Continue monitoring attendance and avoid repeated or consecutive absences.";

}
else {

    $recommendation =
        "Continue regular attendance monitoring and maintain the current attendance pattern.";

}

?>


<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>
Attendance Risk Assessment | AI Attend Mo
</title>

<link rel="stylesheet" href="assets/css/style.css">

</head>


<body class="dashboard-body">


<div class="app-layout">


<?php include __DIR__ . "/includes/student_sidebar.php"; ?>



<main class="main-content">


<!-- TOP BAR -->

<div class="topbar">


<div>

<p class="page-label">
STUDENT PORTAL
</p>

<h1>
Attendance Risk Assessment
</h1>

</div>



<div class="topbar-user">


<div class="user-avatar">

<?php
echo strtoupper(
    substr(
        htmlspecialchars($_SESSION["full_name"]),
        0,
        1
    )
);
?>

</div>


<div>

<strong>

<?php
echo htmlspecialchars($_SESSION["full_name"]);
?>

</strong>

<span>
Student Access
</span>

</div>


</div>


</div>



<!-- DEVELOPMENT NOTICE -->

<div class="development-notice">

<strong>
Attendance Risk Monitoring
</strong>

<p>
This page currently uses attendance data for system development.
This page provides automated attendance insights based on recorded student attendance patterns.
</p>

</div>



<!-- SUMMARY CARDS -->

<div class="summary-grid">


<div class="summary-card">

<p>
Current Risk Level
</p>

<h2 style="color:<?php echo $risk_color; ?>;">

<?php echo htmlspecialchars($risk_status); ?>

</h2>

<span>
Automated attendance insight
</span>

</div>



<div class="summary-card">

<p>
Attendance Rate
</p>

<h2>

<?php echo $attendance_rate; ?>%

</h2>

<span>
Overall attendance
</span>

</div>



<div class="summary-card">

<p>
Absent Records
</p>

<h2>

<?php echo $absent_days; ?>

</h2>

<span>
Recorded absences
</span>

</div>



<div class="summary-card">

<p>
Total Records
</p>

<h2>

<?php echo $total_records; ?>

</h2>

<span>
Attendance entries
</span>

</div>


</div>



<!-- RISK RESULT -->

<div class="dashboard-panel">


<div class="panel-header">

<div>

<p class="panel-label">
AUTOMATED INSIGHT
</p>

<h3>
Attendance Risk Insight
</h3>

</div>

</div>



<div
style="
padding:20px;
background:<?php echo $risk_background; ?>;
border-radius:12px;
"
>

<h2
style="
color:<?php echo $risk_color; ?>;
margin-bottom:10px;
"
>

<?php echo strtoupper(htmlspecialchars($risk_status)); ?> RISK

</h2>


<p>

The system analyzed the student's recorded attendance patterns and generated an automated insight for attendance risk monitoring.

</p>

</div>


</div>



<!-- RISK FACTORS -->

<div class="dashboard-panel">


<div class="panel-header">

<div>

<p class="panel-label">
ATTENDANCE ANALYSIS
</p>

<h3>
Risk Factors
</h3>

</div>

</div>



<div class="attendance-table-wrapper">


<table class="attendance-table">


<thead>

<tr>

<th>
Factor
</th>

<th>
Status
</th>

<th>
Description
</th>

</tr>

</thead>


<tbody>


<tr>

<td>
Attendance Rate
</td>

<td>

<?php echo htmlspecialchars($attendance_factor); ?>

</td>

<td>

<?php echo htmlspecialchars($attendance_description); ?>

</td>

</tr>



<tr>

<td>
Absence Records
</td>

<td>

<?php echo htmlspecialchars($absence_factor); ?>

</td>

<td>

<?php echo htmlspecialchars($absence_description); ?>

</td>

</tr>



<tr>

<td>
Present Records
</td>

<td>

<?php echo $present_days; ?> Present

</td>

<td>

The student currently has
<?php echo $present_days; ?>
recorded present attendance entries.

</td>

</tr>


</tbody>


</table>


</div>


</div>



<!-- RECOMMENDATION -->

<div class="dashboard-panel">


<div class="panel-header">

<div>

<p class="panel-label">
DECISION SUPPORT
</p>

<h3>
Recommendation
</h3>

</div>

</div>


<p>

<?php echo htmlspecialchars($recommendation); ?>

</p>


</div>


</main>


</div>


</body>

</html>