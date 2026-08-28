<?php
// The waiting-room routing predicate, pinned as a TRUTH TABLE.
//
// A lobby gets a WaitingRoom iff it is PRIVATE and its format is not a localMode format.
// Public queue: never. Solo/local: never. Every other private lobby: always, at 2 seats or 4.
//
// The table is the point. A one-off assertion would let goldfish or hotseat silently drift back in
// the next time someone touches the predicate — and the old predicate it replaces
// (rootName === 'SWUSim' && SWUFormatIsRoomFormat) got this wrong in BOTH directions: it denied a
// lobby to every private 2-player game, and to twinsuns-preview (declared seats 2-2).
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

$root = __DIR__ . '/../../..';
require_once $root . '/AppCore/SWU/Formats.php';
require_once $root . '/APIs/Lobbies/Classes/LobbyAdapter.php';
require_once $root . '/SWUSim/LobbyAdapter.php';

$a = new SWULobbyAdapter();

function lobby(string $format, bool $private): object {
    $o = new stdClass();
    $o->format     = $format;
    $o->isPrivate  = $private;
    $o->rootName   = 'SWUSim';
    $o->maxPlayers = SWUGetFormat($format)['maxPlayers'] ?? 2;
    $o->queueType  = 'bo1';
    return $o;
}

// ── The truth table. Every registered format, private and public. ────────────────────────────────
$expectPrivate = [
    'premier' => true, 'eternal' => true, 'open' => true, 'padawan' => true,
    'padawan-preview' => true, 'preview' => true, 'twinsuns-preview' => true,
    'eternal-preview' => true, 'twinsuns' => true, 'teamsuns' => true,
    'goldfish' => false,   // solo
    'hotseat'  => false,   // one human driving both seats
];
foreach ($expectPrivate as $fmt => $want) {
    $got = $a->wantsWaitingRoom(lobby($fmt, true));
    check($got === $want, sprintf('PRIVATE %-16s -> %s', $fmt, $want ? 'waiting room' : 'no waiting room'));
}
foreach (array_keys($expectPrivate) as $fmt) {
    check($a->wantsWaitingRoom(lobby($fmt, false)) === false, sprintf('PUBLIC  %-16s -> no waiting room', $fmt));
}

// Every format in the registry is covered above — otherwise a newly added format silently gets no
// assertion at all and inherits whatever the predicate happens to do.
$missing = array_diff(array_keys(SWUListFormats()), array_keys($expectPrivate));
check(empty($missing), 'every registered format is in the truth table (missing: ' . implode(',', $missing) . ')');

// isPrivate is the AUTHORITY on privacy. An inviteCode alone must not imply it.
$noFlag = lobby('premier', false);
$noFlag->inviteCode = 'abc123';
check($a->wantsWaitingRoom($noFlag) === false, 'an inviteCode alone does NOT make a lobby private');

// An unknown format is not a free pass into the lobby flow.
$bogus = lobby('premier', true);
$bogus->format = 'no-such-format';
check($a->wantsWaitingRoom($bogus) === false, 'an unknown format gets no waiting room');

// ── seatModel: the RENDERING question, kept separate from routing ────────────────────────────────
$m2 = $a->seatModel(lobby('premier', true));
check($m2['maxPlayers'] === 2,    'premier seats 2');
check($m2['teams'] === null,      'premier has no teams');
check($m2['queueType'] === 'bo1', 'seatModel carries queueType through (Spec 2 needs it)');

$m4 = $a->seatModel(lobby('twinsuns', true));
check($m4['maxPlayers'] === 4, 'twinsuns seats up to 4');
check($m4['teams'] === null,   'twinsuns has no teams');

$mt = $a->seatModel(lobby('teamsuns', true));
check($mt['maxPlayers'] === 4,         'teamsuns seats 4');
check($mt['teams'] === ['red','blue'], 'teamsuns has red/blue teams');

// ── Resolution from SiteDef: presence of the block IS the opt-in ─────────────────────────────────
check(LobbyAdapterFor('SWUSim') instanceof SWULobbyAdapter, 'SWUSim resolves to its adapter');
check(LobbyAdapterFor('SWUDeck') === null,       'a sim with no waitingRoom block resolves to null');
check(LobbyAdapterFor('NoSuchSim') === null,     'an unknown sim resolves to null, not a fatal');
check(LobbyAdapterFor('SWUSim') === LobbyAdapterFor('SWUSim'), 'the adapter is cached, not rebuilt per call');

