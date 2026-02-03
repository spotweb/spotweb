(function () {
  function setup() {
    var originalOpenSpot = window.openSpot;
    if (!originalOpenSpot || originalOpenSpot._modernWrapped) {
      return;
    }

    function scrollToComments() {
      var target = document.getElementById('comments');
      if (!target) {
        return false;
      }
      try {
        target.scrollIntoView({ block: 'start', behavior: 'auto' });
      } catch (e) {
        target.scrollIntoView();
      }
      return true;
    }

    function openSpotWrapped(id, url) {
      var shouldScrollToComments = false;
      if (typeof url === 'string' && url.indexOf('#comments') !== -1) {
        shouldScrollToComments = true;
        url = url.split('#')[0];
      }

      var result = originalOpenSpot(id, url);

      if (shouldScrollToComments) {
        var tries = 0;
        var timer = setInterval(function () {
          tries += 1;
          if (scrollToComments() || tries > 20) {
            clearInterval(timer);
          }
        }, 200);
      }

      return result;
    }

    openSpotWrapped._modernWrapped = true;
    window.openSpot = openSpotWrapped;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setup);
  } else {
    setup();
  }
})();
