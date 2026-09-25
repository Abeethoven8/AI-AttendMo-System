<?php

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/db.php";


if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Student") {

    header("Location: login.php");
    exit;

}



$user_id = $_SESSION["user_id"];



// GET STUDENT ID

$stmt = $pdo->prepare("
    SELECT student_id
    FROM students
    WHERE user_id = ?
");

$stmt->execute([$user_id]);

$student = $stmt->fetch();



if(!$student){

    die("Student record not found.");

}


$student_id = $student["student_id"];




// GET ACADEMIC RECORDS

$stmt = $pdo->prepare("
    SELECT 
        subject,
        quarter,
        grade
    FROM academic_records
    WHERE student_id = ?
    ORDER BY subject ASC
");


$stmt->execute([$student_id]);


$academic_records = $stmt->fetchAll();




// COMPUTE GWA

$total_grade = 0;
$total_subjects = count($academic_records);


foreach($academic_records as $record){

    $total_grade += $record["grade"];

}



if($total_subjects > 0){

    $gwa = round($total_grade / $total_subjects, 2);

}
else{

    $gwa = 0;

}



// ACADEMIC STATUS

if($gwa >= 90){

    $academic_status = "Excellent";

}
elseif($gwa >= 85){

    $academic_status = "Good";

}
else{

    $academic_status = "Needs Improvement";

}


?>



<!DOCTYPE html>
<html>

<head>

<title>
Academic Performance | AI Attend Mo
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
Academic Performance
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







<div class="summary-grid">



<div class="summary-card">

<p>
Current Average Grade
</p>


<h2>
<?php echo $gwa; ?>
</h2>


<span>
Overall academic performance
</span>


</div>





<div class="summary-card">

<p>
Academic Status
</p>


<h2>
<?php echo $academic_status; ?>
</h2>


<span>
Current standing
</span>


</div>





<div class="summary-card">

<p>
Subjects Recorded
</p>


<h2>
<?php echo $total_subjects; ?>
</h2>


<span>
Academic records
</span>


</div>





<div class="summary-card">

<p>
Risk Level
</p>


<h2>
Medium
</h2>


<span>
AI assessment
</span>


</div>



</div>







<div class="dashboard-panel">


<div class="panel-header">


<div>


<p class="panel-label">
ACADEMIC RECORDS
</p>


<h3>
Subject Performance
</h3>


</div>


</div>







<div class="attendance-table-wrapper">


<table class="attendance-table">


<thead>


<tr>

<th>
Subject
</th>


<th>
Quarter
</th>


<th>
Grade
</th>


<th>
Remarks
</th>


</tr>


</thead>




<tbody>



<?php foreach($academic_records as $row): ?>


<tr>


<td>
<?php echo htmlspecialchars($row["subject"]); ?>
</td>


<td>
<?php echo htmlspecialchars($row["quarter"]); ?>
</td>


<td>
<?php echo $row["grade"]; ?>
</td>


<td>


<?php

if($row["grade"] >= 90){

    echo "Excellent";

}
elseif($row["grade"] >= 85){

    echo "Very Good";

}
else{

    echo "Good";

}

?>


</td>


</tr>


<?php endforeach; ?>





<?php if(count($academic_records)==0): ?>


<tr>

<td colspan="4">
No academic records found.
</td>

</tr>


<?php endif; ?>



</tbody>


</table>


</div>


</div>








<div class="dashboard-panel">


<div class="panel-header">


<div>


<p class="panel-label">
AI SUPPORT
</p>


<h3>
Academic Insight
</h3>


</div>


</div>




<p>

Academic performance data is presented together with attendance information to support student monitoring and decision-making.

</p>



</div>







</main>


</div>


</body>


</html>