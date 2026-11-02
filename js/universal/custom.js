(function($) {
 "use strict";
	
	$(function(){
	
		$(".scrollup").pageup();
		$(".scrollup2").pageup();
		$(".scrollup3").pageup();
		$(".scrollup4").pageup();
		$(".scrollup5").pageup();
		$(".scrollup6").pageup();
		$(".scrollup7").pageup();
		$(".scrollup8").pageup();
		$(".scrollup9").pageup();
		$(".scrollup10").pageup();
	
	})

	//.parallax(xPosition, speedFactor, outerHeight) options:
	//xPosition - Horizontal position of the element
	//inertia - speed to move relative to vertical scroll. Example: 0.1 is one tenth the speed of scrolling, 2 is twice the speed of scrolling
	//outerHeight (true/false) - Whether or not jQuery should use it's outerHeight option to determine when a section is in the viewport
	$('.parallax').parallax("50%", 0.3);
	$('.parallax2').parallax("50%", 0.3);
	$('.parallax3').parallax("50%", 0.3);
	$('.parallax4').parallax("50%", 0.3);
	$('.parallax5').parallax("50%", 0.3);
	$('.parallax6').parallax("50%", 0.3);
	$('.parallax7').parallax("50%", 0.3);
	$('.parallax8').parallax("50%", 0.3);
	$('.parallax9').parallax("50%", 0.3);
	$('.parallax10').parallax("50%", 0.3);
	$('.parallax11').parallax("50%", 0.3);
	$('.parallax12').parallax("50%", 0.3);
	$('.parallax13').parallax("50%", 0.3);
	$('.parallax14').parallax("50%", 0.3);
	$('.parallax15').parallax("50%", 0.3);
	$('.parallax16').parallax("50%", 0.3);
	$('.parallax17').parallax("50%", 0.3);
	$('.parallax18').parallax("50%", 0.3);
	$('.parallax19').parallax("50%", 0.3);
	$('.parallax20').parallax("50%", 0.3);
	$('.parallax21').parallax("50%", 0.3);
	$('.parallax22').parallax("50%", 0.3);
	$('.parallax23').parallax("50%", 0.3);
	$('.parallax24').parallax("50%", 0.3);
	$('.parallax25').parallax("50%", 0.3);
	$('.parallax26').parallax("50%", 0.3);
	$('.parallax27').parallax("50%", 0.3);
	$('.parallax28').parallax("50%", 0.3);
	$('.parallax29').parallax("50%", 0.3);
	$('.parallax30').parallax("50%", 0.3);
	$('.parallax31').parallax("50%", 0.3);
	$('.parallax32').parallax("50%", 0.3);
	$('.parallax33').parallax("50%", 0.3);
	$('.parallax34').parallax("50%", 0.3);
	$('.parallax35').parallax("50%", 0.3);
	$('.parallax36').parallax("50%", 0.3);
	$('.parallax37').parallax("50%", 0.3);
	$('.parallax38').parallax("50%", 0.3);
	$('.parallax39').parallax("50%", 0.3);
	$('.parallax40').parallax("50%", 0.3);
	$('.parallax41').parallax("50%", 0.3);
	$('.parallax42').parallax("50%", 0.3);
	$('.parallax43').parallax("50%", 0.3);
	$('.parallax44').parallax("50%", 0.3);
	$('.parallax45').parallax("50%", 0.3);
	$('.parallax46').parallax("50%", 0.3);
	$('.parallax47').parallax("50%", 0.3);
	$('.parallax48').parallax("50%", 0.3);
	$('.parallax49').parallax("50%", 0.3);
	$('.parallax50').parallax("50%", 0.3);
	$('.parallax51').parallax("50%", 0.3);
	$('.parallax52').parallax("50%", 0.3);
	$('.parallax53').parallax("50%", 0.3);
	$('.parallax54').parallax("50%", 0.3);
	$('.parallax55').parallax("50%", 0.3);
	$('.parallax56').parallax("50%", 0.3);
	$('.parallax57').parallax("50%", 0.3);
	$('.parallax58').parallax("50%", 0.3);
	$('.parallax59').parallax("50%", 0.3);
	$('.parallax60').parallax("50%", 0.3);
	$('.parallax61').parallax("50%", 0.3);
	$('.parallax62').parallax("50%", 0.3);
	$('.parallax63').parallax("50%", 0.3);
	$('.parallax64').parallax("50%", 0.3);
	$('.parallax65').parallax("50%", 0.3);
	$('.parallax66').parallax("50%", 0.3);
	$('.parallax67').parallax("50%", 0.3);
	$('.parallax68').parallax("50%", 0.3);
	$('.parallax69').parallax("50%", 0.3);
	$('.parallax70').parallax("50%", 0.3);
	$('.parallax71').parallax("50%", 0.3);
	$('.parallax72').parallax("50%", 0.3);
	
	// scroll slider
	$(function(){
		$("#scrollslider").scrollSlider();
	})
	





    $('.google-map').click(function () {
        $('.google-map iframe').css("pointer-events", "all");
    });
    
    $( ".google-map" ).mouseleave(function() {
      $('.google-map iframe').css("pointer-events", "none"); 
    });
 
	
	$('.google-map2').click(function () {
        $('.google-map2 iframe').css("pointer-events", "all");
    });
    
    $( ".google-map2" ).mouseleave(function() {
      $('.google-map2 iframe').css("pointer-events", "none"); 
    });
	

	var contentToToggle = $('.social');
	$('.js-btn-open').on('click', function (e) {
		e.preventDefault();
		contentToToggle.toggleClass('open');
	});

	
	AOS.init({
	  duration: 1000
	});
	
	   // AOS.init({
	   //    offset: 200,
	   //    duration: 600,
	   //    easing: 'ease-in-sine',
	   //    delay: 100,
	   //  });
	
	//# sourceURL=pen.js
	
	
$('.imgslider').each(function() {
  var $this = $(this);
  var $group = $this.find('.slide_group');
  var $slides = $this.find('.slide');
  var bulletArray = [];
  var currentIndex = 0;
  var timeout;
  
  function move(newIndex) {
    var animateLeft, slideLeft;
    
    advance();
    
    if ($group.is(':animated') || currentIndex === newIndex) {
      return;
    }
    
    bulletArray[currentIndex].removeClass('active');
    bulletArray[newIndex].addClass('active');
    
    if (newIndex > currentIndex) {
      slideLeft = '100%';
      animateLeft = '-100%';
    } else {
      slideLeft = '-100%';
      animateLeft = '100%';
    }
    
    $slides.eq(newIndex).css({
      display: 'block',
      left: slideLeft
    });
    $group.animate({
      left: animateLeft
    }, function() {
      $slides.eq(currentIndex).css({
        display: 'none'
      });
      $slides.eq(newIndex).css({
        left: 0
      });
      $group.css({
        left: 0
      });
      currentIndex = newIndex;
    });
  }
  
  function advance() {
    clearTimeout(timeout);
    timeout = setTimeout(function() {
      if (currentIndex < ($slides.length - 1)) {
        move(currentIndex + 1);
      } else {
        move(0);
      }
    }, 4000);
  }
  
  $('.next_btn').on('click', function() {
    if (currentIndex < ($slides.length - 1)) {
      move(currentIndex + 1);
    } else {
      move(0);
    }
  });
  
  $('.previous_btn').on('click', function() {
    if (currentIndex !== 0) {
      move(currentIndex - 1);
    } else {
      move(3);
    }
  });
  
  $.each($slides, function(index) {
    var $button = $('<a class="slide_btn">&bull;</a>');
    
    if (index === currentIndex) {
      $button.addClass('active');
    }
    $button.on('click', function() {
      move(index);
    }).appendTo('.slide_buttons');
    bulletArray.push($button);
  });
  
  advance();
});

	
})(jQuery);