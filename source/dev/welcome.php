<?php
include ('session.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  // loads the number of ajax requests
  if (isset($_POST['refresh_requests'])) {
    $sql = "SELECT sum(r.count) AS s, r.type FROM requests AS r
            WHERE r.dev_id = '$user_check' GROUP BY r.type ORDER BY s DESC";
    $result = $db->query($sql);
    echo "<table>";
    echo "<tr>";
    echo "<th>Requests</th>";
    echo "<th>Type of request</th>";
    echo "</tr>";
    $count = $result->num_rows;
    if (!$count) {
      echo "<tr>";
      echo "<td>-</td>";
      echo "<td>-</td>";
      echo "</tr>";
    }
    else {
      while ($row = $result->fetch_assoc()) {
        $number = $row['s'];
        $type = $row['type'];
        echo "<tr>";
        echo "<td>$number</td>";
        echo "<td>$type</td>";
        echo "</tr>";
      }
    }
  }

  exit(0);
}
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Pollution Greece / dev
    </title>
    <link rel="stylesheet" href="css/style_welcome.css">
  </head>
  <body>
    <h3>Pollution Greece / Developer 
    </h3>
    <div class="info">
      <h1>Welcome 
        <?php echo $login_session; ?>
      </h1>
      <div class="faded_line"></div>
      <!--Informing the developer of their API key-->
      <h1>Your API key is: 
        <?php echo "<span class='apikey'>".$apikey."</span>"; ?>
      </h1>
      <!--Number of ajax requests-->
      <div class="info_top">
        Total number of requests per type:
      </div>
      <div id="requests_count" class="scrollable">
      </div>
      <div class="logout">
        <a class="logout_button" href = "logout.php">Logout
        </a>
      </div>
    </div>
    <script src='http://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js'>
    </script>
    <script src="js/script.js">
    </script>
  </body>
</html>