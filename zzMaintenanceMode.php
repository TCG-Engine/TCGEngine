<?php
// Maintenance mode toggle.
//
//   https://swustats.net/TCGEngine/zzMaintenanceMode.php?rootName=SWUDeck
//   https://petranaki.net/TCGEngine/zzMaintenanceMode.php?rootName=SWUSim
//
// Freezes WRITES so the SET_NNN migration can rebuild the stats tables without losing submissions.
// Reads are never blocked — see AppCore/SWU/Maintenance.php for why, and for what each level covers.
//
// This page is never itself gated: turning maintenance off must not depend on maintenance being off.

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

require_once __DIR__ . '/AppCore/SWU/Maintenance.php';
require_once __DIR__ . '/AccountFiles/AccountDatabaseAPI.php';
require_once __DIR__ . '/AccountFiles/AccountSessionAPI.php';

$error = CheckLoggedInUserMod();
if ($error !== '') { http_response_code(403); echo 'Forbidden: ' . htmlspecialchars($error); exit; }

CheckSession();
if (empty($_SESSION['maintenance_csrf'])) $_SESSION['maintenance_csrf'] = bin2hex(random_bytes(32));
$csrf = (string)$_SESSION['maintenance_csrf'];

$rootName = isset($_GET['rootName']) ? preg_replace('/[^A-Za-z0-9_]/', '', $_GET['rootName']) : 'SWUDeck';
if (!is_dir(SWUMaintenanceRoot() . '/' . $rootName)) {
    http_response_code(400);
    echo 'Unknown rootName: ' . htmlspecialchars($rootName);
    exit;
}

$notice = null; $noticeBad = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) {
        $notice = 'CSRF check failed — reload the page and try again.'; $noticeBad = true;
    } else {
        $level  = (string)($_POST['level'] ?? '');
        $reason = trim((string)($_POST['reason'] ?? ''));
        if ($level !== 'off' && $reason === '') $reason = 'Scheduled maintenance';
        $by = (string)($_SESSION['userid'] ?? $_SESSION['usersUid'] ?? 'mod');

        $r = SWUMaintenanceSet($rootName, $level, $reason, $by);
        if (!empty($r['ok'])) {
            $notice = $level === 'off'
                ? 'Maintenance mode is OFF. Writes are flowing again.'
                : "Maintenance mode is ON at level '$level'.";
            error_log("SWU maintenance [$rootName] set to '$level' by $by: $reason");
        } else {
            $notice = 'FAILED: ' . $r['error']; $noticeBad = true;
        }
    }
}

$state = SWUMaintenanceState($rootName);
$path  = SWUMaintenanceFlagPath($rootName);
$on    = $state['level'] !== 'off';

$since = '';
if ($on && $state['since']) {
    $mins = max(0, (int)floor((time() - $state['since']) / 60));
    $since = date('Y-m-d H:i', $state['since']) . ' (' . ($mins < 60 ? "{$mins}m" : floor($mins / 60) . 'h ' . ($mins % 60) . 'm') . ' ago)';
}

$LEVELS = [
    'off' => [
        'label' => 'Off — normal service',
        'desc'  => 'Everything flows. This is the only state the site should be left in.',
    ],
    'stats' => [
        'label' => 'Stats writes paused',
        'desc'  => 'Blocks SubmitGameResult and SubmitManualGameResult. The site stays fully usable '
                 . 'and readable. This is what the DATABASE migration needs: the rebuild reads a '
                 . 'snapshot, so any stat write before the swap lands in the table that becomes '
                 . '_old and is lost without an error.',
    ],
    'full' => [
        'label' => 'Full write freeze',
        'desc'  => 'Also blocks deck saves. This is what the DECK-FILE rewrite needs — autosave-on-'
                 . 'open racing a format change is what caused the Leader2 sideboard data loss.',
    ],
];

