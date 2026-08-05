<?php
if(!function_exists('_VersionedClientInclude')) {
    function _VersionedClientInclude($path) {
        if(preg_match('#^https?://#i', $path)) return $path;
        $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        $modified = $documentRoot !== '' ? @filemtime($documentRoot . $path) : false;
        return $modified === false ? $path : $path . (strpos($path, '?') === false ? '?' : '&') . 'v=' . $modified;
    }
}
$hellbreakLocationNames = [1 => 'Location 1', 2 => 'Location 2'];
$hellbreakLocationCardNames = [];
global $nameData, $typeData;
if(isset($nameData) && is_array($nameData) && isset($typeData) && is_array($typeData)) {
    foreach($typeData as $hellbreakCardID => $hellbreakType) {
        if(strtolower(trim(strval($hellbreakType))) !== 'location') continue;
        $hellbreakCardName = strval($nameData[$hellbreakCardID] ?? '');
        if($hellbreakCardName !== '') $hellbreakLocationCardNames[strval($hellbreakCardID)] = $hellbreakCardName;
    }
}
if(count($hellbreakLocationCardNames) === 0) {
    $hellbreakCardCachePath = __DIR__ . '/../GeneratedCode/cardArrayCache.json';
    $hellbreakCardCache = is_file($hellbreakCardCachePath) ? json_decode(strval(file_get_contents($hellbreakCardCachePath)), true) : null;
    foreach(is_array($hellbreakCardCache['cardArray'] ?? null) ? $hellbreakCardCache['cardArray'] : [] as $hellbreakCard) {
        if(strtolower(trim(strval($hellbreakCard['type'] ?? ''))) !== 'location') continue;
        $hellbreakCardID = strval($hellbreakCard['id'] ?? '');
        $hellbreakCardName = strval($hellbreakCard['name'] ?? '');
        if($hellbreakCardID !== '' && $hellbreakCardName !== '') $hellbreakLocationCardNames[$hellbreakCardID] = $hellbreakCardName;
    }
}
if(function_exists('GetLocations')) {
    foreach(GetLocations() as $hellbreakLocation) {
        if(!is_object($hellbreakLocation) || !empty($hellbreakLocation->removed)) continue;
        $hellbreakSlot = intval($hellbreakLocation->Slot ?? 0);
        if(!isset($hellbreakLocationNames[$hellbreakSlot])) continue;
        $hellbreakCardID = strval($hellbreakLocation->CardID ?? '');
        $hellbreakName = function_exists('CardName') ? CardName($hellbreakCardID) : '';
        if(is_string($hellbreakName) && trim($hellbreakName) !== '') $hellbreakLocationNames[$hellbreakSlot] = trim($hellbreakName);
    }
}
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars(_VersionedClientInclude('/TCGEngine/HellbreakSim/Custom/GameLayout.css'), ENT_QUOTES); ?>">
<?php if(function_exists('HellbreakTutorialIsActive') && HellbreakTutorialIsActive()): ?>
<link rel="stylesheet" href="<?php echo htmlspecialchars(_VersionedClientInclude('/TCGEngine/HellbreakSim/Tutorial/tutorial.css'), ENT_QUOTES); ?>">
<script defer src="<?php echo htmlspecialchars(_VersionedClientInclude('/TCGEngine/HellbreakSim/Tutorial/tutorial-client.js'), ENT_QUOTES); ?>"></script>
<?php endif; ?>

