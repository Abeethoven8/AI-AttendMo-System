<?php

$current_page = basename($_SERVER['PHP_SELF']);

?>


<aside class="sidebar">


<div class="sidebar-brand">

<h2>
AI: Attend Mo
</h2>

<p>
Student Portal
</p>

</div>




<nav class="sidebar-nav">



<a href="student_dashboard.php"
class="nav-link <?php echo ($current_page == 'student_dashboard.php') ? 'active' : ''; ?>">
Dashboard
</a>




<a href="student_profile.php"
class="nav-link <?php echo ($current_page == 'student_profile.php') ? 'active' : ''; ?>">
My Profile
</a>




<a href="student_attendance.php"
class="nav-link <?php echo ($current_page == 'student_attendance.php') ? 'active' : ''; ?>">
My Attendance
</a>




<a href="student_performance.php"
class="nav-link <?php echo ($current_page == 'student_performance.php') ? 'active' : ''; ?>">
Academic Performance
</a>




<a href="student_risk.php"
class="nav-link <?php echo ($current_page == 'student_risk.php') ? 'active' : ''; ?>">
Attendance Risk Insight
</a>




<a href="student_recommendations.php"
class="nav-link <?php echo ($current_page == 'student_recommendations.php') ? 'active' : ''; ?>">
Recommendations
</a>




<a href="logout.php"
class="nav-link logout-btn">
Logout
</a>



</nav>


</aside>





<!-- LOGOUT MODAL -->


<div id="logoutModal" class="logout-modal">


<div class="logout-box">


<h3>
Logout
</h3>


<p>
Are you sure you want to log out?
</p>



<div class="logout-actions">


<button id="cancelLogout">
Cancel
</button>



<a href="logout.php">
Yes, Logout
</a>



</div>


</div>


</div>





<script>


const logoutBtn = document.querySelector(".logout-btn");

const logoutModal = document.getElementById("logoutModal");

const cancelLogout = document.getElementById("cancelLogout");



if(logoutBtn){


logoutBtn.addEventListener("click", function(e){


    e.preventDefault();


    logoutModal.classList.add("active");


});


}




if(cancelLogout){


cancelLogout.addEventListener("click", function(){


    logoutModal.classList.remove("active");


});


}



</script>