// ── validateDeck ─────────────────────────────────────────────────────────────────────────────────
// The identity block is a GENERIC card list, not an SWU-shaped blob: the shared page renders
// [{id,name,url,kind}] without knowing what a leader is. `kind` only picks a ring colour.
$deck = "Leader\nJTL_001\nBase\nJTL_023\nDeck\n"
      . "3 JTL_100\n3 LOF_100\n3 SEC_100\n3 LAW_100\n3 ASH_100\n3 IBH_010\n"
      . "3 JTL_101\n3 LOF_101\n3 SEC_101\n3 LAW_101\n3 ASH_101\n3 IBH_011\n"
      . "3 JTL_102\n3 LOF_102\n3 SEC_102\n3 LAW_102\n1 JTL_103\n1 LOF_103\n";

$res = $a->validateDeck(lobby('premier', true), $deck);
check($res['ok'] === true, 'a legal premier deck validates: ' . $res['message']);

$cards = $res['identity']['cards'];
check(count($cards) === 2,            'premier identity = 1 leader + 1 base, got ' . count($cards));
check($cards[0]['id'] === 'JTL_001',  'leader comes first');
check($cards[0]['kind'] === 'leader', 'leader carries kind=leader');
check($cards[1]['id'] === 'JTL_023',  'base comes last');
check($cards[1]['kind'] === 'base',   'base carries kind=base');

// The name is the display title, not the raw id — the page has no dictionary to look it up with.
check($cards[0]['name'] === 'Asajj Ventress, I Work Alone', 'leader name is "Title, Subtitle"');
check($cards[1]['name'] === 'Theed Palace',                 'a base with no subtitle is just its title');

// Art URLs resolve SERVER-side through SWUCardImagePath() — the one seam that knows about the mock_
// prefix preview art needs. A hand-built path is how the decision popups 404'd locally.
foreach ($cards as $c) {
    check(strpos($c['url'], '/TCGEngine/AppCore/SWU/Images/WebpImages/') === 0,
          'art url comes from the image seam: ' . $c['url']);
}

// A deck that does not resolve must CLEAR the identity, not keep advertising the previous one.
$bad = $a->validateDeck(lobby('premier', true), 'not a deck');
check($bad['ok'] === false,             'garbage input fails');
check($bad['message'] !== '',           'failure carries a message for the player');
check($bad['identity']['cards'] === [], 'a failed deck clears the identity cards');

$empty = $a->validateDeck(lobby('premier', true), '   ');
check($empty['ok'] === false,             'an empty deck link fails');
check($empty['identity']['cards'] === [], 'an empty deck link clears the identity cards');

// The deck is checked against the LOBBY'S format, never a hardcoded one: this premier-legal deck has
// one leader, so it is NOT legal for Twin Suns (which requires two).
$tw = $a->validateDeck(lobby('twinsuns', true), $deck);
check($tw['ok'] === false, 'a 1-leader deck is rejected for twinsuns (format comes from the lobby)');

// ── Ring colours are ordered CANONICALLY, not as printed ─────────────────────────────────────────
// Vigilance < Command < Aggression < Cunning < Villainy < Heroism.
// ⚠ A DELIBERATE DIVERGENCE FROM THE ART, not a data fix. CardAspect() mirrors each card's printed
// icon order, and that order genuinely varies per card — LAW_002 Beckett really does print Cunning
// above Vigilance. We sort anyway so the same aspect pair always draws the same ring and two seats
// are visually comparable, at the cost of exact fidelity on the 9 cards that print differently.
// If that trade is ever reversed, drop the usort in _aspectColors() AND invert these assertions.
$ac = new ReflectionMethod($a, '_aspectColors');
$ac->setAccessible(true);
$ring = fn($cid) => $ac->invoke($a, $cid);

$BLUE='#3b7dd8'; $GREEN='#2e9e4f'; $RED='#c0392b'; $YELLOW='#e2b13c'; $BLACK='#141414'; $WHITE='#e8e4d8';

