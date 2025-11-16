(function(){
  function qs(sel, root){ return (root||document).querySelector(sel); }
  function qsa(sel, root){ return Array.prototype.slice.call((root||document).querySelectorAll(sel)); }

  function isTableView(){
    var grid = qs('.cardsGrid');
    var table = qs('table.spots');
    var u = new URL(location.href);
    return !!table && u.searchParams.get('view') === 'table';
  }

  function nextPage(){ var el = qs('#nextPage'); return el ? parseInt(el.value, 10) : -1; }
  function getURLParams(){ var el = qs('#getURL'); return el ? el.value : ''; }

  // Thumbnails disabled in table view
  function enhanceThumbs(root){ return; }

  var busy = false;
  function buildNextUrl(){
    var np = nextPage();
    if (!(np > 0)) return null;
    var base = new URL(window.location.href);
    base.searchParams.set('direction','next');
    base.searchParams.set('pagenr', String(np));
    base.searchParams.set('view','table');
    var extra = getURLParams();
    if (extra) {
      try { var tmp = new URL(base.origin + base.pathname + '?' + extra.replace(/^&+/,'')); tmp.searchParams.forEach(function(v,k){ base.searchParams.set(k,v); }); } catch(e){}
    }
    return base.toString();
  }

  function appendTable(html){
    var tmp = document.createElement('div'); tmp.innerHTML = html;
    var newRows = qsa('table.spots tbody#spots tr', tmp);
    if (newRows.length === 0) return false;
    var tbody = qs('table.spots tbody#spots');
    newRows.forEach(function(r){ tbody.appendChild(r); });
    // update nextPage
    var npEl = tmp.querySelector('#nextPage');
    if (npEl) { var cur = qs('#nextPage'); if (cur) cur.value = npEl.value; }
    // Thumbnails disabled
    return true;
  }

  function loadMore(){
    if (busy) return;
    var url = buildNextUrl();
    if (!url) return;
    busy = true;
    fetch(url, { credentials: 'same-origin' })
      .then(function(r){ return r.text(); })
      .then(function(html){ appendTable(html); })
      .catch(function(){})
      .finally(function(){ busy = false; });
  }

  function ensurePager(){
    if (qs('.tablePager')) return;
    var pager = document.createElement('div');
    pager.className = 'tablePager';
    var a = document.createElement('a');
    a.href = '#'; a.className = 'loadMore'; a.textContent = 'Load more';
    a.addEventListener('click', function(ev){ ev.preventDefault(); loadMore(); });
    pager.appendChild(a);
    var spots = qs('.spots');
    if (spots) spots.appendChild(pager);
  }

  function onScroll(){
    if (!isTableView()) return;
    var threshold = 1000;
    var scrolled = window.scrollY + window.innerHeight;
    var full = document.documentElement.scrollHeight;
    if (full - scrolled < threshold) loadMore();
  }

  function init(){
    if (!isTableView()) return;
    // Thumbnails disabled
    ensurePager();
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
  document.addEventListener('scroll', onScroll, { passive: true });
})();
