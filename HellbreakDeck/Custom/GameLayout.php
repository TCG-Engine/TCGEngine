<?php
// northbeach.gg deck-builder layout. The schema binds generated zones into these
// stable slots; card/game data remains owned by the normal generated renderer.
if (!function_exists('HellbreakDeckVersionedAsset')) {
  function HellbreakDeckVersionedAsset($path) {
    $absolute = ($_SERVER['DOCUMENT_ROOT'] ?? '') . $path;
    $version = @filemtime($absolute);
    return $path . ($version ? '?v=' . $version : '');
  }
}
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars(HellbreakDeckVersionedAsset('/TCGEngine/HellbreakDeck/Custom/DeckBuilder.css'), ENT_QUOTES); ?>">
<div id="hellbreakDeckBoard">
  <aside class="hb-deck-library" aria-label="Card library">
    <section id="hbDeckIdentity" aria-label="Selected monster and location">
      <div class="hb-identity-slot hb-monster-slot">
        <span class="hb-identity-label">Monster</span>
        <div id="myMonsterSlot"></div>
      </div>
      <div class="hb-identity-slot hb-location-slot">
        <span class="hb-identity-label">Locations</span>
        <div id="myLocationSlot"></div>
      </div>
      <div class="hb-identity-center" aria-hidden="true"><i></i><span>NB&ndash;09</span></div>
    </section>
    <section class="hb-card-browser">
      <header><span>Card archive</span><small>Choose what comes ashore</small></header>
      <nav id="hbPaneTabs" aria-label="Card archive categories">
        <button type="button" data-pane-index="0">Monsters</button>
        <button type="button" data-pane-index="1">Locations</button>
        <button type="button" data-pane-index="2">Cards</button>
      </nav>
      <div id="myCardPaneSlot"></div>
    </section>
  </aside>

  <main id="hellbreakDeckWorkspace">
    <section class="hb-deck-section hb-main-deck" aria-label="Main deck">
      <header class="hb-deck-section-header">
        <div><span>Main deck</span><small>Your active manifest</small></div>
        <span id="hbMainDeckCount" class="hb-deck-count">0</span>
        <div id="hbDeckToolbar" aria-label="Deck controls">
          <label for="mySortSlot">Sort</label>
          <div id="mySortSlot"></div>
        </div>
      </header>
      <div id="myMainDeckSlot"></div>
    </section>
    <section class="hb-deck-section hb-sideboard" aria-label="Sideboard">
      <header class="hb-deck-section-header">
        <div><span>Sideboard</span><small>Held beyond the tide line</small></div>
        <span id="hbSideboardCount" class="hb-deck-count">0</span>
      </header>
      <div id="mySideboardSlot"></div>
    </section>
  </main>
</div>
<script src="<?php echo htmlspecialchars(HellbreakDeckVersionedAsset('/TCGEngine/HellbreakDeck/Custom/DeckBuilder.js'), ENT_QUOTES); ?>"></script>
