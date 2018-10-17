// uses ajax to refresh the stats automatically
function refresh_requests() {
  $.post("welcome.php", { refresh_requests: "true" }, function(data) {
    document.getElementById("requests_count").innerHTML = data;
  });
}

// refresh every one second
setInterval( function() { refresh_requests(); } ,1000);
// initial refresh
refresh_requests();