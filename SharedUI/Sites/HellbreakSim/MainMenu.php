<?php
include_once __DIR__ . '/MenuBar.php';
include_once __DIR__ . '/../../../AccountFiles/AccountSessionAPI.php';
include_once __DIR__ . '/../../../Database/ConnectionManager.php';
include_once __DIR__ . '/../../../HellbreakSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/Header.php';

$cardCount = count(GetAllCardIds());
$fullImageCount = count(glob(__DIR__ . '/../../../HellbreakSim/WebpImages/*.webp') ?: []);
$browseImages = glob(__DIR__ . '/../../../HellbreakSim/concat/*.webp') ?: [];
$browseImageCount = count(array_filter($browseImages, function($path) {
  $name = pathinfo($path, PATHINFO_FILENAME);
  return !preg_match('/_(back|token)$/', $name) && filesize($path) >= 8000;
}));
$decks = [];
if (IsUserLoggedIn()) {
  $conn = GetLocalMySQLConnection();
  if ($conn) {
    $userID = (string)LoggedInUser();
    $stmt = $conn->prepare('SELECT assetIdentifier, assetName, keyIndicator1, keyIndicator2, lastUpdated FROM ownership WHERE assetType = 1 AND assetOwner = ? AND assetStatus = 1 ORDER BY assetIdentifier DESC');
    if ($stmt) {
      $stmt->bind_param('s', $userID);
      $stmt->execute();
      $result = $stmt->get_result();
      while ($row = $result->fetch_assoc()) {
        $deckID = (string)$row['assetIdentifier'];
        if (is_file(__DIR__ . '/../../../HellbreakDeck/Games/' . $deckID . '/Gamestate.txt')) $decks[] = $row;
      }
      $stmt->close();
    }
    $conn->close();
  }
}
?>
<main class="hellbreak-shell">
  <section class="hellbreak-hero">
    <div><h2>Build now. Break out later.</h2><p>Hellbreak Deck and HellbreakSim share one card catalog and one image library. The simulator is staged for rules work while the deck builder is ready for revealed cards.</p></div>
    <div class="hellbreak-status">
      <span class="hellbreak-badge<?php echo $cardCount ? '' : ' warn'; ?>"><?php echo $cardCount; ?> cards imported</span>
      <span class="hellbreak-badge<?php echo $browseImageCount ? '' : ' warn'; ?>"><?php echo $browseImageCount; ?> deck-ready images</span>
    </div>
  </section>

  <div class="hellbreak-grid">
    <section class="hellbreak-panel full">
      <h3>Deck Library</h3>
      <p>Create a new list or continue editing one of your Hellbreak decks.</p>
      <div class="hellbreak-actions">
        <?php if (IsUserLoggedIn()): ?><a class="hellbreak-button" href="/TCGEngine/HellbreakDeck/CreateDeck.php">Create Deck</a>
        <?php else: ?><a class="hellbreak-button" href="/TCGEngine/SharedUI/Sites/HellbreakSim/LoginPage.php?redirect=%2FTCGEngine%2FSharedUI%2FSites%2FHellbreakSim%2FMainMenu.php">Log in to build</a><?php endif; ?>
      </div>
      <div class="hellbreak-decks">
        <?php if ($decks): foreach ($decks as $deck):
          $deckID = (string)$deck['assetIdentifier'];
          $deckName = trim((string)($deck['assetName'] ?? '')) ?: 'Hellbreak Deck #' . $deckID;
          $monster = trim((string)($deck['keyIndicator1'] ?? ''));
          $location = trim((string)($deck['keyIndicator2'] ?? ''));
          $details = array_filter([$monster ? (CardName($monster) ?: $monster) : '', $location ? (CardName($location) ?: $location) : '']);
        ?>
          <div class="hellbreak-deck"><span><strong><?php echo htmlspecialchars($deckName, ENT_QUOTES); ?></strong><small><?php echo htmlspecialchars($details ? implode(' · ', $details) : 'Choose a Monster and Location', ENT_QUOTES); ?></small></span><a class="hellbreak-button secondary" href="/TCGEngine/NextTurn.php?gameName=<?php echo rawurlencode($deckID); ?>&amp;playerID=1&amp;folderPath=HellbreakDeck">Edit Deck</a></div>
        <?php endforeach; elseif (IsUserLoggedIn()): ?><div class="hellbreak-empty">No saved Hellbreak decks yet.</div>
        <?php else: ?><div class="hellbreak-empty">Log in to see and manage your decks.</div><?php endif; ?>
      </div>
    </section>

    <section class="hellbreak-panel">
      <h3>Card Data</h3>
      <?php if ($cardCount === 0): ?>
        <p>The workbook has not been imported yet, so the editor is correctly showing an empty catalog.</p>
        <div class="hellbreak-note">Download the shared workbook as an <strong>.xlsx</strong> file, then run the importer. OneDrive’s public viewer returned an access page instead of the workbook during setup.</div>
      <?php else: ?>
        <p><?php echo $cardCount; ?> revealed cards are cataloged; <?php echo $browseImageCount; ?> with validated front images are shown in the deck editor.</p>
        <div class="hellbreak-note">Deck images are reflected from HellbreakSim; there is no duplicate deck-app image folder.</div>
      <?php endif; ?>
      <p class="hellbreak-source"><code>php DevTools/Hellbreak/import-workbook.php --source="C:\path\to\Hellbreak.xlsx"</code></p>
    </section>

    <section class="hellbreak-panel">
      <h3>HellbreakSim</h3>
      <p>The board, zones, opening setup, and shared assets are scaffolded. Card abilities and turn rules are intentionally deferred.</p>
      <div class="hellbreak-actions"><a class="hellbreak-button secondary" href="/TCGEngine/HellbreakSim/?shell=1">View Simulator Status</a></div>
    </section>
  </div>
</main>
<?php include_once __DIR__ . '/Disclaimer.php'; ?>
</body></html>
