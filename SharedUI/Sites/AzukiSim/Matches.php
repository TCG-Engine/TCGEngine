<?php

require_once __DIR__ . '/../../Render/PageEntry.php';
_SitePageSessionBootstrap();
include_once __DIR__ . '/../../../AccountFiles/AccountSessionAPI.php';
include_once __DIR__ . '/../../../AzukiSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/../../../AzukiSim/Custom/Stats.php';

if(!IsUserLoggedIn()) {
    header('Location: ./LoginPage.php?redirect=%2FTCGEngine%2FSharedUI%2FSites%2FAzukiSim%2FMatches.php');
    exit();
}

include_once __DIR__ . '/MenuBar.php';
include_once __DIR__ . '/Header.php';

$history = AzukiLoadMatchHistory(LoggedInUser(), 100);
$matches = $history['matches'];
$totalMatches = intval($history['wins']) + intval($history['losses']) + intval($history['draws']);
$winRate = $totalMatches > 0 ? intval(round((intval($history['wins']) / $totalMatches) * 100)) : 0;

function AzukiMatchEsc($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function AzukiMatchRelativeTime($timestamp) {
    $time = strtotime((string)$timestamp);
    if($time === false) return '';
    $seconds = max(0, time() - $time);
    if($seconds < 60) return 'just now';
    if($seconds < 3600) return intval($seconds / 60) . 'm ago';
    if($seconds < 86400) return intval($seconds / 3600) . 'h ago';
    if($seconds < 604800) return intval($seconds / 86400) . 'd ago';
    if($seconds < 2592000) return intval($seconds / 604800) . 'w ago';
    if($seconds < 31536000) return intval($seconds / 2592000) . 'mo ago';
    return intval($seconds / 31536000) . 'y ago';
}

function AzukiMatchReasonLabel($reason) {
    switch((string)$reason) {
        case 'concession': return 'Concession';
        case 'deck_out': return 'Deck out';
        case 'leader_ko': return 'Leader KO';
        default: return ucwords(str_replace('_', ' ', (string)$reason));
    }
}

?>
<main class="azuki-matches-page">
  <header class="azuki-matches-heading">
    <p>Combat record</p>
    <h1>Matches</h1>
  </header>

  <section class="azuki-match-summary" aria-label="Match summary">
    <div class="azuki-match-totals">
      <span>Wins <strong class="is-win"><?php echo intval($history['wins']); ?></strong></span>
      <span>Losses <strong class="is-loss"><?php echo intval($history['losses']); ?></strong></span>
      <span>Draws <strong><?php echo intval($history['draws']); ?></strong></span>
    </div>
    <div class="azuki-win-rate-copy">
      <span>Win rate</span>
      <strong><?php echo $winRate; ?>%</strong>
    </div>
    <div class="azuki-win-rate-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo $winRate; ?>">
      <span style="width: <?php echo $winRate; ?>%"></span>
    </div>
  </section>

  <section class="azuki-match-list" aria-label="Recent matches">
    <?php if(empty($matches)): ?>
      <div class="azuki-match-empty">
        <span aria-hidden="true">戦</span>
        <h2>No completed matches yet</h2>
        <p>Your results will appear here after you finish a game while signed in.</p>
        <a href="./MainMenu.php">Prepare your next deck</a>
      </div>
    <?php else: ?>
      <?php foreach($matches as $match):
        $won = strtoupper((string)$match['result']) === 'W';
        // Azuki presents generic key-card slot 1 as the opposing leader.
        $opponentLeader = trim((string)$match['opponentKeyCard1ID']);
        $opponentLeaderName = $opponentLeader !== '' ? CardName($opponentLeader) : '';
        $modeLabel = (string)$match['gameMode'] === 'rlbot' ? 'Training' : 'Player match';
      ?>
        <article class="azuki-match-row <?php echo $won ? 'is-win' : 'is-loss'; ?>">
          <time datetime="<?php echo AzukiMatchEsc(date(DATE_ATOM, strtotime((string)$match['completedAt']))); ?>">
            <?php echo AzukiMatchEsc(AzukiMatchRelativeTime($match['completedAt'])); ?>
          </time>
          <div class="azuki-match-opponent-leader">
            <?php if($opponentLeader !== ''): ?>
              <img src="/TCGEngine/AzukiSim/crops/<?php echo rawurlencode($opponentLeader); ?>_cropped.png" alt="">
            <?php else: ?>
              <span aria-hidden="true">?</span>
            <?php endif; ?>
          </div>
          <div class="azuki-match-opponent">
            <small>vs</small>
            <strong><?php echo AzukiMatchEsc($match['opponentName']); ?></strong>
            <?php if($opponentLeaderName !== ''): ?><span><?php echo AzukiMatchEsc($opponentLeaderName); ?></span><?php endif; ?>
          </div>
          <div class="azuki-match-deck">
            <small>Your deck</small>
            <?php if(intval($match['deckID']) > 0): ?>
              <a href="/TCGEngine/NextTurn.php?gameName=<?php echo intval($match['deckID']); ?>&amp;playerID=1&amp;folderPath=AzukiDeck"><?php echo AzukiMatchEsc($match['deckName']); ?></a>
            <?php else: ?>
              <strong><?php echo AzukiMatchEsc($match['deckName']); ?></strong>
            <?php endif; ?>
          </div>
          <div class="azuki-match-result">
            <span class="azuki-result-mark"><?php echo $won ? 'W' : 'L'; ?></span>
            <strong><?php echo $won ? 'Victory' : 'Defeat'; ?></strong>
            <small><?php echo intval($match['turnCount']); ?> turns</small>
          </div>
          <div class="azuki-match-tags">
            <span><?php echo AzukiMatchEsc($modeLabel); ?></span>
            <span><?php echo !empty($match['wentFirst']) ? 'Went first' : 'Went second'; ?></span>
            <span><?php echo AzukiMatchEsc(AzukiMatchReasonLabel($match['endReason'])); ?></span>
          </div>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </section>
</main>

<?php include_once __DIR__ . '/Disclaimer.php'; ?>
