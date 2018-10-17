<?php
  session_start();

  unset($_SESSION['login_dev']);
  header("Location: login.php");
?>