// Printed "Aggression,Heroism" (canonical) and "Heroism,Aggression" (not) must draw IDENTICALLY.
check($ring('LOF_012') === [$RED, $WHITE], 'LOF_012 Rey  [Aggression,Heroism] -> red, white');
check($ring('SHD_012') === [$RED, $WHITE], 'SHD_012 Bo-Katan [Heroism,Aggression] -> red, white (reordered)');
check($ring('LOF_012') === $ring('SHD_012'), 'the same aspect pair draws the same ring in either set');

check($ring('SHD_001') === [$BLUE, $BLACK],  'SHD_001 Gar Saxon [Villainy,Vigilance] -> blue, black');
check($ring('SHD_003') === [$BLUE, $WHITE],  'SHD_003 Finn [Heroism,Vigilance] -> blue, white');
check($ring('SHD_010') === [$RED, $BLACK],   'SHD_010 Bossk [Villainy,Aggression] -> red, black');
// ── Verified per-card OVERRIDES beat the canonical sort ──────────────────────────────────────────
// LAW_002 Beckett genuinely prints Cunning above Vigilance, confirmed against the card art, so his
// ring is yellow-then-blue even though canonical order would put Vigilance first.
check($ring('LAW_002') === [$YELLOW, $BLUE], 'LAW_002 Beckett is overridden -> yellow, blue (matches his art)');

// Cards confirmed to print canonically need NO entry, and must not be disturbed by the override path.
check($ring('LAW_001') === [$GREEN, $RED],   'LAW_001 Saw Gerrera [Command,Aggression] -> green, red');
check($ring('ASH_002') === [$RED, $YELLOW],  'ASH_002 Fennec Shand [Aggression,Cunning] -> red, yellow');
check($ring('SHD_016') === [$YELLOW, $WHITE],'SHD_016 Fennec Shand [Cunning,Heroism] -> yellow, white');

// An override only applies when it names EXACTLY the aspects the card has, so a stale entry (errata,
// re-key) degrades to canonical instead of drawing aspects the card does not have.
$ovRef = new ReflectionClass($a);
$ovs = $ovRef->getConstant('ASPECT_ORDER_OVERRIDES');
foreach ($ovs as $ovId => $ovAspects) {
    $printed = array_map(fn($x) => strtolower(trim($x)), explode(',', (string)CardAspect($ovId)));
    $printed = array_values(array_unique($printed));
    sort($printed); $want = $ovAspects; sort($want);
    check($printed === $want, "override for {$ovId} names exactly that card's aspects");
    check(count($ovRef->getMethod('_aspectColors')->invoke($a, $ovId)) === count($ovAspects),
          "override for {$ovId} yields one colour per aspect");
}

// Already-canonical cards are untouched.
check($ring('SOR_010') === [$RED, $BLACK],   'SOR_010 Vader [Aggression,Villainy] unchanged');
check($ring('ASH_001') === [$BLUE, $GREEN],  'ASH_001 Armorer [Vigilance,Command] unchanged');
check($ring('TWI_017') === [$YELLOW, $BLACK, $WHITE], 'TWI_017 Palpatine 3 aspects stay canonical');

// Sorting must not disturb the dedupe (DJ) or the aspect-less fallback.
check($ring('SEC_018') === [$YELLOW],   'SEC_018 DJ [Cunning,Cunning] still one smooth colour');
check($ring('JTL_026') === [$RED],      'JTL_026 Massassi Temple [Aggression] -> red');

// EVERY leader and base sorts canonically — a whole-pool sweep, so a set that prints a new odd order
// cannot slip through.
$canonOrder = [$BLUE, $GREEN, $RED, $YELLOW, $BLACK, $WHITE];
$bad = [];
foreach (GetAllCardIds() as $cid) {
    $t = CardType($cid);
    if ($t !== 'Leader' && $t !== 'Base') continue;
    $got = $ring($cid);
    $idx = array_map(fn($c) => array_search($c, $canonOrder, true), $got);
    $sorted = $idx; sort($sorted);
    if ($idx !== $sorted) $bad[] = $cid;
}
// Overridden cards are non-canonical ON PURPOSE, so the sweep asserts that the ONLY non-canonical
// rings in the game are the ones someone deliberately verified against the art.
sort($bad);
$expected = array_keys($ovs); sort($expected);
check($bad === $expected,
      'the only non-canonical rings are the verified overrides (got: ' . implode(',', $bad) .
      ' / expected: ' . implode(',', $expected) . ')');

echo "PASS\n";
