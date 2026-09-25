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




// GET ATTENDANCE DATA

$stmt = $pdo->prepare("
    SELECT status
    FROM attendance
    WHERE student_id = ?
");


$stmt->execute([$student_id]);


$records = $stmt->fetchAll();



$total_records = count($records);

$present = 0;
$absent = 0;



foreach($records as $row){

    if($row["status"] == "Present"){

        $present++;

    }
    else{

        $absent++;

    }

}




if($total_records > 0){

    $attendance_rate =
    round(($present/$total_records)*100,2);

}
else{

    $attendance_rate = 0;

}



// AUTOMATED INSIGHT LOGIC

$recommendations = [];



if($attendance_rate < 75){


    $recommendations[] = [

        "concern"=>"Attendance Risk",

        "recommendation"=>"Improve attendance consistency and coordinate with appropriate support personnel.",

        "priority"=>"High",

        "basis"=>"Low attendance rate detected"

    ];


}
elseif($attendance_rate < 90){


    $recommendations[] = [

        "concern"=>"Attendance Monitoring",

        "recommendation"=>"Maintain regular attendance and avoid repeated absences.",

        "priority"=>"Medium",

        "basis"=>"Attendance pattern requires monitoring"

    ];


}
else{


    $recommendations[] = [

        "concern"=>"Attendance Performance",

        "recommendation"=>"Continue maintaining good attendance behavior.",

        "priority"=>"Routine",

        "basis"=>"Consistent attendance pattern"

    ];


}



// ADD ABSENCE INSIGHT

if($absent > 0){


    $recommendations[] = [

        "concern"=>"Absence Pattern",

        "recommendation"=>"Review attendance progress and prevent consecutive absences.",

        "priority"=>"Medium",

        "basis"=>"Recorded absence entries"

    ];


}



$total_recommendations = count($recommendations);


$high = 0;
$medium = 0;
$routine = 0;



foreach($recommendations as $item){


    if($item["priority"]=="High"){

        $high++;

    }
    elseif($item["priority"]=="Medium"){

        $medium++;

    }
    else{

        $routine++;

    }

}


?>


<!DOCTYPE html>
<html>

<head>

<title>
Recommendations | AI Attend Mo
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
Recommendations
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
Total Recommendations
</p>

<h2>
<?php echo $total_recommendations; ?>
</h2>

<span>
Automated insights
</span>

</div>



<div class="summary-card">

<p>
High Priority
</p>

<h2 style="color:#B42318;">
<?php echo $high; ?>
</h2>

<span>
Needs attention
</span>

</div>



<div class="summary-card">

<p>
Medium Priority
</p>

<h2 style="color:#B54708;">
<?php echo $medium; ?>
</h2>

<span>
Monitor progress
</span>

</div>



<div class="summary-card">

<p>
Routine
</p>

<h2 style="color:#067647;">
<?php echo $routine; ?>
</h2>

<span>
Maintain performance
</span>

</div>



</div>






<div class="dashboard-panel">


<div class="panel-header">


<div>

<p class="panel-label">
AUTOMATED INSIGHT
</p>


<h3>
SYSTEM EXPLANATION
</h3>


</div>


</div>




<div class="attendance-table-wrapper">


<table class="attendance-table">


<thead>

<tr>

<th>
Concern
</th>

<th>
Recommendation
</th>

<th>
Priority
</th>

<th>
Basis
</th>

</tr>

</thead>



<tbody>


<?php foreach($recommendations as $item): ?>


<tr>


<td>
<?php echo $item["concern"]; ?>
</td>


<td>
<?php echo $item["recommendation"]; ?>
</td>


<td>

<?php echo $item["priority"]; ?>

</td>


<td>
<?php echo $item["basis"]; ?>
</td>


</tr>


<?php endforeach; ?>


</tbody>


</table>


</div>


</div>





<div class="dashboard-panel">


<div class="panel-header">


<div>

<p class="panel-label">
SYSTEM EXPLANATION
</p>


<h3>
Recommendation Explanation
</h3>


</div>


</div>



<p>

These automated insights are generated based on the student's recorded attendance patterns to support monitoring and attendance decision-making.

</p>



</div>



</main>


</div>


</body>


</html>