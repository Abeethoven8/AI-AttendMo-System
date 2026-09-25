<?php

require_once __DIR__ . "/includes/auth.php";


if ($_SESSION["role"] !== "Faculty") {

    header("Location: login.php");
    exit;

}

?>

<!DOCTYPE html>
<html>
<head>

<title>Faculty Dashboard</title>

<style>

body {
    font-family: Arial, sans-serif;
    background:#f5f7fb;
    margin:0;
}

.container {
    padding:40px;
}

.card {
    background:white;
    padding:25px;
    border-radius:12px;
    margin-bottom:20px;
}

h1 {
    color:#123b7a;
}

</style>

</head>

<body>


<div class="container">

<div class="card">

<h1>
Faculty Dashboard
</h1>

<p>
Welcome,
<?php echo $_SESSION["full_name"]; ?>
</p>

</div>


<div class="card">

<h3>
Faculty Features
</h3>

<ul>

<li>View Class Attendance</li>

<li>Monitor Student Attendance Records</li>

<li>View Attendance Analytics</li>

<li>Submit Attendance Data</li>

</ul>

</div>


</div>


</body>

</html>