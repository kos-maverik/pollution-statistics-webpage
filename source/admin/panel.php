<?php
include ("session.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  // ajax request
  if (isset($_POST['refresh_requests'])) {
    $sql = "SELECT sum(r.count) AS s, r.type
            FROM requests AS r GROUP BY r.type ORDER BY s DESC";
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

    echo "</table>";
    exit(0);
  }
  // ajax request
  else if (isset($_POST['refresh_keys'])) {
    $sql = "SELECT sum(r.count) AS s, d.apikey, d.email
            FROM requests AS r LEFT JOIN devs AS d ON r.dev_id=d.id
            GROUP BY d.id ORDER BY s DESC LIMIT 10";
    $result = $db->query($sql);
    echo "<table>";
    echo "<tr>";
    echo "<th>Requests</th>";
    echo "<th>API key</th>";
    echo "<th>E-mail</th>";
    echo "</tr>";
    $count = $result->num_rows;
    if (!$count) {
      echo "<tr>";
      echo "<td>-</td>";
      echo "<td>-</td>";
      echo "<td>-</td>";
      echo "</tr>";
    }
    else {
      while ($row = $result->fetch_assoc()) {
        $requests = $row['s'];
        $apikey = $row['apikey'];
        $email = $row['email'];
        echo "<tr>";
        echo "<td>$requests</td>";
        echo "<td>$apikey</td>";
        echo "<td>$email</td>";
        echo "</tr>";
      }
    }

    echo "</table>";
    exit(0);
  }
  // ajax request
  else if (isset($_POST['refresh_api'])) {
    $sql = "SELECT count(*) as c FROM devs";
    $result = $db->query($sql);
    $row = $result->fetch_assoc();
    $count = $row['c'];
    echo "<b>" . $count . "</b>";
    exit(0);
  }
  // adds station
  else if (isset($_POST['add_station'])) {
    $code = $db->real_escape_string($_POST['station_code']);
    $name = $db->real_escape_string($_POST['station_name']);
    $lat = $db->real_escape_string($_POST['latitude']);
    $lon = $db->real_escape_string($_POST['longitude']);
    // validity checks
    if (!(is_numeric($lat) && is_numeric($lon))) {
      $error = "The coordinates were incorrect";
    }
    else if (strlen($code) > 32) {
      $error = "The password must not exceed 32 characters";
    }
    else if (strlen($name) > 64) {
      $error = "The name must not exceed 64 characters";
    }
    else {
      // checks if the city already exists in the database
      $sql = "SELECT count(*) AS c FROM stations WHERE code = '$code'";
      $result = $db->query($sql);
      $row = $result->fetch_assoc();
      $count = $row['c'];

      if ($count) {
        $error = "The city has already been added";
      }
      else {
        $sql = "INSERT INTO stations VALUES ('$code', '$name', '$lat', '$lon')";
        $db->query($sql);
        $message = 'The city ' . $name . ' was successfully added';
      }
    }
  }
  else
  if (isset($_POST['add_data'])) {
    if (!is_numeric($_POST['year'])) {
      $error = "The year was incorrect";
    }
    else {
      // uploads to this folder due to wamp's security settings
      $target_dir = "C:/wamp64/tmp/upload/";
      $target_file = $target_dir . basename($_FILES["file"]["name"]);

      // checks if the file already exists
      if (file_exists($target_file)) {
        $error = "There is a file with the same name";
      }
      else if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
        // uploads the file to the database
        $station = $db->real_escape_string($_POST['list_stations']);
        $pollutant = $db->real_escape_string($_POST['list_pollutants']);
        $statement = $db->prepare("INSERT INTO data VALUES (?, ?, ?, ?)");
        // opens the csv file and splits it in 24 parts
        // 24 inserts for each line, one for every hour (datetime)
        $file = fopen('C:/wamp64/tmp/upload/' . $_FILES["file"]["name"], 'r');
        $line = fgetcsv($file);
        // validity checks
        if (count($line) != 25) {
          $error = "The CSV file has wrong format";
        }
        else {
          $date = new DateTime($line[0]);
          $year = $date->format('Y');
          rewind($file);

          if ($year != $_POST['year']) {
            $error = "The year does not match the CSV file";
          }
          else {
            // checks if the data (station/pollutant/date) is already in the database
            $sql = "SELECT count(*) as c FROM data WHERE station = '$station'
                    AND pollutant = '$pollutant'
                    AND EXTRACT(YEAR FROM date) = '$year'";
            $result = $db->query($sql);
            $row = $result->fetch_assoc();
            $count = $row['c'];
            if ($count) {
              $error = "The data already exists in the database";
            }
            else {
              // uploads the data to the database
              while (($line = fgetcsv($file)) !== FALSE) {
                for ($i = 0; $i < 24; ++$i) {
                  // checks for the invalid value (-9999)
                  if ($line[$i + 1] != - 9999) {
                    $date = new DateTime($line[0] . ' ' . $i . ':00');
                    $sqltime = $date->format('Y-m-d H:i:s');
                    $statement->bind_param('sssd', $station, $pollutant, $sqltime, $line[$i + 1]);
                    $statement->execute();
                  }
                }
              }
              // closes and deletes the file
              fclose($file);
              unlink('C:/wamp64/tmp/upload/' . $_FILES["file"]["name"]);
              $message = "The file " . basename($_FILES["file"]["name"]) . " was succesfully uploaded";
            }
		  }
        }
      }
      else {
        $error = "There was a problem uploading the file";
      }
    }
  }
  // station removal
  else if (isset($_POST['delete_stations'])) {
    if (!isset($_POST['checklist'])) {
      $error = "No station selected";
    }
    else {
      $count = count($_POST['checklist']);
      for ($i = 0; $i < $count; $i++) {
        $code = $db->real_escape_string($_POST['checklist'][$i]);
        // first deletes the station's data
        $sql = "DELETE FROM data WHERE station = '$code'";
        $db->query($sql);
        $sql = "DELETE FROM stations WHERE code = '$code'";
        $db->query($sql);
      }

      if ($count == 1) {
        $message = '1 station was deleted';
      }
      else {
        $message = $count . ' stations were deleted';
      }
    }
  }
}
$db->close();
?>

