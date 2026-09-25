<?php

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/db.php";


if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Student") {

    header("Location: login.php");
    exit;

}



// GET LOGGED-IN USER

$user_id = $_SESSION["user_id"];




// GET STUDENT INFORMATION

$stmt = $pdo->prepare("
    SELECT student_id
    FROM students
    WHERE user_id = ?
");

$stmt->execute([$user_id]);

$student = $stmt->fetch();



if (!$student) {

    die("Student profile not found.");

}


$student_id = $student["student_id"];





// GET ATTENDANCE RECORDS

$stmt = $pdo->prepare("
    SELECT status
    FROM attendance
    WHERE student_id = ?
");


$stmt->execute([$student_id]);


$attendance_records = $stmt->fetchAll();
$present_count = 0;
$absent_count = 0;





// COMPUTE ATTENDANCE SUMMARY

$total_records = count($attendance_records);

$present_days = 0;

$absent_days = 0;



foreach ($attendance_records as $record) {


    if ($record["status"] === "Present") {

        $present_days++;
        $present_count++;

    } else {

        $absent_days++;
        $absent_count++;

    }

}




if ($total_records > 0) {

    $attendance_rate =
        round(
            ($present_days / $total_records) * 100,
            2
        );

} else {

    $attendance_rate = 0;

}





// SIMPLE RISK CLASSIFICATION

if ($attendance_rate >= 90) {

    $risk_status = "Low";

}
elseif ($attendance_rate >= 75) {

    $risk_status = "Medium";

}
else {

    $risk_status = "High";

}


?>


<!DOCTYPE html>
<html>

<head>

<title>
AI: Attend Mo | Student Dashboard
</title>

<link rel="stylesheet" href="assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>


<body class="dashboard-body">


<div class="app-layout">



<?php include __DIR__ . "/includes/student_sidebar.php"; ?>



<main class="main-content">



<div class="topbar">


<div>

<p class="page-label">
STUDENT PORTAL
</p>


<h1>
Dashboard
</h1>


</div>



<div class="topbar-user">


<div class="user-avatar">

<?php echo strtoupper(substr($_SESSION["full_name"],0,1)); ?>

</div>



<div>

<strong>
<?php echo $_SESSION["full_name"]; ?>
</strong>


<span>
Student Access
</span>


</div>


</div>


</div>





<div class="development-notice">


<strong>
Student Dashboard
</strong>


<p>
Your attendance records, automated insights, and attendance monitoring information are presented here.
</p>


</div>






<!-- SUMMARY CARDS -->


<div class="summary-grid">



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
Present Days
</p>


<h2>
<?php echo $present_days; ?>
</h2>


<span>
Recorded attendance
</span>


</div>





<div class="summary-card">

<p>
Absent Days
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
Risk Status
</p>


<h2>
<?php echo $risk_status; ?>
</h2>


<span>
Automated insight result
</span>


</div>



</div>

<!-- DASHBOARD CONTENT GRID -->


<div class="dashboard-grid">



<!-- ATTENDANCE ANALYTICS -->


<div class="dashboard-panel large-panel">


<div class="panel-header">


<div>

<div class="panel-label">
DESCRIPTIVE ANALYTICS
</div>


<h3>
My Attendance Overview
</h3>

</div>


</div>



<div class="chart-container">

<canvas id="attendanceChart"></canvas>

</div>


</div>




<!-- AI RISK -->


<div class="dashboard-panel">


<div class="panel-header">


<div>


<div class="panel-label">
AUTOMATED INSIGHT
</div>


<h3>
Attendance Risk Insight
</h3>


</div>


</div>





<div class="risk-preview">



<div>

<span>
Current Risk Level
</span>


<strong>
<?php echo $risk_status; ?> Risk
</strong>


</div>





<div>

<span>
Attendance Rate
</span>


<strong>
<?php echo $attendance_rate; ?>%
</strong>


</div>





<div>

<span>
Absent Records
</span>


<strong>
<?php echo $absent_days; ?>
</strong>


</div>





</div>



</div>








<!-- ACADEMIC PERFORMANCE -->


<div class="dashboard-panel">


<div class="panel-header">


<div>


<div class="panel-label">
ACADEMIC ANALYTICS
</div>


<h3>
Academic Performance
</h3>


</div>


</div>




<p>
GWA Summary
</p>


<p>
Attendance vs Academic Performance
</p>




</div>








<!-- RECOMMENDATION -->


<div class="dashboard-panel">


<div class="panel-header">


<div>


<div class="panel-label">
DECISION SUPPORT
</div>


<h3>
Recommendations
</h3>


</div>


</div>





<div class="recommendation-preview">


<?php if ($risk_status === "High"): ?>


<p>
Attendance patterns indicate a higher risk level. Continuous monitoring and appropriate support actions are recommended.
</p>


<?php elseif ($risk_status === "Medium"): ?>


<p>
Maintain regular attendance and avoid consecutive absences to improve academic performance.
</p>


<?php else: ?>


<p>
The student shows a consistent attendance pattern. Continue maintaining good attendance behavior.
</p>


<?php endif; ?>


</div>



</div>




</div>





</main>


</div>

<script>

const ctx = document.getElementById('attendanceChart');


new Chart(ctx, {

type: 'doughnut',

data: {

labels: [
'Present',
'Absent'
],

datasets: [{

data: [

<?php echo $present_count; ?>,

<?php echo $absent_count; ?>

]

}]

},

options: {
    responsive:true,
    maintainAspectRatio:false,

    plugins:{
        legend:{
            position:'right',
            align:'center',
            labels:{
                boxWidth:18,
                boxHeight:18,
                padding:15
            }
        }
    }
}

});


</script>


</body>


</html>