header('Content-Type: text/html; charset=utf-8');
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?>
<!doctype html><meta charset="utf-8"><title>Maintenance mode — <?= $h($rootName) ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
  :root { color-scheme: light dark; }
  body { font: 14px/1.6 ui-monospace, Menlo, monospace; padding: 24px; max-width: 780px; margin: 0 auto; }
  h1 { font-size: 18px; margin: 0 0 4px; }
  .sub { opacity: .7; margin: 0 0 20px; }
  .state { padding: 14px 16px; border-radius: 8px; margin: 0 0 20px; border: 2px solid; }
  .state.on  { border-color: #c62828; background: rgba(198,40,40,.08); }
  .state.off { border-color: #2e7d32; background: rgba(46,125,50,.08); }
  .state b { font-size: 16px; }
  .notice { padding: 10px 14px; border-radius: 6px; margin: 0 0 16px; border-left: 4px solid #2e7d32;
            background: rgba(46,125,50,.08); }
  .notice.bad { border-left-color: #c62828; background: rgba(198,40,40,.08); }
  form { margin: 0 0 12px; }
  fieldset { border: 1px solid rgba(128,128,128,.4); border-radius: 8px; padding: 12px 16px; margin: 0 0 14px; }
  legend { padding: 0 6px; font-weight: bold; }
  .desc { opacity: .8; margin: 6px 0 10px; }
  button { font: inherit; padding: 8px 16px; border-radius: 6px; cursor: pointer; border: 1px solid; }
  button.danger { background: #c62828; color: #fff; border-color: #c62828; }
  button.safe { background: #2e7d32; color: #fff; border-color: #2e7d32; }
  input[type=text] { font: inherit; padding: 6px 8px; width: 100%; box-sizing: border-box;
                     border-radius: 6px; border: 1px solid rgba(128,128,128,.5); background: transparent;
                     color: inherit; margin: 0 0 10px; }
  code { background: rgba(128,128,128,.15); padding: 1px 5px; border-radius: 4px; }
  .foot { margin-top: 26px; opacity: .75; font-size: 13px; }
  table { border-collapse: collapse; width: 100%; margin: 8px 0; }
  td, th { text-align: left; padding: 4px 10px 4px 0; vertical-align: top; }
</style>

<h1>Maintenance mode — <?= $h($rootName) ?></h1>
<p class="sub">Freezes writes. Never blocks reads.</p>

<?php if ($notice): ?>
  <div class="notice<?= $noticeBad ? ' bad' : '' ?>"><?= $h($notice) ?></div>
<?php endif; ?>

<div class="state <?= $on ? 'on' : 'off' ?>">
  <b><?= $on ? 'MAINTENANCE IS ON' : 'Normal service' ?></b>
  <?php if ($on): ?>
    <table>
      <tr><th>level</th><td><code><?= $h($state['level']) ?></code> — <?= $h($LEVELS[$state['level']]['label'] ?? '') ?></td></tr>
      <tr><th>reason</th><td><?= $h($state['reason']) ?></td></tr>
      <tr><th>since</th><td><?= $h($since) ?></td></tr>
      <tr><th>set by</th><td><?= $h($state['by'] ?: '—') ?></td></tr>
    </table>
    <?php if (!empty($state['malformed'])): ?>
      <p><b>⚠ The flag file could not be parsed.</b> It is being treated as a full freeze because
      something deliberately created it. Fix or delete <code><?= $h($path) ?></code>.</p>
    <?php endif; ?>
  <?php else: ?>
    <p style="margin:6px 0 0">All writes are flowing.</p>
  <?php endif; ?>
</div>

<?php foreach ($LEVELS as $key => $meta): if ($key === $state['level']) continue; ?>
  <form method="post">
    <fieldset>
      <legend><?= $h($meta['label']) ?></legend>
      <div class="desc"><?= $h($meta['desc']) ?></div>
      <?php if ($key !== 'off'): ?>
        <input type="text" name="reason" placeholder="Reason (shown in logs; the API returns it)"
               value="<?= $h($state['reason'] ?: 'SET_NNN identity migration') ?>">
      <?php endif; ?>
      <input type="hidden" name="csrf" value="<?= $h($csrf) ?>">
      <input type="hidden" name="level" value="<?= $h($key) ?>">
      <button class="<?= $key === 'off' ? 'safe' : 'danger' ?>">
        <?= $key === 'off' ? 'Turn maintenance OFF' : 'Switch to ' . $h($key) ?>
      </button>
    </fieldset>
  </form>
<?php endforeach; ?>

<div class="foot">
  <p><b>Flag file:</b> <code><?= $h($path) ?></code><br>
  The state is a file, not a database row — deliberately, so it still works while the database is
  mid-migration or down. If this page is ever unreachable, <code>rm</code> that file to restore
  service; its absence means "off".</p>
  <p><b>What is never blocked:</b> reads. Meta pages, deck pages and card stats keep working at
  every level, and the <code>zz</code> tools stay reachable — including this one.</p>
  <p><b>Callers see</b> HTTP <code>503</code> with a <code>Retry-After</code> header and the
  endpoint's usual <code>{"success":false,"error":…}</code> body, so a well-behaved client retries
  instead of discarding the submission. Karabast will still lose any game it does not retry, so
  keep the window short.</p>
</div>
