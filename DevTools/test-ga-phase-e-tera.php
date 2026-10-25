<?php
// Retained loaded-state checks for Phase E Tera clauses that scenario play cannot reach (a direct action bypasses prereqs, so a fixture can only show the positive path):
//  - Bloom: Autumn's Fall's hand ability needs the Element Bonus (a TERA champion).
//  - Flourishing Restoration's element waiver needs the Diao Chan Bonus.
//  - Lu Bu, Indomitable Titan's replacement needs exactly 32 damage counters, the Diao Chan Bonus and a Lu Bu on the field.
chdir(dirname(__DIR__));
require 'Core/EngineActionRunner.php';
EngineLoadRootRuntime('GrandArchiveSim');
$gameName = 'ga_phase_e_tera_' . getmypid();
$gameDir = 'GrandArchiveSim/Games/' . $gameName;
RegressionEnsureDir($gameDir);
$checks = 0;
function trCheck($ok, $label) {
    global $checks;
    if (!$ok) { echo "MISS $label\n"; if (getenv('GA_CHECK_CONTINUE') !== '1') throw new RuntimeException($label); return; }
    ++$checks;
    echo "PASS $label\n";
}
function trLoad() {
    global $gameDir, $playerID;
    $playerID = 1;
    copy('DevTools/ga-fixtures/base-states/slipstream-target-cost-ordinary.txt', $gameDir . '/Gamestate.txt');
    ParseGamestate('./GrandArchiveSim/');
    $playerID = 1;
    foreach ([1, 2] as $seat) { $q = &GetDecisionQueue($seat); $q = []; }
}
try {
    $autumn = 'pebu7agtcd'; $restoration = 'alnjt7DyZL'; $luBu = 'xyan7zbtxi';
    $corsage = 'd7l6i5thdy'; $enchantress = '00xbh8oc00'; $fire = 'LMyKyVC2O9';

    // Bloom: Autumn's Fall
    trLoad();
    $champ = &GetField(1)[0]; $champ->CardID = $corsage;
    AddHand(1, $autumn);
    $slot = count(GetHand(1)) - 1;
    trCheck(CanHandActivateAbility(1, "myHand-$slot", 0) === true, "Autumn's Fall: a TERA champion can use the discard ability");
    trCheck((GAApplyActivateAbilityCountOverrides() || true) && CardHandActivatedAbilityCount($autumn) === 1, "Autumn's Fall: the hand ability is counted");
    trLoad();
    $champ = &GetField(1)[0]; $champ->CardID = $fire;
    AddHand(1, $autumn);
    $slot = count(GetHand(1)) - 1;
    trCheck(CanHandActivateAbility(1, "myHand-$slot", 0) === false, "Autumn's Fall: a FIRE champion cannot use the discard ability");

    // Flourishing Restoration: the waiver is the Diao Chan Bonus
    trLoad();
    $champ = &GetField(1)[0]; $champ->CardID = $enchantress;
    trCheck(CanPlayerUseCardElement(1, $restoration, false, false) === true, 'Flourishing Restoration: Diao Chan, Enchantress waives the TERA requirement');
    trLoad();
    $champ = &GetField(1)[0]; $champ->CardID = $fire;
    trCheck(CanPlayerUseCardElement(1, $restoration, false, false) === false, 'Flourishing Restoration: a FIRE champion without Diao Chan cannot meet the TERA requirement');
    $champ->CardID = $enchantress; $champ->Subcards = [];
    trCheck(CanPlayerUseCardElement(1, $restoration, false, false) === true, 'Flourishing Restoration: Diao Chan, Enchantress still waives it with no lineage');

    // Lu Bu, Indomitable Titan: the death replacement
    $setup = function (int $damage, bool $diaoChan, bool $withLuBu) use ($corsage, $fire, $luBu) {
        trLoad();
        $champ = &GetField(1)[0];
        $champ->CardID = $diaoChan ? $corsage : $fire;
        $champ->Damage = $damage;
        if ($withLuBu) AddField(1, CardID: $luBu, Owner: 1, Controller: 1);
        return LuBuDiaoChanChampionReplacement(1, 'myField-0');
    };
    trCheck($setup(33, true, true) === false, 'Lu Bu: 33 damage counters is not exactly thirty-two');
    trCheck($setup(32, false, true) === false, 'Lu Bu: no Diao Chan Bonus, no replacement');
    trCheck($setup(32, true, false) === false, 'Lu Bu: no Lu Bu on the field, no replacement');
    trCheck($setup(32, true, true) === true, 'Lu Bu: exactly 32 damage with the Diao Chan Bonus and Lu Bu replaces the death');
    echo "OK $checks checks\n";
} finally {
    if (is_dir($gameDir)) { foreach (glob($gameDir . '/*') as $f) @unlink($f); @rmdir($gameDir); }
}
