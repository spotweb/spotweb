(function(){
  function qs(sel, root){ return (root||document).querySelector(sel); }
  function qsa(sel, root){ return Array.prototype.slice.call((root||document).querySelectorAll(sel)); }

  function hasCards(){ return !!qs('.cardsGrid'); }

  var busy = false;
  function nextPage(){ var el = qs('#nextPage'); return el ? parseInt(el.value, 10) : -1; }
  function getURLParams(){ var el = qs('#getURL'); return el ? el.value : ''; }

  function buildNextUrl(){
    var np = nextPage();
    if (!(np > 0)) return null;
    var base = new URL(window.location.href);
    base.searchParams.set('direction','next');
    base.searchParams.set('pagenr', String(np));
    // ensure view=cards persists
    base.searchParams.set('view','cards');
    // Append server-side sort/filter parameters contained in #getURL (already URL-encoded '&key=val' string)
    var extra = getURLParams();
    if (extra) {
      // Merge extra params into URL
      try {
        var tmp = new URL(base.origin + base.pathname + '?' + extra.replace(/^&+/,'') );
        tmp.searchParams.forEach(function(v,k){ base.searchParams.set(k,v); });
      } catch(e){}
    }
    return base.toString();
  }

  function appendFrom(html){
    var tmp = document.createElement('div');
    tmp.innerHTML = html;
    var cards = qsa('.cardsGrid .spotCard', tmp);
    if (cards.length === 0) return false;
    var grid = qs('.cardsGrid');
    cards.forEach(function(n){ grid.appendChild(n); });
    // update paging inputs
    var npEl = tmp.querySelector('#nextPage');
    if (npEl) { var cur = qs('#nextPage'); if (cur) cur.value = npEl.value; }
    // toggle pager visibility
    var pager = document.querySelector('.cardsPager');
    if (pager) {
      if (nextPage() > 0) {
        pager.style.display = '';
        var btn = pager.querySelector('.loadMore');
        if (btn) { var u = buildNextUrl(); if (u) btn.setAttribute('href', u); }
      } else {
        pager.style.display = 'none';
      }
    }
    return true;
  }

  function loadMore(){
    var url = buildNextUrl();
    if (!url) return;
    window.location.href = url; // Simple full navigation for reliability
  }

  function onScroll(){ /* disabled: explicit Next page button only */ }

  // Sentinel observer for more reliable triggers
  function observeSentinel(){ /* disabled */ }

  function ensurePager(){
    var pager = document.querySelector(".cardsPager");
    if (!pager) { return; }
    if (nextPage() > 0) {
      pager.style.display = "";
    } else {
      pager.style.display = "none";
    }
  }

  function init(){
    if (!hasCards()) return;
    ensurePager();
    observeSentinel();
    // No autoscroll; use button only
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
