$(window, document, undefined).ready(function() {

  $('input').blur(function() {
    var $this = $(this);
    if ($this.val())
      $this.addClass('used');
    else
      $this.removeClass('used');
  });

  var $ripples = $('.ripples');

  $ripples.on('click.Ripples', function(e) {

    var $this = $(this);
    var $offset = $this.parent().offset();
    var $circle = $this.find('.ripplesCircle');

    var x = e.pageX - $offset.left;
    var y = e.pageY - $offset.top;

    $circle.css({
      top: y + 'px',
      left: x + 'px'
    });

    $this.addClass('is-active');

  });

  $ripples.on('animationend webkitAnimationEnd mozAnimationEnd oanimationend MSAnimationEnd', function(e) {
  	$(this).removeClass('is-active');
  });

});

function checkPass() {
  if ($('#repassword').val().length>0) {
    if ($('#password').val() == $('#repassword').val()) {
      document.getElementById('repassword').style.backgroundColor = 'rgba(200, 250, 150, 0.5)';
    }
    else {
      document.getElementById('repassword').style.backgroundColor = 'rgba(255, 200, 200, 0.5)';
    }
  } else {
    document.getElementById('repassword').style.backgroundColor = 'transparent';
  }
};