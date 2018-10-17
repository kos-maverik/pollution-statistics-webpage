// Google Maps API
var map, heatmap;
var infowindow = new google.maps.InfoWindow();
var markers = [];
// localhost restricted API key
var my_apikey = 'd531e8059e80d9c0ed4fcb3f966d24b0';

// map initialization
function initMap() {
  map = new google.maps.Map(document.getElementById('map'), {
    zoom: 7,
    center: {lat: 38.585033, lng: 22.783782},
    mapTypeId: 'roadmap'
  });
  
  heatmap = new google.maps.visualization.HeatmapLayer({
    map: map
  });
}
initMap();

// toggles heatmap
function toggleHeatmap() {
  heatmap.setMap(heatmap.getMap() ? null : map);
}

// toggles markers
function toggleMarkers() {
  for(var i = 0; i<markers.length; i++){
    markers[i].setMap(markers[i].getMap() ? null : map);
  }
}

// changes heatmap's colors
function changeGradient() {
  var gradient = [
  'rgba(0, 255, 255, 0)',
  'rgba(0, 255, 255, 1)',
  'rgba(0, 191, 255, 1)',
  'rgba(0, 127, 255, 1)',
  'rgba(0, 63, 255, 1)',
  'rgba(0, 0, 255, 1)',
  'rgba(0, 0, 223, 1)',
  'rgba(0, 0, 191, 1)',
  'rgba(0, 0, 159, 1)',
  'rgba(0, 0, 127, 1)',
  'rgba(63, 0, 91, 1)',
  'rgba(127, 0, 63, 1)',
  'rgba(191, 0, 31, 1)',
  'rgba(255, 0, 0, 1)'
  ]
  heatmap.set('gradient', heatmap.get('gradient') ? null : gradient);
}

// changes heatmap's radius
function changeRadius(radius) {
  heatmap.set('radius', radius);
}

// changes heatmap's opacity
function changeOpacity() {
  heatmap.set('opacity', heatmap.get('opacity') ? null : 0.2);
}

// creates heatmap
function createHeatmap(json, pollutant, request) {
  var data = [];
  // normalizes values in [1, 10]
  if (pollutant == 'CO') {
    max_value = 2;
  } else if (pollutant == 'NO') {
    max_value = 60;
  } else if (pollutant == 'NO2') {
    max_value = 60;
  } else if (pollutant == 'O3') {
    max_value = 80;
  } else if (pollutant == 'SO2') {
    max_value = 30;
  }

  for(var i = 0; i<json.length; i++) {
    if (request == 'mean_values') {
      value = (json[i].avg /max_value)*10;
    } else {
      value = (json[i].value /max_value)*10;
    }
    // if the value is off limits
    if (value > 10) {
      value = 10;
    } else if (value < 1) {
      value = 1;
    }

    data.push({
      location: new google.maps.LatLng(json[i].lat, json[i].lon),
      weight: value
    });
  }

  heatmap = new google.maps.visualization.HeatmapLayer({
    data: data,
    maxIntensity: 10,
    radius: 35,
    map: map,
  });
}

// creates markers
function createMarkers(json, request) {
  for(var i = 0; i<json.length; i++) {
    // info window contents
    var contentString = '<div class="info_station">'+
    json[i].name+'</div><div class="info_coords">'+
    parseFloat(json[i].lat).toFixed(4)+', '+
    parseFloat(json[i].lon).toFixed(4)+'</div>'+
    '<div class="bodyContent"><p>';

    if (request == 'mean_values') {
      contentString += 'Mean value: '+
      parseFloat(json[i].avg).toFixed(4)+
      '</p><p>Standard Deviation: '+
      parseFloat(json[i].std).toFixed(4);
    } else {
      contentString += 'Absolute value: '+
      parseFloat(json[i].value).toFixed(4);
    }
    contentString += '</p></div></div>';

    markers.push(new google.maps.Marker({
      draggable: false,
      animation: google.maps.Animation.DROP,
      position: new google.maps.LatLng(json[i].lat, json[i].lon),
      content_string: contentString
    }));

    // visual effect
    addMarkerWithTimeout(markers[i], 100*i);

    // single info window
    markers[i].addListener('click', function() {
      infowindow.setContent(this.content_string); 
      infowindow.open(map,this); 
    });
  }
}

