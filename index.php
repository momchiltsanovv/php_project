<?php
require_once 'config/session.php';

// Redirect to profile if logged in, otherwise to login
if (isLoggedIn()) {
    header('Location: /profile.php');
} else {
    header('Location: /login.php');
}
exit();


