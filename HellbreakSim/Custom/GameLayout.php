<?php
if(!function_exists('_VersionedClientInclude')) {
    function _VersionedClientInclude($path) {
        if(preg_match('#^https?://#i', $path)) return $path;
        $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        $modified = $documentRoot !== '' ? @filemtime($documentRoot . $path) : false;
        return $modified === false ? $path : $path . (strpos($path, '?') === false ? '?' : '&') . 'v=' . $modified;
    }
}
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars(_VersionedClientInclude('/TCGEngine/HellbreakSim/Custom/GameLayout.css'), ENT_QUOTES); ?>">
<?php if(function_exists('HellbreakTutorialIsActive') && HellbreakTutorialIsActive()): ?>
<link rel="stylesheet" href="<?php echo htmlspecialchars(_VersionedClientInclude('/TCGEngine/HellbreakSim/Tutorial/tutorial.css'), ENT_QUOTES); ?>">
<script defer src="<?php echo htmlspecialchars(_VersionedClientInclude('/TCGEngine/HellbreakSim/Tutorial/tutorial-client.js'), ENT_QUOTES); ?>"></script>
<?php endif; ?>

<div class="hb-atmosphere" aria-hidden="true"></div>
<main id="hellbreakTable" class="hb-table log-collapsed" aria-label="Hellbreak game table">
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
                <div id="theirHealthStackSlot" class="hb-zone hb-health-stack" data-label="Health stack"></div>
                <span class="hb-top-health">Top <span id="theirTopHealthSlot"></span></span>
                <div id="theirMonsterSlot" class="hb-zone hb-monster" data-label="Monster"></div>
            </div>
            <div class="hb-field-zone">
                <div id="theirCharactersSlot" class="hb-zone hb-characters" data-label="Characters"></div>
                <div id="theirAssetsSlot" class="hb-zone hb-assets" data-label="Assets"></div>
            </div>
            <div class="hb-pile-zone">
                <div id="theirVaultSlot" class="hb-zone hb-pile" data-label="Vault"></div>
                <div id="theirCryptSlot" class="hb-zone hb-pile" data-label="Crypt"></div>
                <div id="theirDeckSlot" class="hb-zone hb-pile" data-label="Deck"></div>
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
                <div id="myHealthStackSlot" class="hb-zone hb-health-stack" data-label="Health stack"></div>
                <span class="hb-top-health">Top <span id="myTopHealthSlot"></span></span>
                <div id="myMonsterSlot" class="hb-zone hb-monster" data-label="Monster"></div>
            </div>
            <div class="hb-field-zone">
                <div id="myCharactersSlot" class="hb-zone hb-characters" data-label="Characters"></div>
                <div id="myAssetsSlot" class="hb-zone hb-assets" data-label="Assets"></div>
            </div>
            <div class="hb-pile-zone">
                <div id="myVaultSlot" class="hb-zone hb-pile" data-label="Vault"></div>
                <div id="myCryptSlot" class="hb-zone hb-pile" data-label="Crypt"></div>
                <div id="myDeckSlot" class="hb-zone hb-pile" data-label="Deck"></div>
            </div>
            <div id="myHandSlot" class="hb-zone hb-hand" data-label="Your hand"></div>
        </section>
    </div>
</main>

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
