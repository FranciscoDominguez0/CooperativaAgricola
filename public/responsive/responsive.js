/* Small helpers for mobile behaviours: toggle mobile nav, add class on resize if needed */
(function(){
  document.addEventListener('DOMContentLoaded', function(){
    var toggles = document.querySelectorAll('.menu-toggle');
    toggles.forEach(function(toggle){
      toggle.addEventListener('click', function(e){
        var target = document.querySelector(toggle.getAttribute('data-target'));
        if(!target) return;
        target.classList.toggle('open');
      });
    });

    // Optionally add a `mobile` class to body when width <= 768
    function refreshMobileClass(){
      if(window.innerWidth <= 768) document.body.classList.add('mobile');
      else document.body.classList.remove('mobile');
    }
    refreshMobileClass();
    window.addEventListener('resize', refreshMobileClass);
  });
})();
