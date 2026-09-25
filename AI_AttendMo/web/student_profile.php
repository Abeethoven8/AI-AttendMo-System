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
    SELECT 
        student_id,
        student_number,
        grade_level,
        section,
        gender
    FROM students
    WHERE user_id = ?
");


$stmt->execute([$user_id]);


$student = $stmt->fetch();



if(!$student){

    die("Student record not found.");

}


$student_id = $student["student_id"];
$student_number = $student["student_number"];
$grade_level = $student["grade_level"];
$section = $student["section"];
$gender = $student["gender"];




// GET ATTENDANCE SUMMARY

$stmt = $pdo->prepare("
    SELECT status
    FROM attendance
    WHERE student_id = ?
");


$stmt->execute([$student_id]);


$records = $stmt->fetchAll();



$total = count($records);

$present = 0;
$absent = 0;


foreach($records as $row){

    if($row["status"]=="Present"){

        $present++;

    }else{

        $absent++;

    }

}



if($total > 0){

    $attendance_rate = round(($present/$total)*100,2);

}else{

    $attendance_rate = 0;

}



// RISK

if($attendance_rate >= 90){

    $risk = "Low Risk";

}elseif($attendance_rate >=75){

    $risk = "Medium Risk";

}else{

    $risk = "High Risk";

}


?>


<!DOCTYPE html>
<html>

<head>

<title>My Profile | AI Attend Mo</title>

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
My Profile
</h1>

</div>



<div class="topbar-user">


<div class="user-avatar">

<?php echo strtoupper(substr($_SESSION["full_name"],0,1)); ?>

</div>



<div>

<strong>
<?php echo $_SESSION["username"]; ?>
</strong>


<span>
Student Access
</span>


</div>


</div>


</div>





<!-- PROFILE GRID -->

<div class="dashboard-grid">





<!-- PROFILE INFORMATION -->

<div class="dashboard-panel large-panel">


<div class="panel-header">

<div>

<p class="panel-label">
PROFILE INFORMATION
</p>


<h3>
Student Account
</h3>


</div>


</div>




<div style="
display:flex;
align-items:center;
gap:20px;
padding:20px 0;
">



<div style="
width:75px;
height:75px;
border-radius:50%;
background:#155EEF;
color:white;
display:flex;
align-items:center;
justify-content:center;
font-size:32px;
font-weight:700;
">


<?php echo strtoupper(substr($_SESSION["full_name"],0,1)); ?>


</div>




<div>


<h2 style="margin-bottom:5px;">

<?php echo $_SESSION["full_name"]; ?>

</h2>


<p style="color:#667085;">

Student Account

</p>


</div>



</div>





<hr style="
border:none;
border-top:1px solid #EEF1F5;
margin:20px 0;
">






<div style="
display:grid;
grid-template-columns:repeat(2,1fr);
gap:25px;
">



<div>

<p style="color:#667085;font-size:12px;">
Username
</p>

<strong>
<?php echo $_SESSION["username"]; ?>
</strong>

</div>




<div>

<p style="color:#667085;font-size:12px;">
Role
</p>

<strong>
Student
</strong>

</div>




<div>

<p style="color:#667085;font-size:12px;">
Account Status
</p>


<strong style="color:#067647;">
Active
</strong>


</div>




<div>

<p style="color:#667085;font-size:12px;">
Risk Status
</p>


<strong style="color:#B54708;">
<?php echo htmlspecialchars($risk); ?>
</strong>

</div>



</div>



</div>








<!-- ATTENDANCE SUMMARY -->


<div class="dashboard-panel">


<div class="panel-header">

<div>

<p class="panel-label">
ATTENDANCE
</p>


<h3>
Attendance Summary
</h3>


</div>

</div>



<h2 style="
font-size:38px;
color:#155EEF;
">

<?php echo $attendance_rate; ?>%

</h2>


<p>
Overall Attendance Rate
</p>


<br>


<p>
Present Days:
<strong>
<?php echo $present; ?>
</strong>
</p>


<p>
Absent Days:
<strong>
<?php echo $absent; ?>
</strong>
</p>



</div>








<!-- AI RISK -->


<div class="dashboard-panel">


<div class="panel-header">

<div>

<p class="panel-label">
AI ANALYTICS
</p>


<h3>
Risk Assessment
</h3>


</div>


</div>




<h2 style="
color:#B54708;
font-size:32px;
">

<?php echo $risk; ?>
</h2>



<p>
Current AI assessment result
</p>



</div>




</div>



</main>


</div>


</body>


</html>