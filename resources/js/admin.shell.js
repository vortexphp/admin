/**
 * Admin shell: search overlay (Ctrl/Cmd+K), user menu toggle, outside click + Escape.
 */
(function () {
  var searchRoot = document.getElementById('adm-search-root');
  var searchOpenBtn = document.getElementById('adm-search-open');
  var searchCloseEls = document.querySelectorAll('[data-adm-search-close]');
  var searchInput = document.getElementById('adm-search-input');
  var userToggle = document.getElementById('adm-user-menu-toggle');
  var userPanel = document.getElementById('adm-user-menu-panel');

  function searchIsOpen() {
    return searchRoot && !searchRoot.classList.contains('hidden');
  }

  function openSearch() {
    if (!searchRoot || searchIsOpen()) {
      return;
    }
    searchRoot.classList.remove('hidden');
    searchRoot.setAttribute('aria-hidden', 'false');
    if (searchInput) {
      searchInput.focus();
      searchInput.select();
    }
    document.documentElement.style.overflow = 'hidden';
  }

  function closeSearch() {
    if (!searchRoot || !searchIsOpen()) {
      return;
    }
    searchRoot.classList.add('hidden');
    searchRoot.setAttribute('aria-hidden', 'true');
    document.documentElement.style.overflow = '';
  }

  function closeUserMenu() {
    if (userPanel) {
      userPanel.hidden = true;
    }
    if (userToggle) {
      userToggle.setAttribute('aria-expanded', 'false');
    }
  }

  function toggleUserMenu() {
    if (!userPanel || !userToggle) {
      return;
    }
    var next = userPanel.hidden;
    userPanel.hidden = !next;
    userToggle.setAttribute('aria-expanded', next ? 'true' : 'false');
  }

  if (searchOpenBtn && searchRoot) {
    searchOpenBtn.addEventListener('click', openSearch);
  }
  searchCloseEls.forEach(function (el) {
    el.addEventListener('click', closeSearch);
  });
  if (searchRoot) {
    var searchCard = searchRoot.querySelector('[data-adm-search-card]');
    searchRoot.addEventListener('click', function (e) {
      if (searchCard && !searchCard.contains(e.target)) {
        closeSearch();
      }
    });
  }

  document.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && String(e.key).toLowerCase() === 'k') {
      e.preventDefault();
      if (searchIsOpen()) {
        closeSearch();
      } else {
        openSearch();
      }
      return;
    }
    if (e.key === 'Escape') {
      closeSearch();
      closeUserMenu();
    }
  });

  if (userToggle && userPanel) {
    userToggle.addEventListener('click', function (e) {
      e.stopPropagation();
      toggleUserMenu();
    });
  }

  var userMenuRoot = document.getElementById('adm-user-menu-root');
  document.addEventListener('click', function (e) {
    if (!userPanel || userPanel.hidden) {
      return;
    }
    if (userMenuRoot && userMenuRoot.contains(e.target)) {
      return;
    }
    closeUserMenu();
  });
})();

