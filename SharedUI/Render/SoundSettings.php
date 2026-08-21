<?php
// Profile "Sounds" section — the ACCOUNT-level half of the mute setting. Its in-game twin is the
// gear menu's "Mute sounds" row (SWUSim/Custom/GameLayoutShared.php); both write through
// SWUSim/PlayerSettingsApi.php, so the two surfaces cannot drift.
//
// Two layers by design (product decision 2026-08-20):
//   • ACCOUNT (here) — follows you to any device, and is what a Profile toggle has to mean.
//   • BROWSER (localStorage, in-game) — wins locally, and is the only layer a LOGGED-OUT player has.
// A browser choice made while signed out is promoted onto the account on first login (the `promote`
// action), which is why "never set" is stored as absent rather than as 0.
require_once __DIR__ . '/../../SWUSim/PlayerSettings.php';

function RenderSoundSettings(int $userId): string {
    $muted = SWUSimAccountMuted($userId) === true;
    $chk   = $muted ? ' checked' : '';
    ob_start(); ?>
<div class="sound-settings">
  <label class="sound-settings-row">
    <input type="checkbox" id="profileMuteSounds"<?= $chk ?>>
    <span>Mute sounds</span>
  </label>
  <p class="sound-settings-hint">Turns off every in-game sound, including the chime that plays when it becomes your turn.</p>
  <span id="profileMuteStatus" class="sound-settings-status" role="status" aria-live="polite"></span>
</div>
<style>
  .sound-settings-row { display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 600; }
  .sound-settings-row input { width: 18px; height: 18px; cursor: pointer; }
  .sound-settings-hint { margin: 6px 0 0; opacity: 0.7; font-size: 13px; }
  .sound-settings-status { display: inline-block; margin-top: 6px; min-height: 1em; font-size: 13px; opacity: 0.85; }
  .sound-settings-status.is-error { color: #ffb3bd; }
</style>
<script>
(function () {
  var box = document.getElementById('profileMuteSounds');
  var out = document.getElementById('profileMuteStatus');
  if (!box) return;
  function base() { var p = location.pathname, i = p.indexOf('/TCGEngine/'); return i >= 0 ? p.slice(0, i + 11) : '/TCGEngine/'; }
  box.addEventListener('change', function () {
    var want = box.checked;
    if (out) { out.className = 'sound-settings-status'; out.textContent = 'Saving…'; }
    // Keep this browser's own layer in step with the account change, so a Profile edit is reflected
    // immediately in a game open in the same browser rather than being masked by a stale local value.
    try {
      if (window.TCGSettings) window.TCGSettings.set('MuteSounds', want, { rootName: 'SWUSim', type: 'boolean' });
    } catch (e) {}
    var x = new XMLHttpRequest();
    x.open('POST', base() + 'SWUSim/PlayerSettingsApi.php', true);
    x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    x.onload = function () {
      var ok = false;
      try { ok = !!JSON.parse(x.responseText).success; } catch (e) {}
      if (out) {
        out.className = 'sound-settings-status' + (ok ? '' : ' is-error');
        out.textContent = ok ? 'Saved.' : 'Could not save — try again.';
      }
      if (!ok) box.checked = !want;   // roll the control back so it never shows an unsaved state
    };
    x.onerror = function () {
      if (out) { out.className = 'sound-settings-status is-error'; out.textContent = 'Could not save — try again.'; }
      box.checked = !want;
    };
    x.send('action=set&mute=' + (want ? '1' : '0'));
  });
})();
</script>
<?php
    return (string)ob_get_clean();
}
