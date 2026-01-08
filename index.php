<?php
require_once 'config/session.php';

// Redirect to feed if logged in, otherwise to login
if (isLoggedIn()) {
    header('Location: /feed.php');
} else {
    header('Location: /login.php');
}
exit();


