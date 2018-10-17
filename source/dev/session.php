<?php
  session_start();

  // redirects to login page if there is no active session
  $user_check = $_SESSION['login_dev'];
  if(!isset($user_check)){
    header("location:login.php");
    exit(0);
  }
   
  include('config.php');

  $ses_sql = $db->query("select email, apikey from devs where id = '$user_check'");
  $row = $ses_sql->fetch_assoc();
   
  $login_session = $row['email'];
  $apikey = $row['apikey'];
?>