<?php
// Retained loaded-state checks for Phase E Umbra clauses that scenario play cannot reach (a direct action bypasses prereqs, so a fixture can only show the positive path):
//  - Gloamspire Mantle's ambush is only for Umbra element PHANTASIA allies (a non-phantasia Umbra ally or a non-Umbra phantasia ally does not get it, nor does anyone without the Mantle).
//  - Mary Ann borrows each listed keyword only from her controller's omens, and only Mary Ann borrows it.
//  - Spirelle's ability can only be activated when a card banished by Spirelle exists.
chdir(dirname(__DIR__));
require 'Core/EngineActionRunner.php';
EngineLoadRootRuntime('GrandArchiveSim');
$gameName = 'ga_phase_e_umbra_' . getmypid();
$gameDir = 'GrandArchiveSim/Games/' . $gameName;
RegressionEnsureDir($gameDir);
$checks = 0;
function umCheck($ok, $label) {
    global $checks;
    if (!$ok) { echo "MISS $label\n"; if (getenv('GA_CHECK_CONTINUE') !== '1') throw new RuntimeException($label); return; }
    ++$checks;
    echo "PASS $label\n";
}
function umLoad() {
    global $gameDir, $playerID;
    $playerID = 1;
    copy('Tests/Integration/GrandArchiveSim/slipstream-target-cost-ordinary/initial_gamestate.txt', $gameDir . '/Gamestate.txt');
    ParseGamestate('./GrandArchiveSim/');
    $playerID = 1;
    foreach ([1, 2] as $seat) { $q = &GetDecisionQueue($seat); $q = []; }
}
try {
    $mantle = 'fooz13xfpk'; $shadow = 'gveirpdm44'; $wraith = 'xrpx8jypwc'; $maiden = 'xkzLY4vWMk';
    $maryAnn = 'mt5zs1w6c0'; $gildas = 'g99PIuhU0O'; $executioner = 'itwys9kf4r'; $shopkeep = 'px60u5n1do'; $guide = 'em6eEh9q8y';
    $spirelle = 'p2n1953som';

    $ambush = function (string $cardID, bool $withMantle) use ($mantle) {
        umLoad();
        if ($withMantle) AddField(1, CardID: $mantle, Owner: 1, Controller: 1);
        $obj = AddField(1, CardID: $cardID, Owner: 1, Controller: 1);
        return HasAmbush($obj, GetField(1));
    };
    umCheck($ambush($shadow, true) === true, 'Mantle: an Umbra phantasia ally has ambush');
    umCheck($ambush($shadow, false) === false, 'Mantle: without the Mantle the same ally has no ambush');
    umCheck($ambush($wraith, true) === false, 'Mantle: an Umbra ally that is not a phantasia has no ambush');
    umCheck($ambush($maiden, true) === false, 'Mantle: a phantasia ally of another element has no ambush');

    $share = function (string $holder, ?string $omen, string $keyword) {
        umLoad();
        $obj = AddField(1, CardID: $holder, Owner: 1, Controller: 1);
        if ($omen !== null) { $o = AddBanish(1, $omen, 'TEST'); $o->Counters = ['omen' => 1]; }
        return MaryAnnOmensHaveKeyword($obj, $keyword);
    };
    umCheck($share($maryAnn, $gildas, 'Vigor') === true, 'Mary Ann: a vigor omen gives her vigor');
    umCheck($share($maryAnn, $gildas, 'Stealth') === true, 'Mary Ann: a stealth omen gives her stealth');
    umCheck($share($maryAnn, $executioner, 'Ambush') === true, 'Mary Ann: an ambush omen gives her ambush');
    umCheck($share($maryAnn, $shopkeep, 'Intercept') === true, 'Mary Ann: an intercept omen gives her intercept');
    umCheck($share($maryAnn, $gildas, 'Ambush') === false, 'Mary Ann: an omen without ambush does not give ambush');
    umCheck($share($maryAnn, $guide, 'Vigor') === false, 'Mary Ann: an omen without vigor does not give vigor');
    umCheck($share($maryAnn, null, 'Vigor') === false, 'Mary Ann: with no omens she has no borrowed keyword');
    umCheck($share($guide, $gildas, 'Vigor') === false, 'Mary Ann: only Mary Ann borrows the keywords');

    $spirelleCase = function (bool $banished, bool $tagged) use ($spirelle, $guide) {
        umLoad();
        AddField(1, CardID: $spirelle, Owner: 1, Controller: 1);
        $hand = &GetHand(1); $hand = [];
        AddHand(1, $guide); AddHand(1, $guide);
        if ($banished) { $b = AddBanish(1, $guide, 'TEST'); $b->Counters = $tagged ? ['spirelle_banished' => 1] : ['omen' => 1]; }
        $field = GetField(1);
        $idx = 0; foreach ($field as $i => $o) if ($o->CardID === $spirelle) $idx = $i;
        return CanActivateAbility(1, 'myField-' . $idx, 0);
    };
    umCheck($spirelleCase(true, true) === true, 'Spirelle: a card banished by Spirelle lets the ability be activated');
    umCheck($spirelleCase(true, false) === false, 'Spirelle: a banished omen that Spirelle did not banish does not');
    umCheck($spirelleCase(false, false) === false, 'Spirelle: with nothing banished it cannot');
    echo "OK $checks checks\n";
} finally {
    if (is_dir($gameDir)) { foreach (glob($gameDir . '/*') as $f) @unlink($f); @rmdir($gameDir); }
}
