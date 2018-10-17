<?php
$db = new mysqli('localhost', 'root', '', 'pollution');

if($db->connect_errno){
  die('Unable to connect to database [' . $db->connect_error . ']');
}

if (!$db->set_charset("utf8")) {
  printf("Error loading character set utf8: %s\n", $mysqli->error);
  exit();
}
?>