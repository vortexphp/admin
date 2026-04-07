/**
 * Persists panel light/dark preference in localStorage under admin-theme (light | dark).
 * Expects an inline script in layout to have applied the initial .dark class on <html>.
 */
(function () {
  var key = 'admin-theme';
  var root = document.documentElement;

  function apply(theme) {
    if (theme === 'dark') {
      root.classList.add('dark');
    } else {
      root.classList.remove('dark');
    }
  }

  function persist(theme) {
    try {
      localStorage.setItem(key, theme);
    } catch (e) {
      /* ignore */
    }
  }

  function bindToggle(button) {
    button.addEventListener('click', function () {
      var next = root.classList.contains('dark') ? 'light' : 'dark';
      apply(next);
      persist(next);
      button.setAttribute('aria-label', next === 'dark' ? 'Switch to light theme' : 'Switch to dark theme');
    });
  }

  var btn = document.getElementById('adm-theme-toggle');
  if (btn) {
    bindToggle(btn);
    btn.setAttribute(
      'aria-label',
      root.classList.contains('dark') ? 'Switch to light theme' : 'Switch to dark theme',
    );
  }
})();
