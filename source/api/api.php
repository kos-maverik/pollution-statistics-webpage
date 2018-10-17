<?php
include "config.php";
// if there is a request
if (isset($_GET['request'])) {
  // results table
  $res = array();
  $request = $_GET['request'];
  // checks if the requests contains an API key
  if (!isset($_GET['apikey'])) {
    $res['error'] = 'An API key must be passed with each request';
  }
  else {
    // checks if the API key exists in the database
    $apikey = $db->real_escape_string($_GET['apikey']);
    $sql = "SELECT id, count(*) AS c FROM devs WHERE apikey = '$apikey'";
    $result = $db->query($sql);
    $row = $result->fetch_assoc();
    $id = $row['id'];
    $count = $row['c'];
    if (!$count) {
      $res['error'] = 'Your API key is invalid';
    }
    else
    // reads the request's type
    // request 1. stations
    if ($request == "stations") {
      $sql = "SELECT * FROM STATIONS ORDER BY name";
      $result = $db->query($sql);
      while ($row = $result->fetch_assoc()) {
        $json['code'] = $row['code'];
        $json['name'] = $row['name'];
        $json['lat'] = $row['latitude'];
        $json['lon'] = $row['longitude'];
        $res[] = $json;
      }
    }
    // request 2. pollutants
    else if ($request == "pollutants") {
      $sql = "SELECT formula, name, unit FROM POLLUTANTS ORDER BY name";
      $result = $db->query($sql);
      while ($row = $result->fetch_assoc()) {
        $json['formula'] = $row['formula'];
        $json['name'] = $row['name'];
        $json['unit'] = $row['unit'];
        $res[] = $json;
      }
    }
    // request 3. absolute value
    else if ($request == "absolute_values") {
      // checks if a station has been selected
      if (isset($_GET['station_code'])) {
        $station = $db->real_escape_string($_GET['station_code']);
        $where = " AND station = '$station'";
      }
      else {
        $where = "";
      }

      $formula = $db->real_escape_string($_GET['formula']);
      $date = $db->real_escape_string($_GET['date']);
      // calculates the results
      $sql = "SELECT s.code, s.name, s.latitude, s.longitude, d.value FROM data AS d
              INNER JOIN stations AS s ON d.station = s.code
              WHERE pollutant = '$formula' AND date = '$date'" . $where . ";";
      $result = $db->query($sql);
      while ($row = $result->fetch_assoc()) {
        $json['code'] = $row['code'];
        $json['name'] = $row['name'];
        $json['lat'] = $row['latitude'];
        $json['lon'] = $row['longitude'];
        $json['value'] = $row['value'];
        $res[] = $json;
      }
    }
    // request 4. mean value and standard deviation
    else if ($request == "mean_values") {
      // checks if a station has been selected
      if (isset($_GET['station_code'])) {
        $station = $db->real_escape_string($_GET['station_code']);
        $where = " AND station = '$station'";
      }
      else {
        $where = "";
      }

      $formula = $db->real_escape_string($_GET['formula']);
      $from = $db->real_escape_string($_GET['date_from']);
      $to = $db->real_escape_string($_GET['date_to']);
      // calculates the results
      $sql = "SELECT s.code, s.name, s.latitude, s.longitude, AVG(d.value) AS avg, STD(VALUE) AS std FROM data AS d
              INNER JOIN stations AS s ON d.station = s.code
              WHERE pollutant = '$formula' AND date BETWEEN '$from' AND '$to'" . $where . " GROUP BY s.code;";
      $result = $db->query($sql);
      while ($row = $result->fetch_assoc()) {
        $json['code'] = $row['code'];
        $json['name'] = $row['name'];
        $json['lat'] = $row['latitude'];
        $json['lon'] = $row['longitude'];
        $json['std'] = $row['std'];
        $json['avg'] = $row['avg'];
        $res[] = $json;
      }
    }
    // invalid request
    else {
      $res['error'] = 'Invalid request';
    }
    // increases the number of requests in the database
    if (!isset($res['error'])) {
      $sql = "UPDATE requests SET count = count + 1
		WHERE dev_id = '$id' AND type = '$request'";
      $db->query($sql);
    }
  }
  // returns the results (or an error message) in json format
  echo json_encode($res, JSON_UNESCAPED_UNICODE);  
}
// if no request was provided, does nothing

$db->close();
?>