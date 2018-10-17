// uses ajax to refresh the stats automatically
function refresh_requests() {
  $.post("panel.php", { refresh_requests: "true" }, function(data) {
    document.getElementById("requests_count").innerHTML = data;
  });
}

function refresh_keys() {
  $.post("panel.php", { refresh_keys: "true" }, function(data) {
    document.getElementById("top_apikeys").innerHTML = data;
  });
}

function refresh_api() {
  $.post("panel.php", { refresh_api: "true" }, function(data) {
    document.getElementById("apikeys_count").innerHTML = data;
  });
}

// event handlers for the menu buttons
$('#menu_stations').click(function(){
  $('#menu_stations').addClass('active');
  $('#menu_stats').removeClass('active');
  
  $('#stations').removeClass('hide');
  $('#stats').addClass('hide');
}); 

$('#menu_stats').click(function(){
  $('#menu_stats').addClass('active');
  $('#menu_stations').removeClass('active');
  
  $('#stats').removeClass('hide');
  $('#stations').addClass('hide');
});

// swap between adding and editing stations
$('#edit_button').click(function(){
  $('#stations_edit').removeClass('hide');
  $('#stations_add').addClass('hide');
});

$('#add_button').click(function(){
  $('#stations_add').removeClass('hide');
  $('#stations_edit').addClass('hide');
});

// station removing visual effect
$("input:checkbox").on("change", function () {
  $next = $(this).parent().next();
  $next.toggleClass('strike');
  $next.next().toggleClass('strike');
  $next.next().next().toggleClass('strike');
  $next.next().next().next().toggleClass('strike');
  $next.next().next().next().next().toggleClass('strike');
});

// refresh every one second
setInterval( function() { refresh_requests(); } ,1000);
setInterval( function() { refresh_keys(); } ,1000);
setInterval( function() { refresh_api(); } ,1000);

// initial refresh
refresh_requests();
refresh_keys();
refresh_api();

// map initialization
function initMap() {
  var greece =  {lat: 38.585033, lng: 22.783782};
  var map = new google.maps.Map(document.getElementById('map'), {
    zoom: 7,
    center: greece,
    mapTypeId: 'roadmap'
  });

  // marker initialization
  var marker = new google.maps.Marker({
    position: greece,
    map: map,
    title: 'Μετακινείστε τον δείκτη',
    animation: google.maps.Animation.DROP,
    draggable: true
  });

  // updates forms after letting the marker
  marker.addListener('dragend', function(evt) {
    $('#latitude').val(this.getPosition().lat());
    $('#longitude').val(this.getPosition().lng());
  });
}