// display markers with small delay
function addMarkerWithTimeout(marker, timeout) {
  window.setTimeout(function() {
    marker.setMap(map);
  }, timeout);
}

// removes everything from the map
function clearMap() {
  // removes the markers
  for(var i = 0; i<markers.length; i++) {
    markers[i].setMap(null);
  }
  markers = [];

  // removes the heatmap layer
  heatmap.setMap(null);
  // default button states
  $('#toggle_heatmap').addClass('active');
  $('#toggle_markers').addClass('active');
  $('#toggle_gradient').removeClass('active');
  $('#toggle_opacity').removeClass('active');
  $('#radius_slider').val(35);
}

// API request for absolute or mean value
function getHeatmap() {

  // clears the map
  clearMap();
  // API request data
  var data = {};

  // station's code or empty string for all stations
  var station = $('#select_station').val();
  // pollutant's code
  var pollutant = $('#select_pollutant').val();
  // first date
  // converts date to MySQL date
  var datetime1 = datetime_MYSQL($('#select_date1').val() + ' ' + $('#select_time1').val());

  // creates the request's data
  data['apikey'] = my_apikey;
  data['formula'] = pollutant;
  // if a station has been selected
  if (station != '') {
    data['station_code'] = station;
  }

  // if there is a second date, gets mean value in request
  if ($('#date2').is(':checked')) {
    // second date
    var datetime2 = datetime_MYSQL($('#select_date2').val() + ' ' + $('#select_time2').val());
    data['date_from'] = datetime1;
    data['date_to'] = datetime2;
    data['request'] = 'mean_values';
  // if there is not a second date, gets absolute value
  } else {
    data['date'] = datetime1;
    data['request'] = 'absolute_values';
  }

  // ajax call
  $.getJSON("../api/api.php", data,
  function(json) {
    // error in data parsing (e.g. wrong API)
    if (json.hasOwnProperty('error')) {
      alert(json.error);
    } else {
      // no results
      if (!json.length) {
        alert('No results found.');
      } else {
        // creates the heatmap and the markers
        createHeatmap(json, pollutant, data['request']);
        createMarkers(json, data['request']);
      }
    }
  });

  // prevents reloading
  return false;
}

// converts date to MySQL date
function datetime_MYSQL(datetime) {
  // converts date to JavaScript date
  var d = new Date(datetime);

  // MySQL date format
  mysql_dt = d.getFullYear() + '-' +
    ('0'+(d.getMonth()+1)).slice(-2) + '-' +
    ('0'+d.getDate()).slice(-2) + ' ' +
    ('0'+d.getHours()).slice(-2) + ':' +
    ('0'+d.getMinutes()).slice(-2) + ':00';
  return mysql_dt;
}

// changes buttons' colors
$('.toggle_button').click(function() {
  $(this).toggleClass('active');
});

// second date enabled / disabled
$('#date2').change(function() {
  // enable inputs make them required
  if ($(this).is(':checked')) {
    $('#select_time2').prop('disabled', false);
    $('#select_date2').prop('disabled', false);
    $('#select_time2').prop('required', true);
    $('#select_date2').prop('required', true);
    $('#group_date2').removeClass('inactive');
  }
  // disable inputs and make them optional
  else {
    $('#select_time2').prop('disabled', true);
    $('#select_date2').prop('disabled', true);
    $('#select_time2').prop('required', false);
    $('#select_date2').prop('required', false);
    $('#group_date2').addClass('inactive');
  }
});

// automatic request, returns all stations
$.getJSON("../api/api.php", { request: "stations", apikey: my_apikey }, function(json) {
  if (json.hasOwnProperty('error')) {
    alert(json.error);
  } else {
    for(var i = 0; i<json.length; i++){
      document.getElementById("select_station").innerHTML += 
      "<option value='"+json[i].code+"'>" + json[i].name + " - " + json[i].code + "</option>";
    }
  }
});

// automatic request, returns all pollutants
$.getJSON("../api/api.php", { request: "pollutants", apikey: my_apikey }, function(json) {
  if (json.hasOwnProperty('error')) {
    alert(json.error);
  } else {
    for(var i = 0; i<json.length; i++){
      document.getElementById("select_pollutant").innerHTML += 
      "<option value='"+json[i].formula+"'>" + json[i].name +
      " - " + json[i].formula + " (" + json[i].unit + "/m&sup3;)</option>";
    }
  }
});