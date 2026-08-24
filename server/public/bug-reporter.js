/*!
 * divStrong Bug Reporter — embed widget
 * Drop-in: <script src="https://divstrong.com/bug-reporter.js" data-site-key="..." defer></script>
 */
(function () {
  'use strict';

  if (window.__divStrongBugReporterLoaded) return;
  window.__divStrongBugReporterLoaded = true;

  // --- locate this script and pull config ---
  var thisScript =
    document.currentScript ||
    (function () {
      var s = document.getElementsByTagName('script');
      for (var i = s.length - 1; i >= 0; i--) {
        if (s[i].src && s[i].src.indexOf('bug-reporter.js') !== -1) return s[i];
      }
      return null;
    })();

  if (!thisScript) return;

  var siteKey = thisScript.getAttribute('data-site-key');
  if (!siteKey) {
    console.warn('[divStrong bug-reporter] missing data-site-key, aborting.');
    return;
  }

  var srcUrl;
  try {
    srcUrl = new URL(thisScript.src);
  } catch (e) {
    return;
  }
  var apiOrigin = srcUrl.origin;
  var apiUrl = apiOrigin + '/api/bug-reports';
  var configUrl = apiOrigin + '/api/bug-reports/config?key=' + encodeURIComponent(siteKey);
  // html2canvas-pro supports modern CSS color functions (oklch, etc.) used by
  // Tailwind v4 / Filament v4; the original html2canvas 1.4.1 throws on them.
  // It registers the same window.html2canvas global, so it's a drop-in.
  var html2canvasUrl = 'https://cdn.jsdelivr.net/npm/html2canvas-pro@1.5.8/dist/html2canvas-pro.min.js';

  // --- capture last N console errors before user reports ---
  var errorBuffer = [];
  var MAX_ERRORS = 20;
  // Capture starts immediately -- errors thrown during page load happen long
  // before the activation check below resolves, and they are the ones worth
  // attaching to a report. If the site turns out to be inactive we undo all of
  // it in teardownErrorCapture() so a disabled widget leaves the page untouched.
  var origError = console.error;
  var patchedError = function () {
    try {
      var parts = [];
      for (var i = 0; i < arguments.length; i++) {
        var a = arguments[i];
        parts.push(a instanceof Error ? a.stack || a.message : typeof a === 'string' ? a : safeStringify(a));
      }
      errorBuffer.push(parts.join(' '));
      if (errorBuffer.length > MAX_ERRORS) errorBuffer.shift();
    } catch (e) { /* swallow */ }
    return origError.apply(console, arguments);
  };
  console.error = patchedError;

  function onWindowError(e) {
    errorBuffer.push((e.message || 'error') + ' @ ' + (e.filename || '?') + ':' + (e.lineno || '?'));
    if (errorBuffer.length > MAX_ERRORS) errorBuffer.shift();
  }
  function onRejection(e) {
    errorBuffer.push('unhandledrejection: ' + safeStringify(e.reason));
    if (errorBuffer.length > MAX_ERRORS) errorBuffer.shift();
  }
  window.addEventListener('error', onWindowError);
  window.addEventListener('unhandledrejection', onRejection);

  function teardownErrorCapture() {
    // Only restore if nothing else wrapped console.error after us; clobbering
    // another tool's patch would be worse than leaving ours in place.
    if (console.error === patchedError) console.error = origError;
    window.removeEventListener('error', onWindowError);
    window.removeEventListener('unhandledrejection', onRejection);
    errorBuffer.length = 0;
  }

  function safeStringify(v) {
    try { return JSON.stringify(v); } catch (e) { return String(v); }
  }

  // --- DOM mount via Shadow DOM so client CSS can't bleed in ---
  function init() {
    if (document.getElementById('divstrong-bug-reporter-host')) return;

    var host = document.createElement('div');
    host.id = 'divstrong-bug-reporter-host';
    host.style.cssText = 'all:initial;position:fixed;z-index:2147483646';
    document.body.appendChild(host);
    var root = host.attachShadow({ mode: 'closed' });

    root.innerHTML =
      '<style>' +
        ':host, * { box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }' +
        '.fab { position: fixed; right: 20px; bottom: 20px; width: 52px; height: 52px; border-radius: 999px;' +
              ' background: #1f2937; color: #fff; border: none; cursor: pointer; box-shadow: 0 6px 16px rgba(0,0,0,.25);' +
              ' display: flex; align-items: center; justify-content: center; transition: transform .15s ease, background .15s ease; }' +
        '.fab:hover { background: #111827; transform: translateY(-1px); }' +
        '.fab svg { width: 24px; height: 24px; }' +
        '.overlay { position: fixed; inset: 0; background: rgba(0,0,0,.45); display: none; align-items: center; justify-content: center; padding: 16px; }' +
        '.overlay.open { display: flex; }' +
        '.modal { background: #fff; color: #111827; border-radius: 12px; width: 100%; max-width: 460px;' +
                ' padding: 20px 22px; box-shadow: 0 24px 64px rgba(0,0,0,.3); }' +
        '.modal h2 { margin: 0 0 4px; font-size: 18px; font-weight: 600; }' +
        '.modal p.help { margin: 0 0 14px; font-size: 13px; color: #6b7280; }' +
        '.field { margin-bottom: 12px; }' +
        '.field label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 4px; }' +
        '.field textarea, .field input, .field select { width: 100%; padding: 9px 11px; border: 1px solid #d1d5db; border-radius: 6px;' +
                                        ' font-size: 14px; font-family: inherit; color: #111827; background: #fff; }' +
        '.field textarea { min-height: 110px; resize: vertical; }' +
        '.field textarea:focus, .field input:focus, .field select:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.15); }' +
        '.actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 10px; }' +
        '.btn { padding: 9px 16px; border-radius: 6px; font-size: 14px; font-weight: 500; border: none; cursor: pointer; }' +
        '.btn-cancel { background: transparent; color: #6b7280; }' +
        '.btn-cancel:hover { color: #111827; }' +
        '.btn-send { background: #2563eb; color: #fff; }' +
        '.btn-send:hover { background: #1d4ed8; }' +
        '.btn-send:disabled { background: #93c5fd; cursor: wait; }' +
        '.status { font-size: 13px; margin-top: 8px; min-height: 18px; }' +
        '.status.error { color: #dc2626; }' +
        '.status.success { color: #16a34a; }' +
        '.brand { font-size: 11px; color: #9ca3af; margin-top: 12px; text-align: right; }' +
      '</style>' +
      '<button class="fab" type="button" aria-label="Report a bug" title="Report a bug">' +
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
          '<path d="M8 2l1.88 1.88"/><path d="M14.12 3.88L16 2"/>' +
          '<path d="M9 7.13v-1a3.003 3.003 0 1 1 6 0v1"/>' +
          '<path d="M12 20c-3.3 0-6-2.7-6-6v-3a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v3c0 3.3-2.7 6-6 6z"/>' +
          '<path d="M12 20v-9"/><path d="M6.53 9C4.6 8.8 3 7.1 3 5"/>' +
          '<path d="M6 13H2"/><path d="M3 21c0-2.1 1.7-3.9 3.8-4"/>' +
          '<path d="M20.97 5c0 2.1-1.6 3.8-3.5 4"/>' +
          '<path d="M22 13h-4"/><path d="M17.2 17c2.1.1 3.8 1.9 3.8 4"/>' +
        '</svg>' +
      '</button>' +
      '<div class="overlay" role="dialog" aria-modal="true" aria-labelledby="ds-bug-title">' +
        '<div class="modal">' +
          '<h2 id="ds-bug-title">Report a bug</h2>' +
          '<p class="help">A screenshot of this page will be attached automatically.</p>' +
          '<div class="field">' +
            '<label for="ds-bug-what">What Happened?</label>' +
            '<textarea id="ds-bug-what" placeholder="Tell us what went wrong, what you expected, and any steps you took..."></textarea>' +
          '</div>' +
          '<div class="field">' +
            '<label for="ds-bug-severity">Severity</label>' +
            '<select id="ds-bug-severity">' +
              '<option value="unsure" selected>Unsure</option>' +
              '<option value="low">Low</option>' +
              '<option value="high">High</option>' +
              '<option value="critical">Critical</option>' +
            '</select>' +
          '</div>' +
          '<div class="field">' +
            '<label for="ds-bug-email">Your email (optional)</label>' +
            '<input type="email" id="ds-bug-email" placeholder="you@example.com" />' +
          '</div>' +
          '<div class="actions">' +
            '<button class="btn btn-cancel" type="button" data-action="cancel">Cancel</button>' +
            '<button class="btn btn-send" type="button" data-action="send">Send</button>' +
          '</div>' +
          '<div class="status" data-role="status"></div>' +
          '<div class="brand">Powered by divStrong</div>' +
        '</div>' +
      '</div>';

    var fab = root.querySelector('.fab');
    var overlay = root.querySelector('.overlay');
    var modal = root.querySelector('.modal');
    var textarea = root.getElementById('ds-bug-what');
    var severitySelect = root.getElementById('ds-bug-severity');
    var emailInput = root.getElementById('ds-bug-email');
    var cancelBtn = root.querySelector('[data-action="cancel"]');
    var sendBtn = root.querySelector('[data-action="send"]');
    var statusEl = root.querySelector('[data-role="status"]');

    function open() {
      statusEl.textContent = '';
      statusEl.className = 'status';
      sendBtn.disabled = false;
      sendBtn.textContent = 'Send';
      overlay.classList.add('open');
      setTimeout(function () { textarea.focus(); }, 50);
    }
    function close() {
      overlay.classList.remove('open');
      textarea.value = '';
      emailInput.value = '';
      severitySelect.value = 'unsure';
    }

    fab.addEventListener('click', open);
    cancelBtn.addEventListener('click', close);
    overlay.addEventListener('click', function (e) { if (e.target === overlay) close(); });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && overlay.classList.contains('open')) close();
    });

    sendBtn.addEventListener('click', function () {
      var what = (textarea.value || '').trim();
      if (!what) {
        statusEl.textContent = 'Please describe what happened.';
        statusEl.className = 'status error';
        textarea.focus();
        return;
      }
      sendBtn.disabled = true;
      sendBtn.textContent = 'Sending...';
      statusEl.textContent = 'Capturing screenshot...';
      statusEl.className = 'status';

      // Hide widget before screenshot.
      host.style.display = 'none';

      loadHtml2Canvas()
        .then(function () {
          return window.html2canvas(document.body, {
            useCORS: true,
            allowTaint: false,
            logging: false,
            backgroundColor: '#ffffff',
            // scale 1 (not devicePixelRatio): retina pages would otherwise
            // produce a screenshot several times larger than needed.
            scale: 1,
          });
        })
        .then(function (canvas) {
          host.style.display = '';
          // Downscale very large captures so the upload stays well under the
          // server's size limit (tall / high-DPI pages can otherwise exceed it).
          var MAX_W = 1600, MAX_H = 4000;
          var ratio = Math.min(1, MAX_W / canvas.width, MAX_H / canvas.height);
          var out = canvas;
          if (ratio < 1) {
            out = document.createElement('canvas');
            out.width = Math.max(1, Math.round(canvas.width * ratio));
            out.height = Math.max(1, Math.round(canvas.height * ratio));
            var ctx = out.getContext('2d');
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, out.width, out.height);
            ctx.drawImage(canvas, 0, 0, out.width, out.height);
          }
          return new Promise(function (resolve) {
            // JPEG keeps the payload small and reliable vs lossless PNG.
            out.toBlob(function (blob) { resolve(blob); }, 'image/jpeg', 0.82);
          });
        })
        .catch(function (e) {
          // Screenshot capture is best-effort: if html2canvas fails (e.g. an
          // unsupported CSS color, tainted image), still send the report
          // without a screenshot rather than losing it entirely.
          host.style.display = '';
          try { console.warn('[bug-reporter] screenshot capture failed, sending without it:', e && e.message); } catch (_) {}
          return null;
        })
        .then(function (blob) {
          host.style.display = '';
          statusEl.textContent = 'Sending report...';
          var fd = new FormData();
          fd.append('what_happened', what);
          fd.append('url', window.location.href);
          fd.append('severity', severitySelect.value || 'unsure');
          if (emailInput.value) fd.append('reporter_email', emailInput.value.trim());
          fd.append('viewport_width', String(window.innerWidth || 0));
          fd.append('viewport_height', String(window.innerHeight || 0));
          if (document.referrer) fd.append('referrer', document.referrer);
          errorBuffer.forEach(function (e) { fd.append('console_errors[]', e); });
          if (blob) fd.append('screenshot', blob, 'screenshot.jpg');

          return fetch(apiUrl, {
            method: 'POST',
            headers: { 'X-Site-Key': siteKey, 'Accept': 'application/json' },
            body: fd,
            credentials: 'omit',
            mode: 'cors',
          });
        })
        .then(function (res) {
          if (!res.ok) throw new Error('Server responded ' + res.status);
          statusEl.textContent = 'Thanks — report sent.';
          statusEl.className = 'status success';
          sendBtn.textContent = 'Sent';
          setTimeout(close, 1400);
        })
        .catch(function (err) {
          host.style.display = '';
          statusEl.textContent = 'Could not send report. Please try again.';
          statusEl.className = 'status error';
          sendBtn.disabled = false;
          sendBtn.textContent = 'Send';
          if (window.console && console.warn) console.warn('[bug-reporter]', err);
        });
    });
  }

  function loadHtml2Canvas() {
    if (window.html2canvas) return Promise.resolve();
    return new Promise(function (resolve, reject) {
      var s = document.createElement('script');
      s.src = html2canvasUrl;
      s.async = true;
      s.onload = function () { resolve(); };
      s.onerror = function () { reject(new Error('Failed to load html2canvas')); };
      document.head.appendChild(s);
    });
  }

  // The is_active toggle lives in the divStrong admin, but this file is a
  // static asset -- so ask the API whether the site is switched on before
  // rendering anything. Fails closed: if we cannot confirm the site is active
  // we render nothing, since a submission would be rejected anyway.
  function isSiteActive() {
    return fetch(configUrl, { method: 'GET', credentials: 'omit', mode: 'cors' })
      .then(function (res) { return res.ok ? res.json() : null; })
      .then(function (body) { return !!(body && body.active); })
      .catch(function () { return false; });
  }

  isSiteActive().then(function (active) {
    if (!active) {
      teardownErrorCapture();
      return;
    }
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', init);
    } else {
      init();
    }
  });
})();
