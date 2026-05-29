  </main><!-- /.admin-main -->
</div><!-- /.admin-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
  'use strict';

  var wrapper   = document.getElementById('adminWrapper');
  var sidebar   = document.getElementById('adminSidebar');
  var toggle    = document.getElementById('sidebarToggle');
  var chevron   = toggle.querySelector('.sidebar-chevron');
  var hoverZone = document.getElementById('sidebarHoverZone');
  var KEY       = 'irm_sidebar';
  var peekTimer;

  function getState() { return localStorage.getItem(KEY) || 'pinned'; }

  function applyState(s) {
    if (s === 'collapsed') {
      wrapper.classList.add('sidebar-collapsed');
      wrapper.classList.remove('sidebar-peek');
      chevron.textContent = '›'; /* › */
    } else {
      wrapper.classList.remove('sidebar-collapsed', 'sidebar-peek');
      chevron.textContent = '‹'; /* ‹ */
    }
  }

  /* Restore saved state immediately */
  applyState(getState());

  /* Toggle button */
  toggle.addEventListener('click', function () {
    var next = getState() === 'pinned' ? 'collapsed' : 'pinned';
    localStorage.setItem(KEY, next);
    applyState(next);
  });

  /* Floating peek on hover when collapsed */
  function showPeek() {
    clearTimeout(peekTimer);
    if (getState() === 'collapsed') {
      wrapper.classList.add('sidebar-peek');
    }
  }

  function hidePeek() {
    clearTimeout(peekTimer);
    peekTimer = setTimeout(function () {
      wrapper.classList.remove('sidebar-peek');
    }, 180);
  }

  hoverZone.addEventListener('mouseenter', showPeek);
  hoverZone.addEventListener('mouseleave', hidePeek);
  sidebar.addEventListener('mouseenter', showPeek);
  sidebar.addEventListener('mouseleave', hidePeek);
  toggle.addEventListener('mouseenter', showPeek);
  toggle.addEventListener('mouseleave', hidePeek);

}());
</script>
</body>
</html>
