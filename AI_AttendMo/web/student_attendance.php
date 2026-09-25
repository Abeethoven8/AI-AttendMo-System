<?php

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/db.php";


if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Student") {

    header("Location: login.php");
    exit;

}


// GET USER ID

$user_id = $_SESSION["user_id"];


// GET STUDENT ID

$stmt = $pdo->prepare("
    SELECT student_id
    FROM students
    WHERE user_id = ?
");

$stmt->execute([$user_id]);

$student = $stmt->fetch();


if (!$student){

    die("Student record not found.");

}


$student_id = $student["student_id"];




// GET ATTENDANCE RECORDS

$stmt = $pdo->prepare("
    SELECT 
        attendance_date,
        subject,
        status
    FROM attendance
    WHERE student_id = ?
    ORDER BY attendance_date ASC
");


$stmt->execute([$student_id]);


$attendance_records = $stmt->fetchAll();




// SUMMARY

$total_records = count($attendance_records);

$present_days = 0;
$absent_days = 0;



foreach($attendance_records as $row){


    if($row["status"] == "Present"){

        $present_days++;

    }
    else{

        $absent_days++;

    }

}




if($total_records > 0){

    $attendance_rate =
    round(($present_days / $total_records) * 100,2);

}
else{

    $attendance_rate = 0;

}


?>


<!DOCTYPE html>
<html>

<head>

<title>
My Attendance | AI Attend Mo
</title>


<link rel="stylesheet" href="assets/css/style.css">


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
My Attendance Records
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
Attendance Monitoring
</strong>


<p>
View your attendance history, attendance rate, and recorded attendance status per subject.
</p>


</div>






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
Completed attendance
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
Recorded absence
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









<div class="dashboard-panel">



<div class="panel-header">


<div>


<p class="panel-label">
ATTENDANCE RECORDS
</p>


<h3>
My Attendance Records
</h3>


</div>


</div>





<div class="attendance-table-wrapper">



<table class="attendance-table">


<thead>


<tr>

<th>
Date
</th>


<th>
Subject
</th>


<th>
Status
</th>


<th>
Remarks
</th>


</tr>


</thead>




<tbody>




<?php foreach($attendance_records as $row): ?>


<tr>



<td>

<?php echo htmlspecialchars($row["attendance_date"]); ?>

</td>




<td>

<?php echo htmlspecialchars($row["subject"]); ?>

</td>




<td>


<?php if($row["status"] == "Present"): ?>


<span class="attendance-status present">

Present

</span>


<?php else: ?>


<span class="attendance-status absent">

Absent

</span>


<?php endif; ?>


</td>




<td>


<?php 

echo ($row["status"] == "Present")
? "Attended"
: "Absent";

?>


</td>



</tr>



<?php endforeach; ?>





<?php if(count($attendance_records)==0): ?>


<tr>

<td colspan="4">

No attendance records found.

</td>

</tr>


<?php endif; ?>



</tbody>


</table>



</div>



</div>





</main>


</div>



</body>


</html>