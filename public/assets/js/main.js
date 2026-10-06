/**
 * RMS (Restaurant Management System) - Core App Javascript
 * Handles navigation highlights, UI notifications, responsive toggles,
 * and 2-second auto-dismissing CRUD alerts.
 */

// Universal RMS Toast & Alert System (Immediately available)
(function () {
  window.rmsToast = function (message, type = 'info', onDismiss = null) {
    let toastContainer = document.getElementById('rms-toast-container');
    if (!toastContainer) {
      toastContainer = document.createElement('div');
      toastContainer.id = 'rms-toast-container';
      toastContainer.style.cssText = 'position:fixed;top:16px;right:18px;z-index:999999;display:flex;flex-direction:column;gap:8px;pointer-events:none;';
      document.body.appendChild(toastContainer);
    }

    const toast = document.createElement('div');
    const bgColors = {
      success: '#10b981',
      danger: '#ef4444',
      error: '#ef4444',
      warning: '#f59e0b',
      info: '#4f46e5'
    };
    const icons = {
      success: 'bi-check-circle-fill',
      danger: 'bi-exclamation-octagon-fill',
      error: 'bi-exclamation-octagon-fill',
      warning: 'bi-exclamation-triangle-fill',
      info: 'bi-info-circle-fill'
    };

    const color = bgColors[type] || bgColors.info;
    const icon = icons[type] || icons.info;

    toast.style.cssText = `
      background: ${color};
      color: #fff;
      padding: 7px 14px;
      border-radius: 20px;
      box-shadow: 0 4px 14px rgba(0,0,0,0.2);
      font-size: 0.82rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 7px;
      max-width: 320px;
      pointer-events: auto;
      transform: translateY(-10px);
      opacity: 0;
      transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    `;

    toast.innerHTML = `<i class="bi ${icon}" style="font-size: 0.95rem; flex-shrink: 0;"></i><span style="line-height: 1.25;">${message}</span>`;
    toastContainer.appendChild(toast);

    requestAnimationFrame(() => {
      toast.style.transform = 'translateY(0)';
      toast.style.opacity = '1';
    });

    // Auto-dismiss alert after exactly 2 seconds using setTimeout
    setTimeout(() => {
      toast.style.transform = 'translateY(-10px)';
      toast.style.opacity = '0';
      setTimeout(() => {
        toast.remove();
        if (typeof onDismiss === 'function') onDismiss();
      }, 250);
    }, 2000);
  };

  /**
   * Helper for CRUD operations:
   * Shows alert immediately, then executes action (reload, redirect, or callback)
   * in a 2-second setTimeout function.
   */
  window.showCrudAlert = function (message, type = 'success', action = null) {
    window.rmsToast(message, type);
    if (action) {
      setTimeout(() => {
        if (typeof action === 'function') {
          action();
        } else if (action === 'reload' || action === true) {
          window.location.reload();
        } else if (typeof action === 'string') {
          window.location.href = action;
        }
      }, 2000); // 2-second setTimeout function
    }
  };

  // Auto-dismiss all Bootstrap alerts on page after 2 seconds
  function initAutoDismissAlerts() {
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent):not([data-auto-dismiss-bound])');
    alerts.forEach(alert => {
      alert.setAttribute('data-auto-dismiss-bound', 'true');
      setTimeout(() => {
        alert.style.transition = 'opacity 0.35s ease, transform 0.35s ease';
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-8px)';
        setTimeout(() => {
          if (alert.parentNode) alert.remove();
        }, 350);
      }, 2000); // 2 seconds
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAutoDismissAlerts);
  } else {
    initAutoDismissAlerts();
  }

  /**
   * Universal Direct Print via Hidden Iframe (Direct print, no separate preview)
   */
  window.rmsDirectPrint = function (url) {
    if (!url || url === '#' || url.startsWith('javascript:')) return;
    let iframe = document.getElementById('rms-direct-print-frame');
    if (!iframe) {
      iframe = document.createElement('iframe');
      iframe.id = 'rms-direct-print-frame';
      iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden;pointer-events:none;';
      document.body.appendChild(iframe);
    }

    try {
      const u = new URL(url, window.location.origin);
      u.searchParams.set('autoprint', '1');
      u.searchParams.set('_t', Date.now().toString());

      iframe.onload = function () {
        setTimeout(() => {
          try {
            if (iframe.contentWindow && !iframe.contentWindow._rmsPrintExecuted) {
              iframe.contentWindow._rmsPrintExecuted = true;
              iframe.contentWindow.focus();
              iframe.contentWindow.print();
            }
          } catch (e) {
            console.warn('Direct print execution error:', e);
          }
        }, 120);
      };

      iframe.src = u.toString();
      if (window.rmsToast) {
        window.rmsToast('Sending directly to printer...', 'info');
      }
    } catch (e) {
      console.warn('Invalid direct print URL:', e);
    }
  };

  // Delegated click handler for any direct print elements
  document.addEventListener('click', function (e) {
    const printBtn = e.target.closest('.btn-direct-print, [data-direct-print]');
    if (printBtn) {
      e.preventDefault();
      const targetUrl = printBtn.dataset.directPrint || printBtn.getAttribute('href');
      if (targetUrl) {
        window.rmsDirectPrint(targetUrl);
      }
    }
  });
})();

document.addEventListener('DOMContentLoaded', function () {
  // Highlight active sidebar navigation link based on current URL path
  const currentPath = window.location.pathname.toLowerCase();
  const sidebarLinks = document.querySelectorAll('.app-sidebar .nav-link');

  sidebarLinks.forEach(link => {
    const href = link.getAttribute('href');
    if (!href || href === '#' || href.startsWith('javascript:')) return;

    try {
      const linkPath = new URL(href, window.location.origin).pathname.toLowerCase();
      if (currentPath === linkPath || (linkPath !== '/rms/public/index.php' && currentPath.includes(linkPath))) {
        link.classList.add('active');
        // Open parent treeview if in sub-menu
        const parentTreeview = link.closest('.nav-treeview');
        if (parentTreeview) {
          const parentNavItem = parentTreeview.closest('.nav-item');
          if (parentNavItem) {
            parentNavItem.classList.add('menu-open');
            const parentLink = parentNavItem.querySelector(':scope > .nav-link');
            if (parentLink) parentLink.classList.add('active');
          }
        }
      }
    } catch (e) {}
  });
});
