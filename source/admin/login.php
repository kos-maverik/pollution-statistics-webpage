<?php
session_start();

// redirects to home page if there is an active session
if (isset($_SESSION['login_admin'])) {
  header("location: panel.php");
  exit(0);
}

include ("config.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  // checks if admin's login details are correct
  $email = $db->real_escape_string($_POST['email']);
  $password = $db->real_escape_string($_POST['password']);
  $sql = "SELECT id FROM admins WHERE email = '$email' and password = '$password'";
  $result = $db->query($sql);

  $row = $result->fetch_assoc();
  $id = $row['id'];
  $count = $result->num_rows;

  // if the email and the password are correct
  if ($count == 1) {
    // redirect to home page and set session
    $_SESSION['login_admin'] = $id;
    header("location: panel.php");
    exit(0);
  }
  // otherwise display error message
  else {
    $error = "Incorrect e-mail or password";
  }
}
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <title>Pollution Greece
    </title>
    <link rel="stylesheet" href="css/style.css">
  </head>
  <body>
    <div class="top">
      <h1>Pollution Greece / admin</h1>
      <h3>Statistics</h3>
    </div>
    <!--Login form with nice visual effects-->
    <form action="#" method = "post">
      <div class="group">
        <input type="email" name="email" required="required" autofocus>
        <span class="highlight"></span>
        <span class="bar"></span>
        <label class="email">E-mail</label>
      </div>
      <div class="group">
        <input type="password" name="password" required="required" autocomplete="new-password">
        <span class="highlight"></span>
        <span class="bar"></span>
        <label class="pass">Password</label>
      </div>
      <button type="submit" class="button buttonBlue">
        Login
        <span class="ripples buttonRipples">
          <span class="ripplesCircle"></span>
        </span>
      </button>
      <!--If there was a login error-->
      <?php
        if (isset($error)) {
          echo '<div align="center" style = "font-size:1.6vh; color:#cc0000; margin-top:1.5vh">'.$error.'</div>';
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
    <script src='http://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js'></script>
    <script src="js/index.js"></script>
  </body>
</html>