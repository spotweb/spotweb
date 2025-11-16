(function(){
  function baseHref(){
    var b = document.querySelector('base');
    return b ? b.getAttribute('href') : (location.origin + location.pathname.replace(/index\.php.*/, ''));
  }
  function isOverlayVisible(){
    var ov = document.getElementById('overlay');
    if (!ov) return false;
    try {
      var cs = window.getComputedStyle(ov);
      var shown = cs && cs.display !== 'none' && (ov.offsetWidth + ov.offsetHeight) > 0;
      return shown && ov.childNodes.length > 0; // only treat as visible when it actually contains details
    } catch(e) { return false; }
  }
  document.addEventListener('click', function(ev){
    var a = ev.target && ev.target.closest ? ev.target.closest('a.closeDetails') : null;
    if (!a) return;
    ev.preventDefault();
    ev.stopImmediatePropagation();

    // 1) Try to close SpotWeb overlay if that API exists
    try {
      if (typeof closeDetails === 'function') {
        var scrollLocation = (document.documentElement && document.documentElement.scrollTop) || document.body.scrollTop || 0;
        closeDetails(scrollLocation);
        return;
      }
    } catch(e) {}

    // 2) Try to go back if we have a history entry
    try {
      if (window.history.length > 1) {
        window.history.back();
        setTimeout(function(){
          if (/(\?|&)page=getspot/.test(location.search)) {
            var ref = document.referrer || '';
            if (ref && !/page=getspot/.test(ref)) { location.href = ref; }
            else { location.href = baseHref(); }
          }
        }, 200);
        return;
      }
    } catch(e) {}

    // 3) Fallback to referrer or base
    try {
      var r = document.referrer || '';
      if (r && !/page=getspot/.test(r)) { location.href = r; }
      else { location.href = baseHref(); }
    } catch(e) { location.href = baseHref(); }
  }, true);
})();

(function(){
  function preventFixedOnSpotinfo() {
    try {
      var body = document.body;
      if (!body) { return; }

      function check() {
        if (body.classList.contains('spotinfo') && body.classList.contains('fixed')) {
          body.classList.remove('fixed');
        }
      }

      check();

      if (body.__spotinfoFixedObserver) { return; }
      var observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
          if (mutation.attributeName === 'class') {
            check();
          }
        });
      });
      observer.observe(body, { attributes: true, attributeFilter: ['class'] });
      body.__spotinfoFixedObserver = observer;
    } catch(e) {}
  }

  function markSpotPage(){
    try {
      if (!document.body) return;
      preventFixedOnSpotinfo();
      if (document.body.classList.contains('spotinfo')) {
        return;
      }
      if (document.querySelector('div.details table.spotinfo')) {
        document.body.classList.add('spotinfo-page');
        document.body.classList.add('spotinfo');
      }
    } catch(e) {}
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', markSpotPage);
  } else {
    markSpotPage();
  }
})();

window.addEventListener('DOMContentLoaded', function () {
  var loc = window.location.search || '';
  var href = window.location.href || '';
  var targets = [
    '?page=editsettings',
    '?page=edituserprefs',
    '?page=usermanagement',
    'tplname=usermanagement'
  ];
  if (targets.some(function (needle) {
    return loc.indexOf(needle) !== -1 || href.indexOf(needle) !== -1;
  })) {
    document.body.classList.add('modern-config');
  }
});

