// SWUSim localized card art (images only): rewrites SWU card-art URLs to AppCore/SWU/Images/i18n/<lang>/
// when that language's manifest lists the file, else leaves (or puts back) the English URL.
//
// Why a URL rewriter and not a per-card lookup: ~30 client sites build card-art URLs, and several rewrite
// an existing URL as a string (/concat/ -> /WebpImages/ for the hover preview, toggling _back for a leader
// flip). A localized URL keeps the folder and filename and only inserts i18n/<lang>/, so those string
// rewrites keep working, and this file is the only place that knows about languages.
//
// Applied three ways: the ResolveCardImageUrl hook in Card() (so the board requests the localized file
// first); a MutationObserver that rewrites <img src> and inline background-image anywhere else; and a
// whole-document pass when the manifest arrives or the language changes (in either direction).
//
// Setting: browser TCGSettings "CardLanguage" (rootName SWUSim) wins; else the account value
// (window.SWU_ACCOUNT_CARD_LANGUAGE, via SWUSim/PlayerSettingsApi.php); else English.
// Design: docs/superpowers/specs/2026-09-17-swusim-card-i18n-images-design.md §2
(function (root) {
  var LANGS = ['en', 'es', 'it', 'fr'];
  var FOLDERS = ['WebpImages', 'concat', 'crops'];
  var CORPUS = 'AppCore/SWU/Images/';
  var URL_RE = /^(.*?AppCore\/SWU\/Images\/)(?:i18n\/[a-z]{2}\/)?(WebpImages|concat|crops)\/([^\/?#]+?)(_cropped)?\.(webp|png)((?:[?#].*)?)$/;

  function normalizeLang(v) {
    if (v === null || v === undefined) return null;
    var s = String(v).toLowerCase().trim();
    return LANGS.indexOf(s) >= 0 ? s : null;
  }

  function indexManifest(json) {
    var out = {};
    FOLDERS.forEach(function (f) {
      out[f] = new Set(json && Array.isArray(json[f]) ? json[f] : []);
    });
    return out;
  }

  // Each folder only ever holds one file shape: crops are "<stem>_cropped.png"; WebpImages and concat
  // are plain "<stem>.webp". A URL whose extension/suffix doesn't match its folder can't correspond to
  // anything the i18n pipeline (or manifest) would ever produce, so it must never be localized — doing
  // so would point at a file (e.g. a localized crops/<stem>.png) that can't exist.
  function shapeMatchesFolder(folder, cropped, ext) {
    if (folder === 'crops') return cropped === '_cropped' && ext === 'png';
    return cropped === '' && ext === 'webp'; // WebpImages, concat
  }

  function rewriteUrl(url, lang, manifest) {
    if (typeof url !== 'string' || url.indexOf(CORPUS) < 0) return url;
    var m = URL_RE.exec(url);
    if (!m) return url;
    var prefix = m[1], folder = m[2], stem = m[3], cropped = m[4] || '', ext = m[5], tail = m[6] || '';
    var file = folder + '/' + stem + cropped + '.' + ext + tail;
    var l = normalizeLang(lang);
    var localized = l && l !== 'en' && stem.indexOf('mock_') !== 0 && shapeMatchesFolder(folder, cropped, ext)
      && manifest && manifest[folder] && manifest[folder].has(stem);
    return prefix + (localized ? 'i18n/' + l + '/' : '') + file;
  }

  function rewriteCss(value, lang, manifest) {
    if (typeof value !== 'string' || value.indexOf(CORPUS) < 0) return value;
    return value.replace(/url\((['"]?)([^'")]+)\1\)/g, function (all, q, u) {
      return 'url(' + q + rewriteUrl(u, lang, manifest) + q + ')';
    });
  }

  var api = { LANGS: LANGS, normalizeLang: normalizeLang, indexManifest: indexManifest, rewriteUrl: rewriteUrl, rewriteCss: rewriteCss, shapeMatchesFolder: shapeMatchesFolder };
  if (typeof module !== 'undefined' && module.exports) { module.exports = api; return; }
  if (!root || !root.document) return;

  var state = { lang: 'en', manifest: null, pending: null };
  var SETTING = { rootName: 'SWUSim', type: 'string', defaultValue: null };

  function browserLanguage() {
    try {
      if (root.TCGSettings && typeof root.TCGSettings.get === 'function') return normalizeLang(root.TCGSettings.get('CardLanguage', SETTING));
      return normalizeLang(root.localStorage.getItem('tcg.settings.SWUSim.CardLanguage'));
    } catch (e) { return null; }
  }

  function effectiveLanguage() {
    return browserLanguage() || normalizeLang(root.SWU_ACCOUNT_CARD_LANGUAGE) || 'en';
  }

  function artRoot() {
    return String(root.SWUArtRoot || '/TCGEngine/AppCore/SWU/Images').replace(/\/$/, '');
  }

  function currentUrl(url) { return rewriteUrl(url, state.lang, state.manifest); }

  function applyToElement(el) {
    if (!el || el.nodeType !== 1) return;
    if (el.tagName === 'IMG') {
      var src = el.getAttribute('src');
      if (src && src.indexOf(CORPUS) >= 0) {
        var next = currentUrl(src);
        if (next !== src) el.setAttribute('src', next);
      }
    }
    var style = el.getAttribute('style');
    if (style && style.indexOf(CORPUS) >= 0) {
      var nextStyle = rewriteCss(style, state.lang, state.manifest);
      if (nextStyle !== style) el.setAttribute('style', nextStyle);
    }
  }

  function applyToTree(node) {
    if (!node || node.nodeType !== 1) return;
    applyToElement(node);
    var list = node.querySelectorAll('img[src*="' + CORPUS + '"], [style*="' + CORPUS + '"]');
    for (var i = 0; i < list.length; i++) applyToElement(list[i]);
  }

  function applyToDocument() { applyToTree(root.document.documentElement); }

  function loadManifest(lang) {
    if (lang === 'en' || typeof root.fetch !== 'function') return Promise.resolve(null);
    return root.fetch(artRoot() + '/i18n/' + lang + '/manifest.json', { cache: 'no-cache', credentials: 'same-origin' })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (j) { return j ? indexManifest(j) : null; })
      .catch(function () { return null; });
  }

  // Switch languages only once the new manifest is in hand, so the page never points at files it has not
  // confirmed exist. A newer call supersedes an older one still in flight.
  function activate(lang) {
    var l = normalizeLang(lang) || 'en';
    var token = {};
    state.pending = token;
    return loadManifest(l).then(function (manifest) {
      if (state.pending !== token) return;
      state.lang = l;
      state.manifest = manifest;
      applyToDocument();
    });
  }

  function postSetting(body) {
    try {
      var p = root.location.pathname, i = p.indexOf('/TCGEngine/');
      var base = i >= 0 ? p.slice(0, i + 11) : '/TCGEngine/';
      var x = new XMLHttpRequest();
      x.open('POST', base + 'SWUSim/PlayerSettingsApi.php', true);
      x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      x.send(body);
    } catch (e) {}
  }

  function setLanguage(lang) {
    var l = normalizeLang(lang) || 'en';
    try { if (root.TCGSettings) root.TCGSettings.set('CardLanguage', l, SETTING); } catch (e) {}
    if (root.SWU_LOGGED_IN) {
      root.SWU_ACCOUNT_CARD_LANGUAGE = l;
      postSetting('action=setCardLanguage&lang=' + encodeURIComponent(l));
    }
    return activate(l);
  }

  // A choice made in this browser before logging in is carried onto an account that has never set one.
  function promoteOnLogin() {
    if (!root.SWU_LOGGED_IN || root.SWU_ACCOUNT_CARD_LANGUAGE !== null) return;
    var local = browserLanguage();
    if (!local) return;
    root.SWU_ACCOUNT_CARD_LANGUAGE = local;
    postSetting('action=promoteCardLanguage&lang=' + encodeURIComponent(local));
  }

  function start() {
    if (typeof root.MutationObserver === 'function') {
      // Runs in English too: a node rendered from a cached string may still carry an i18n URL from a
      // language the player has switched away from, and rewriteUrl puts it back.
      new root.MutationObserver(function (records) {
        for (var i = 0; i < records.length; i++) {
          var rec = records[i];
          if (rec.type === 'attributes') applyToElement(rec.target);
          else for (var j = 0; j < rec.addedNodes.length; j++) applyToTree(rec.addedNodes[j]);
        }
      }).observe(root.document.documentElement, { subtree: true, childList: true, attributes: true, attributeFilter: ['src', 'style'] });
    }
    promoteOnLogin();
    activate(effectiveLanguage());
  }

  if (typeof root.ResolveCardImageUrl !== 'function') {
    root.ResolveCardImageUrl = function (logicalCardNumber, src) { return currentUrl(src); };
  }
  root.SWUCardI18n = {
    LANGS: LANGS, effectiveLanguage: effectiveLanguage, setLanguage: setLanguage, activate: activate,
    applyToDocument: applyToDocument, currentUrl: currentUrl, rewriteUrl: rewriteUrl, rewriteCss: rewriteCss,
  };
  if (root.document.readyState === 'loading') root.document.addEventListener('DOMContentLoaded', start);
  else start();
})(typeof window !== 'undefined' ? window : undefined);