<div class="hb-atmosphere" aria-hidden="true"></div>
<main id="hellbreakTable" class="hb-table log-collapsed" aria-label="Hellbreak game table"
    data-location-1-name="<?php echo htmlspecialchars($hellbreakLocationNames[1], ENT_QUOTES); ?>"
    data-location-2-name="<?php echo htmlspecialchars($hellbreakLocationNames[2], ENT_QUOTES); ?>"
    data-location-card-names="<?php echo htmlspecialchars(json_encode($hellbreakLocationCardNames), ENT_QUOTES); ?>">
    <header class="hb-topbar">
        <button class="hb-home" type="button" onclick="location.href='/TCGEngine/SharedUI/Sites/HellbreakSim/MainMenu.php'" aria-label="Return to Hellbreak menu">H</button>
        <div class="hb-brand"><strong>HELLBREAK</strong><span>Quick-Start Table</span></div>
        <div class="hb-round-state" aria-live="polite">
            <span id="hbRound">Round 1</span>
            <strong id="hbPhase">Setup</strong>
        </div>
        <div class="hb-priority-state">
            <span id="hbInitiative">Initiative: Player 1</span>
            <strong id="hbPriority">Priority: Player 1</strong>
        </div>
        <button id="hbZoneGuideToggle" class="hb-zone-guide-toggle" type="button" aria-expanded="false" aria-controls="hbZoneGuide">Zones</button>
        <button id="hbUndo" class="hb-undo" type="button" title="Undo your most recent action (U)">Undo <kbd>U</kbd></button>
        <button id="hbLogToggle" class="hb-log-toggle" type="button" aria-expanded="false" aria-controls="hbHistory">History</button>
    </header>

    <div class="hb-board">
        <section id="hbTheirSide" class="hb-side hb-side-their" aria-label="Opponent battlefield">
            <div class="hb-player-summary">
                <span class="hb-player-kicker">Opponent</span>
                <strong id="hbTheirLabel">Player 2</strong>
                <div class="hb-resource-row">
                    <span class="hb-resource hb-health" title="Health"><i aria-hidden="true">♥</i><span id="theirHealthValueSlot"></span></span>
                    <span class="hb-resource hb-blood" title="Blood"><i aria-hidden="true">◆</i><span id="theirBloodValueSlot"></span></span>
                    <span class="hb-resource hb-malice" title="Malice"><i aria-hidden="true">✦</i><span id="theirMaliceValueSlot"></span></span>
                </div>
            </div>
            <div class="hb-profile-zone">
                <div id="theirHealthStackSlot" class="hb-zone hb-health-stack" data-label="Health" title="Face-down Health cards"></div>
                <span class="hb-top-health">Top card · <span id="theirTopHealthSlot"></span> HP</span>
                <div id="theirMonsterSlot" class="hb-zone hb-monster" data-label="Monster" title="The opponent's main character"></div>
            </div>
            <div class="hb-field-zone">
                <div id="theirCharactersSlot" class="hb-zone hb-characters" data-label="Minions by location"></div>
                <div id="theirAssetsSlot" class="hb-zone hb-assets" data-label="Assets" title="Assets the opponent controls"></div>
            </div>
            <div class="hb-pile-zone">
                <div id="theirVaultSlot" class="hb-zone hb-pile" data-label="Vault" title="Tucked cards that generate Feeding resources"></div>
                <div id="theirCryptSlot" class="hb-zone hb-pile" data-label="Crypt" title="Discard pile"></div>
                <div id="theirDeckSlot" class="hb-zone hb-pile" data-label="Deck" title="Face-down draw pile"></div>
            </div>
            <div id="theirHandSlot" class="hb-zone hb-hand hb-opponent-hand" data-label="Opponent hand"></div>
        </section>

        <section class="hb-center" aria-label="Contested locations and game history">
            <div class="hb-center-copy"><span>Contested ground</span><strong>LOCATIONS</strong></div>
            <div id="LocationsSlot" class="hb-zone hb-locations" data-label=""></div>
            <aside id="hbHistory" class="hb-history" aria-label="Recent game history">
                <div class="hb-history-heading"><strong>Recent Events</strong><span>Public</span></div>
                <ol id="hbHistoryList"><li class="hb-history-empty">The match is just beginning.</li></ol>
            </aside>
        </section>

        <section id="hbMySide" class="hb-side hb-side-my" aria-label="Your battlefield">
            <div class="hb-player-summary">
                <span class="hb-player-kicker">You</span>
                <strong id="hbMyLabel">Player 1</strong>
                <div class="hb-resource-row">
                    <span class="hb-resource hb-health" title="Health"><i aria-hidden="true">♥</i><span id="myHealthValueSlot"></span></span>
                    <span class="hb-resource hb-blood" title="Blood"><i aria-hidden="true">◆</i><span id="myBloodValueSlot"></span></span>
                    <span class="hb-resource hb-malice" title="Malice"><i aria-hidden="true">✦</i><span id="myMaliceValueSlot"></span></span>
                </div>
            </div>
            <div class="hb-profile-zone">
                <div id="myHealthStackSlot" class="hb-zone hb-health-stack" data-label="Health" title="Your face-down Health cards"></div>
                <span class="hb-top-health">Top card · <span id="myTopHealthSlot"></span> HP</span>
                <div id="myMonsterSlot" class="hb-zone hb-monster" data-label="Monster" title="Your main character"></div>
            </div>
            <div class="hb-field-zone">
                <div id="myCharactersSlot" class="hb-zone hb-characters" data-label="Minions by location"></div>
                <div id="myAssetsSlot" class="hb-zone hb-assets" data-label="Assets" title="Assets you control"></div>
            </div>
            <div class="hb-pile-zone">
                <div id="myVaultSlot" class="hb-zone hb-pile" data-label="Vault" title="Tucked cards that generate Feeding resources"></div>
                <div id="myCryptSlot" class="hb-zone hb-pile" data-label="Crypt" title="Your discard pile"></div>
                <div id="myDeckSlot" class="hb-zone hb-pile" data-label="Deck" title="Your face-down draw pile"></div>
            </div>
            <div id="myHandSlot" class="hb-zone hb-hand" data-label="Your hand"></div>
        </section>
    </div>
