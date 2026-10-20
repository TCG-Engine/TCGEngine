<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_house_rules_match.php
// Twin Suns house rules: the set StartRoom resolves onto $lobby->houseRulesActive must reach BOTH the game (as HR_*
// variables SWUHouseRule() reads) AND the match record — a later rematch builds a synthetic lobby with no options, so
// the match is the only place it can read them back from. docs/superpowers/specs/2026-10-18-twinsuns-house-rules-design.md
header('Content-Type: text/plain');
include __DIR__ . '/../../SWUSim/MatchFlow.php';

class _HRMPlayer {
    private $seat; private $key; private $link;
    public function __construct($seat, $link) { $this->seat = $seat; $this->link = $link; $this->key = 'hr' . $seat . uniqid(); }
    public function getGamePlayerID() { return $this->seat; }
    public function setGamePlayerID($s) { $this->seat = $s; }
    public function getAuthKey() { return $this->key; }
    public function getDeckLink() { return $this->link; }
    public function getPreconstructedDeck() { return ''; }
}

// A Twin Suns-legal list (2 leaders + base + 80 singletons) from the tracked fixture.
$j = json_decode(file_get_contents(__DIR__ . '/../../SWUSim/DevTools/tests/fixtures/twinsuns_deck.json'), true);
$lines = ['Leader', '1 ' . $j['leader']['id'], '1 ' . $j['secondleader']['id'], 'Base', '1 ' . $j['base']['id'], 'Deck'];
foreach ($j['deck'] as $c) $lines[] = $c['count'] . ' ' . $c['id'];
$deck = implode("\n", $lines);

$lobby = new stdClass();
$lobby->isPrivate = true;
$lobby->format = 'twinsuns';
$lobby->queueType = 'bo1';
$lobby->players = [new _HRMPlayer(1, $deck), new _HRMPlayer(2, $deck), new _HRMPlayer(3, $deck)];
$lobby->houseRulesActive = ['extraBaseHp' => 10, 'planScry' => 2];

$matchId = SWUCreateMatchFromLobby($lobby);
$checks = [];
$checks['match created'] = is_string($matchId) && $matchId !== '';
$m = $matchId ? SWUReadMatch($matchId) : null;
$checks['match stores the house rules'] = is_array($m) && ($m['houseRules'] ?? null) === ['extraBaseHp' => 10, 'planScry' => 2];
$checks['game reads Extra Base HP 10'] = function_exists('SWUHouseRule') && SWUHouseRule('extraBaseHp') === 10;
$checks['game reads Plan Scry 2'] = function_exists('SWUHouseRule') && SWUHouseRule('planScry') === 2;
$checks['an unchosen rule reads null'] = function_exists('SWUHouseRule') && SWUHouseRule('takeCredit') === null;

// A lobby with NO house rules leaves the match record without the key (absent, not empty).
$plain = new stdClass();
$plain->isPrivate = true; $plain->format = 'twinsuns'; $plain->queueType = 'bo1';
$plain->players = [new _HRMPlayer(1, $deck), new _HRMPlayer(2, $deck), new _HRMPlayer(3, $deck)];
$plainId = SWUCreateMatchFromLobby($plain);
$pm = $plainId ? SWUReadMatch($plainId) : null;
$checks['no rules: the match has no houseRules key'] = is_array($pm) && !array_key_exists('houseRules', $pm);
$checks['no rules: the game reads null'] = function_exists('SWUHouseRule') && SWUHouseRule('extraBaseHp') === null;

echo (count(array_filter($checks)) === count($checks))
   ? "PASS (" . count($checks) . " checks) match=$matchId\n"
   : "FAIL: " . implode(', ', array_keys(array_filter($checks, fn($v) => !$v))) . "\n  flash=" . GetFlashMessage() . "\n";
