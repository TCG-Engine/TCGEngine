<?php

include_once __DIR__ . '/../AccountFiles/AccountSessionAPI.php';
include_once __DIR__ . '/../Database/ConnectionManager.php';
include_once __DIR__ . '/../AzukiSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/../SharedUI/Sites/AzukiSim/MenuBar.php';
include_once __DIR__ . '/../SharedUI/Sites/AzukiSim/Header.php';

function LoadAzukiDecks($userID) {
  $conn = GetLocalMySQLConnection();
  $stmt = $conn->prepare(
    "SELECT * FROM ownership WHERE assetType = 1 AND assetOwner = ? AND assetStatus = 1 ORDER BY assetIdentifier DESC"
  );
  $stmt->bind_param('i', $userID);
  $stmt->execute();
  $result = $stmt->get_result();
  $decks = [];
  while ($row = $result->fetch_assoc()) $decks[] = $row;
  $stmt->close();
  $conn->close();
  return $decks;
}

$decks = IsUserLoggedIn() ? LoadAzukiDecks(LoggedInUser()) : [];
$error = trim((string)($_GET['error'] ?? ''));

?>
<style>
  .azuki-deck-home { max-width: 1120px; margin: 28px auto; padding: 0 18px 48px; color: #eef4ff; }
  .azuki-deck-hero, .azuki-deck-list { background: rgba(18,24,34,.92); border: 1px solid rgba(86,166,255,.35); border-radius: 12px; box-shadow: 0 14px 40px rgba(0,0,0,.3); }
  .azuki-deck-hero { display:flex; justify-content:space-between; align-items:center; gap:18px; padding:24px; }
  .azuki-deck-hero h1 { margin:0 0 6px; font-family:Teko,sans-serif; font-size:42px; line-height:1; }
  .azuki-deck-hero p { margin:0; color:#b9c7d8; }
  .azuki-deck-actions { display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end; }
  .azuki-deck-button { display:inline-flex; align-items:center; justify-content:center; min-height:42px; padding:0 18px; border:1px solid #4ca7ff; color:white; background:#1769aa; text-decoration:none; border-radius:6px; font-weight:700; cursor:pointer; }
  .azuki-deck-button.secondary { background:rgba(37,53,72,.9); }
  .azuki-deck-import { display:flex; gap:8px; margin-top:12px; }
  .azuki-deck-import input { min-width:320px; height:42px; box-sizing:border-box; background:#0d131c; color:white; border:1px solid #44576d; border-radius:6px; padding:0 12px; }
  .azuki-deck-list { margin-top:18px; padding:18px; }
  .azuki-deck-list h2 { margin:0 0 14px; }
  .azuki-deck-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:12px; }
  .azuki-deck-card { display:flex; gap:12px; align-items:center; min-height:92px; padding:12px; color:white; text-decoration:none; background:rgba(9,15,23,.88); border:1px solid rgba(255,255,255,.12); border-radius:8px; }
  .azuki-deck-card:hover { border-color:#4ca7ff; transform:translateY(-1px); }
  .azuki-deck-card img { width:58px; height:82px; object-fit:cover; border-radius:5px; }
  .azuki-deck-card strong { display:block; font-size:17px; }
  .azuki-deck-card span { color:#9fb0c2; font-size:13px; }
  .azuki-deck-empty, .azuki-deck-error { padding:16px; border-radius:8px; color:#c7d4e3; background:rgba(255,255,255,.04); }
  .azuki-deck-error { margin-top:14px; color:#ffd0d0; border:1px solid rgba(255,100,100,.35); }
  @media(max-width:700px){ .home-header{box-sizing:border-box;padding-top:58px!important}.azuki-deck-hero{align-items:stretch;flex-direction:column}.azuki-deck-actions{justify-content:flex-start}.azuki-deck-import{flex-direction:column}.azuki-deck-import input{min-width:0;width:100%} }
</style>
<main class="azuki-deck-home">
  <section class="azuki-deck-hero">
    <div>
      <h1>AzukiDeck</h1>
      <p>Build and save Azuki decks with the SWUDeck editor experience.</p>
      <?php if (IsUserLoggedIn()): ?>
      <form class="azuki-deck-import" action="CreateDeck.php" method="get">
        <input name="deckLink" aria-label="Import deck link" placeholder="Optional thegateikz.com deck URL or slug">
        <button class="azuki-deck-button secondary" type="submit">Import Deck</button>
      </form>
      <?php endif; ?>
    </div>
    <div class="azuki-deck-actions">
      <?php if (IsUserLoggedIn()): ?>
        <a id="createDeckButton" class="azuki-deck-button" href="CreateDeck.php">New Deck</a>
      <?php else: ?>
        <a class="azuki-deck-button" href="/TCGEngine/SharedUI/Sites/AzukiSim/Signup.php?redirect=%2FTCGEngine%2FAzukiDeck%2F">Create account</a>
        <a class="azuki-deck-button secondary" href="/TCGEngine/SharedUI/Sites/AzukiSim/LoginPage.php?redirect=%2FTCGEngine%2FAzukiDeck%2F">Log in</a>
      <?php endif; ?>
    </div>
  </section>

  <?php if ($error !== ''): ?><div class="azuki-deck-error"><?php echo htmlspecialchars($error, ENT_QUOTES); ?></div><?php endif; ?>

  <section class="azuki-deck-list">
    <h2>Your decks</h2>
    <?php if (!IsUserLoggedIn()): ?>
      <div class="azuki-deck-empty">Log in to create and manage Azuki decks.</div>
    <?php elseif (empty($decks)): ?>
      <div class="azuki-deck-empty">No Azuki decks yet. Create one to open the builder.</div>
    <?php else: ?>
      <div class="azuki-deck-grid">
      <?php foreach ($decks as $deck):
        $deckID = intval($deck['assetIdentifier']);
        $name = trim((string)$deck['assetName']) !== '' ? $deck['assetName'] : 'Deck #' . $deckID;
        $leaderID = trim((string)($deck['keyIndicator1'] ?? ''));
      ?>
        <a class="azuki-deck-card" href="../NextTurn.php?gameName=<?php echo $deckID; ?>&amp;playerID=1&amp;folderPath=AzukiDeck">
          <?php if ($leaderID !== ''): ?><img src="../AzukiSim/WebpImages/<?php echo rawurlencode($leaderID); ?>.webp" alt=""><?php endif; ?>
          <div><strong><?php echo htmlspecialchars($name, ENT_QUOTES); ?></strong><span>Open deck builder</span></div>
        </a>
      <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</main>
