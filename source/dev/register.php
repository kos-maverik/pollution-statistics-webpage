<?php
session_start();

if (isset($_SESSION['login_dev'])) {
  header("location: welcome.php");
  exit(0);
}

include ("config.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  $email = $db->real_escape_string($_POST['email']);
  $password = $db->real_escape_string($_POST['password']);
  $repassword = $db->real_escape_string($_POST['repassword']);
  // checks if the password confirmation is correct
  if ($repassword != $password) {
    $error = "Password and password confirmation must match";
  }
  // checks the password's length
  else if (strlen($password) < 6 || strlen($password) > 32) {
    $error = "The password must contain from 6 to 32 characters";
  }
  // checks the email's length
  else if (strlen($email) > 32) {
    $error = "The email must not exceed 32 characters";
  }
  else {
    // checks if the email already exists in the database
    $sql = "SELECT 1 FROM devs WHERE email = '$email'";
    $result = $db->query($sql);
    $count = $result->num_rows;

    // returns the corresponding error
    if ($count) {
      $error = "That email address is already in use";
    }
    else {
      // md5 encryption salted with the secret password maverik
      $apikey = md5("maverik".$email);
      // inserts new developer's details into the database
      $sql = "INSERT INTO devs (email, password, apikey) VALUES ('$email', '$password', '$apikey')";
      $db->query($sql);
      $sql = "SELECT id FROM devs WHERE email = '$email' and password = '$password'";
      $result = $db->query($sql);
      $row = $result->fetch_assoc();
      $id = $row['id'];
      // intializes the number of requests to 0
      $sql = "INSERT INTO requests VALUES ('$id', 'stations', 0)";
      $db->query($sql);
      $sql = "INSERT INTO requests VALUES ('$id', 'pollutants', 0)";
      $db->query($sql);
      $sql = "INSERT INTO requests VALUES ('$id', 'absolute_values', 0)";
      $db->query($sql);
      $sql = "INSERT INTO requests VALUES ('$id', 'mean_values', 0)";
      $db->query($sql);
      // redirects to home page and set session
      $_SESSION['login_dev'] = $id;
      header("location: welcome.php");
      exit(0);
    }
  }
}
?>

<!DOCTYPE html>
<html >
  <head>
    <meta charset="UTF-8">
    <title>Pollution Greece
    </title>
    <link rel="stylesheet" href="css/style.css">
  </head>
  <body>
    <div class="top_register">
      <h1>Pollution Greece
      </h1>
      <h3>Statistics
      </h3>
    </div>
    <!--Login form with nice visual effects-->
    <form action="#" class="register" method = "post">
      <div class="create">Create account</div>
      <div class="group">
        <input type="email" name="email" required="required" autofocus>
        <span class="highlight"></span>
        <span class="bar"></span>
        <label class="email">E-mail</label>
      </div>
      <div class="group">
        <input type="password" id="password" name="password" required="required" autocomplete="new-password"
               onKeyUp="checkPass();">
        <span class="highlight"></span>
        <span class="bar"></span>
        <label class="pass">Password</label>
      </div>
      <div class="group">
        <input type="password" id="repassword" name="repassword" required="required" autocomplete="new-password"
               onKeyUp="checkPass();">
        <span class="highlight"></span>
        <span class="bar"></span>
        <label class="repass">Confirm password</label>
      </div>
      <span id="message"></span>
      <button type="submit" class="button buttonBlue">
        Register
        <span class="ripples buttonRipples">
          <span class="ripplesCircle"></span>
        </span>
      </button>
      <div class="register">
        <a id="reg" href="login.php">Already have an account?</a>
      </div>
      <!--If there was a register error-->
      <?php
        if (isset($error)) {
          echo '<div align="center" style = "font-size:11px; color:#cc0000; margin-top:10px">'.$error.'</div>';
        }
      ?>
    </form>
    <!--University advertising-->
    <footer>
      <a href="https://www.ceid.upatras.gr/" target="_blank">
        <img src="https://www.ceid.upatras.gr/sites/all/themes/ceid_theme/logo.png" alt="CEID">
      </a>
      <p>
        <a href="https://www.ceid.upatras.gr/" target="_blank">Ceid Upatras</a>
      </p>
    </footer>
    <script>
    </script>
    <script src='http://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js'>
    </script>
    <script src="js/index.js">
    </script>
  </body>
</html>