<!DOCTYPE html>
<html >
  <head>
    <meta charset="utf-8">
    <title>Pollution Greece / admin
    </title>
    <link rel="stylesheet" href="css/style_panel.css">
  </head>
  <body>
    <?php
      echo "<h3>Pollution Greece / Administrator / " .$login_session."</h3>";
    ?>
    <!--Menu-->
    <ul>
      <li><a id="menu_stations" class="active" href="#">Stations</a></li>
      <li><a id="menu_stats" href="#">API Statistics</a></li>
      <li class="logout"><a class="logout" href="logout.php">Logout</a></li>
    </ul>
    <!--Panel-->
    <div class="controls">
      <!--Stations-->
      <div id="stations">
        <!--Add station-->
        <div id="stations_add">
          <!--User input-->
          <div class="left">
            <form method="post">
              <fieldset>
                <legend>Add station</legend>
                <input class="left_input" id="station_name" type="text" name="station_name"
                       placeholder="Station name" required="required">
                <input class="left_input" type="text" name="station_code" placeholder="Station code" required="required">
                <input class="left_input" id="latitude" type="text" name="latitude" placeholder="Latitude" required="required">
                <input class="left_input" id="longitude" type="text" name="longitude" placeholder="Longitude" required="required">
                <button name="add_station" type="submit" class="add_button">Add</button>
              </fieldset>
            </form>
            <!--Swap between adding and editing stations-->
            <div class="edit">
              <a class="stations_button" id="edit_button" href="#">Edit stations</a>
            </div>
            <!--Success or error message-->
            <?php
              if (isset($error)) {
                echo '<div class="error">'.$error.'</div>';
              } else if (isset($message)) {
                echo '<div class="message">'.$message.'</div>';
              }
            ?>
          </div>
          <!--Map-->
          <div class="right">
            <div class="right_info">Enter the coordinates, or select the location on the map</div>
            <div id="map"></div>
          </div>
        </div>
        <!--Edit stations-->
        <div class="hide" id="stations_edit">
          <!--Add data to a station-->
          <div class="left">
            <form method="post" enctype="multipart/form-data">
              <fieldset>
                <legend>Upload data (CSV)</legend>
                <!--Select a station-->
                <div class="options_city">
                  <?php
                    include "config.php";
                    $sql = "SELECT code, name FROM stations ORDER BY name";
                    $result = $db->query($sql);
                    echo "<select name='list_stations' required='required'>";
                    echo "<option value=''>Select a station</option>";
                    while ($row = $result->fetch_assoc()) {
                      $code = $row['code'];
                      $name = $row['name']; 
                      echo '<option value="'.$code.'">'.$name.' - '.$code.'</option>';
                    }
                    echo "</select>";
                    $db->close();
                  ?>
                </div>
                <!--Select a pollutant-->
                <div class="options_pollutant">
                  <?php
                    include "config.php";
                    $sql = "SELECT formula, name, unit FROM pollutants ORDER BY name";
                    $result = $db->query($sql);
                    echo "<select name='list_pollutants' required='required'>";
                    echo "<option value=''>Select a pollutant</option>";
                    while ($row = $result->fetch_assoc()) {
                      $formula = $row['formula'];
                      $name = $row['name'];
                      $unit = $row['unit'];
                      echo '<option value="'.$formula.'">'.$name.' - '.$formula.' ('.$unit.'/m&sup3;)'.'</option>';
                    }
                    echo "</select>";
                    $db->close();
                  ?> 
                </div>
                <!--Select year and coordinates-->
                <input class="left_input" type="text" name="year" placeholder="Year" required="required">
                <input class="left_input" type="file" name="file" id="file" required="required">
                <button name="add_data" type="submit" class="add_button">Add</button>
              </fieldset>
            </form>
            <!--Swap between adding and editing a station-->
            <div class="edit">
              <a class="stations_button" id="add_button" href="#">Add a station</a>
            </div>
            <?php
              if (isset($error)) {
              echo '<div align="center" style = "font-size:11px; color:#cc0000; margin-top:20px">'.$error.'</div>';
              } else if (isset($message)) {
              echo '<div align="center" style = "font-size:11px; color:#5294F9; margin-top:20px">'.$message.'</div>';
              }
            ?>
          </div>
          <!--Delete stations-->
          <div class="right">
            <div class="right_info">
              Select the stations you wish to delete
            </div>
            <form class="delete_form" method="post">
              <div class="scrollable">
                <!--Display details for every station-->
                <!--Might take a while due to big database-->
                <?php
                  include "config.php";
                  $sql = "SELECT s.code, s.name, s.latitude, s.longitude, count(d.station) as c
                          FROM stations AS s LEFT JOIN data AS d ON s.code=d.station
                          GROUP BY s.code";
                  $result = $db->query($sql);
                  echo "<table>";
                  echo "<tr>";
                  echo "<th>Select</th>";
                  echo "<th>Station code</th>";
                  echo "<th>Station name</th>";
                  echo "<th>Latitude</th>";
                  echo "<th>Longitude</th>";
                  echo "<th>Number of data</th>";
                  echo "</tr>";
                  $count =  $result->num_rows;
                  if (!$count) {
                    echo "<tr>";
                    echo "<td>-</td>";
                    echo "<td>-</td>";
                    echo "<td>-</td>";
                    echo "<td>-</td>";
                    echo "<td>-</td>";
                    echo "<td>-</td>";
                    echo "</tr>";
                  } else {
                    while ($row = $result->fetch_assoc()) {
                      $code = $row['code'];
                      $name = $row['name']; 
                      $lat = $row['latitude']; 
                      $lon = $row['longitude']; 
                      $count = $row['c'];
                      echo "<tr>";
                      echo "<td><input class='right_input' type='checkbox' name='checklist[]' value='$code'></td>";
                      echo "<td>$code</td>";
                      echo "<td>$name</td>";
                      echo "<td>$lat</td>";
                      echo "<td>$lon</td>";
                      echo "<td>$count</td>";
                      echo "</tr>";
                    }
                  }
                  echo "</table>";
                  $db->close();
                ?>
              </div>
              <div class="delete">
                <button name="delete_stations" type="submit" class="delete_button">Delete stations
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <!--Statistics-->
      <!--Automatically refreshed with ajax-->
      <div id="stats" class="hide">
        <div class="stats_info">
          Number of API keys created:
          <span id="apikeys_count"></span>
        </div>
        <div class="stats_left">
          <div class="info_top">
            Top 10 API keys with most requests:
          </div>
          <div class="scrollable" id="top_apikeys">
          </div>
        </div>
        <div class="stats_right">
          <div class="info_top">
            Total number of requests per type:
          </div>
          <div class="scrollable" id="requests_count">
          </div>
        </div>
      </div>
    </div>
    <script src='http://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js'></script>
    <script src="js/script.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBRx9J4CEDhExOiBPZSYzZNH5lZEb05_D0&amp;callback=initMap">
    </script>
  </body>
</html>