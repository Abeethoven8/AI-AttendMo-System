<?php

session_start();


/*
|--------------------------------------------------------------------------
| AI: Attend Mo - Logout
|--------------------------------------------------------------------------
*/


// Remove all session data
session_unset();


// Destroy current session
session_destroy();


// Return to login page
header("Location: login.php");
exit;