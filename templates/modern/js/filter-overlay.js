document.addEventListener('DOMContentLoaded', function () {
  var panel = document.querySelector('div#filter .sidebarPanel.advancedSearch');
  if (!panel) {
    return;
  }

  var updateState = function () {
    var visible = panel.offsetWidth > 0 && panel.offsetHeight > 0 && panel.style.display !== 'none';
    document.body.classList.toggle('filters-open', visible);
  };

  var observer = new MutationObserver(updateState);
  observer.observe(panel, { attributes: true, attributeFilter: ['style', 'class'] });

  document.addEventListener('click', function () {
    setTimeout(updateState, 25);
  });

  updateState();
});