</main>

<aside id="hbZoneGuide" class="hb-zone-guide" hidden aria-label="Hellbreak zone reference">
    <div class="hb-zone-guide-heading"><strong>Table zones</strong><button type="button" aria-label="Close zone reference">×</button></div>
    <dl>
        <div><dt>Monster</dt><dd>Your main character. Its lurking side supplies Feeding resources.</dd></div>
        <div><dt>Health</dt><dd>Face-down Health cards. The Top value shows damage left on the current card.</dd></div>
        <div><dt>Vault</dt><dd>Cards tucked beneath your monster. Their resource bars are collected during Feeding.</dd></div>
        <div><dt>Crypt</dt><dd>Your discard pile.</dd></div>
        <div><dt>Deck</dt><dd>Your face-down draw pile.</dd></div>
        <div><dt>Hand</dt><dd>Your private cards. Playable cards highlight when you have priority.</dd></div>
        <div><dt>Locations</dt><dd>The two contested spaces where minions attack and characters scheme.</dd></div>
        <div><dt>Minions</dt><dd>Characters in play, separated by the location where they currently fight and scheme.</dd></div>
        <div><dt>Assets</dt><dd>Assets you have played and still control.</dd></div>
    </dl>
</aside>

<div id="hbVictory" class="hb-victory" hidden role="dialog" aria-modal="true" aria-labelledby="hbVictoryTitle">
    <div class="hb-victory-card">
        <span class="hb-victory-mark" aria-hidden="true">H</span>
        <p>THE HORROR ENDS</p>
        <h2 id="hbVictoryTitle">Victory</h2>
        <span id="hbVictoryCopy">The final Health card has been revealed.</span>
        <div class="hb-victory-actions">
            <button type="button" onclick="location.href='/TCGEngine/SharedUI/Sites/HellbreakSim/MainMenu.php'">Return to Menu</button>
            <button type="button" class="secondary" onclick="location.reload()">View Final Board</button>
        </div>
    </div>
</div>
