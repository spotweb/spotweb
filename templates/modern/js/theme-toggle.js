(function () {
  var STORAGE_KEY = 'spotweb_theme';

  function getPref() { return localStorage.getItem(STORAGE_KEY) || 'auto'; }
  function humanize(s) { return s.charAt(0).toUpperCase() + s.slice(1); }
  function resolveMode(pref) { if (pref === 'auto') { return (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light'; } return pref; }
  function updateLabel(mode, pref) {
    var el = document.getElementById('themeCurrent');
    if (!el) return;
    el.textContent = pref === 'auto' ? 'Theme: Auto (' + humanize(mode) + ')' : 'Theme: ' + humanize(mode);
  }
  function apply(pref) {
    var mode = resolveMode(pref);
    document.documentElement.setAttribute('data-theme', mode);
    updateLabel(mode, pref);
  }
  function set(pref) { try { localStorage.setItem(STORAGE_KEY, pref); } catch (e) {} apply(pref); }
  function init() {
    apply(getPref());
    var toolbar = document.getElementById('toolbar');
    // Theme dropdown
    if (toolbar && !document.getElementById('themeCurrent')) {
      var themeWrap = document.createElement('div');
      themeWrap.className = 'toolbarButton theme dropdown right';
      themeWrap.innerHTML = "<ul><li><p><a id=\"themeCurrent\">Theme</a></p>" +
                            "<ul>" +
                            "<li><a href=\"#\" onclick=\"window.SpotwebTheme && window.SpotwebTheme.set('light'); return false;\">Light</a></li>" +
                            "<li><a href=\"#\" onclick=\"window.SpotwebTheme && window.SpotwebTheme.set('dark'); return false;\">Dark</a></li>" +
                            "<li><a href=\"#\" onclick=\"window.SpotwebTheme && window.SpotwebTheme.set('auto'); return false;\">Auto</a></li>" +
                            "</ul></li></ul>";
      toolbar.appendChild(themeWrap);
    }

    // View + Density controls
    if (toolbar && !document.getElementById('viewCurrent')) {
      var viewPref = localStorage.getItem('spotweb_view') || 'cards';
      var densityPref = localStorage.getItem('spotweb_density') || 'cozy';

      var viewWrap = document.createElement('div');
      viewWrap.className = 'toolbarButton theme dropdown right';
      viewWrap.innerHTML = "<ul><li><p><a id=\"viewCurrent\">View: " + (viewPref==='table'?'Table':'Cards') + "</a></p>" +
                          "<ul>" +
                          "<li><a href=\"#\" id=\"viewCards\">Cards</a></li>" +
                          "<li><a href=\"#\" id=\"viewTable\">Table</a></li>" +
                          "<li class=\"sep\"></li>" +
                          "<li><a href=\"#\" id=\"densityCozy\">Density: Cozy</a></li>" +
                          "<li><a href=\"#\" id=\"densityCompact\">Density: Compact</a></li>" +
                          "</ul></li></ul>";
      toolbar.appendChild(viewWrap);

      var updateDensity = function(mode){
        try { localStorage.setItem('spotweb_density', mode); } catch(e){}
        document.documentElement.setAttribute('data-density', mode);
      };

      function setViewCookie(v){ try { document.cookie = 'spotweb_view=' + v + '; path=/; max-age=' + (60*60*24*365); } catch(e){} }
      document.getElementById('viewCards').onclick = function(){
        try { localStorage.setItem('spotweb_view','cards'); } catch(e){}
        setViewCookie('cards');
        var u = new URL(location.href);
        u.searchParams.set('view','cards');
        location.href = u.toString();
        return false;
      };
      document.getElementById('viewTable').onclick = function(){
        try { localStorage.setItem('spotweb_view','table'); } catch(e){}
        setViewCookie('table');
        var u = new URL(location.href);
        u.searchParams.set('view','table');
        location.href = u.toString();
        return false;
      };
      document.getElementById('densityCozy').onclick = function(){ updateDensity('cozy'); return false; };
      document.getElementById('densityCompact').onclick = function(){ updateDensity('compact'); return false; };

      // Apply saved density on load
      document.documentElement.setAttribute('data-density', densityPref);

      // Sync cookie from storage if missing
      try {
        if (!document.cookie.match(/(?:^|; )spotweb_view=/)) {
          var prefView = localStorage.getItem('spotweb_view') || 'cards';
          setViewCookie(prefView);
        }
      } catch(e){}
    }

    // If a view preference exists but URL lacks ?view=, redirect to it
    try {
      var u = new URL(location.href);
      if (!u.searchParams.has('view')) {
        var prefView = localStorage.getItem('spotweb_view');
        if (prefView) { u.searchParams.set('view', prefView); location.replace(u.toString()); }
      }
    } catch(e){}
    try {
      var mql = window.matchMedia('(prefers-color-scheme: dark)');
      var onChange = function(){ if ((localStorage.getItem(STORAGE_KEY) || 'auto') === 'auto') { apply('auto'); } };
      if (mql.addEventListener) { mql.addEventListener('change', onChange); }
      else if (mql.addListener) { mql.addListener(onChange); }
    } catch (e) {}
  }
  window.SpotwebTheme = { init: init, set: set };
  document.addEventListener('DOMContentLoaded', function () { try { window.SpotwebTheme.init(); } catch (e) {} });
})();
