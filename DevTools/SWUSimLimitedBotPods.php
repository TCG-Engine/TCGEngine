<?php
// The Limited bots' ACCEPTANCE run (Plan 5, spec docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md
// §3.7). The bot drafter is a heuristic with no unit tests (owner rule 2026-10-12): this run is its proof.
//
// Each pod: 8 seats drafting <packs> packs of <set>. Seats 1–4 are EXPERT bots (LTDraftBotPickCard); seats 5–8 draft by
// the §3.2 timeout rule (LTDraftAutoPickCard). Every seat then builds with the same bot builder (SWULimitedBotBuildDeck),
// so only the drafting differs. It reports, per arm, how many decks meet the shape rule (8–10 two-cost units AND 20+
// units AND legal), writes every deck as a self-play fixture, and plays expert seat i against auto-pick seat 4+i with
// DevTools/SWUSimBotSelfPlayTest.php — <games> games per pair, alternating the first player — then prints the expert
// win rate with a 95% Wilson interval.
//
// Acceptance (spec §3.7 [my call]): expert shape compliance ≥ 90%; expert wins ≥ 55% of ≥ 200 games with the interval's
// lower bound above 50%.
//
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 \
//     php -d apc.enable_cli=1 -d xdebug.mode=off DevTools/SWUSimLimitedBotPods.php --pods=12 --games=5 [--seed=1] [--set=HMW]
//     [--packs=3] [--lineup=LAW,ASH,HMW (Host's Choice, overrides --set/--packs)] [--out=.claude/tmp/limited-bot-pods/<stamp>] [--no-games]
chdir(__DIR__ . '/..');
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
require_once './SWUSim/Limited/LimitedRoom.php';      // the pure draft / room rules
require_once './SWUSim/Limited/LimitedBot.php';       // the builder (card data)

$opt = [];
foreach (array_slice($argv, 1) as $a) if (preg_match('/^--([a-z-]+)(?:=(.*))?$/', $a, $m)) $opt[$m[1]] = $m[2] ?? '1';
$pods  = max(1, intval($opt['pods'] ?? 12));
$games = max(1, intval($opt['games'] ?? 5));
$seed  = intval($opt['seed'] ?? 1);
$set   = strtoupper(strval($opt['set'] ?? 'HMW'));
$packs = max(3, min(6, intval($opt['packs'] ?? 3)));
$out   = strval($opt['out'] ?? ('.claude/tmp/limited-bot-pods/' . gmdate('Ymd-His')));
@mkdir($out, 0777, true);

// The shape rule (owner 2026-11-05): 8–11 turn-1 plays (units costing 1 or 2 with no aspect penalty against the deck's
// leader + base), 9–10 the target, and 20+ units.
function podShape(array $deck): array {
    $have = array_merge(_LTBotAspects($deck['leader']), _LTBotAspects($deck['base']));
    $splashBase = SWULimitedIsSplashBase($deck['base']);
    $units = 0; $early = 0;
    foreach ($deck['main'] as $c) {
        if (CardType($c) !== 'Unit') continue;
        $units++;
        $miss = _LTBotMissingPips(_LTBotAspects($c), $have);
        // a splash base's Epic Action makes a one-V/C/A/Cunning-pip-off cheap unit a turn-1 play too (owner 2026-11-05)
        if (intval(CardCost($c)) <= 2 && ($miss === 0 || ($splashBase && $miss === 1 && _LTBotMissingIsSplashable(_LTBotAspects($c), $have)))) $early++;
    }
    return ['units' => $units, 'twos' => $early, 'ok' => $units >= LT_BOT_UNIT_MIN && $early >= LT_BOT_EARLY_MIN && $early <= LT_BOT_EARLY_MAX,
            'target' => $early === 9 || $early === 10];
}
function podFixture(array $deck, string $label): string {
    $lines = ["# Limited bot-pod fixture — $label (DevTools/SWUSimLimitedBotPods.php)", "# Style: {$deck['style']}", '', 'Leader', '1 ' . $deck['leader'], '', 'Base', '1 ' . $deck['base'], '', 'Deck'];
    foreach (array_count_values($deck['main']) as $c => $n) $lines[] = "$n $c";
    return implode("\n", $lines) . "\n";
}

$now = time();
$shape = ['expert' => [0, 0], 'autopick' => [0, 0]];   // [ok, built]
$target = 0;
$pairs = [];
$samples = [];
for ($p = 1; $p <= $pods; $p++) {
    $settings = isset($opt['lineup'])
        ? SWULimitedSettingsFromRequest(['packMode' => 'hostchoice', 'packCount' => count(explode(',', $opt['lineup'])), 'lineup' => explode(',', strtoupper($opt['lineup']))], 'draft')
        : SWULimitedSettingsFromRequest(['packMode' => 'single', 'singleSet' => $set, 'packCount' => $packs], 'draft');
    $lt = LTSetSettings(LTNew('draft'), $settings, SWULimitedValidateSettings($settings));
    for ($s = 1; $s <= 8; $s++) $lt = $s <= 4 ? LTAddBotSeat($lt, $s, "expert$s") : LTAddSeat($lt, $s, "autopick$s");
    $o = SWULimitedOpenDraft($settings, range(1, 8), SWULimitedRng($seed * 1000 + $p));
    $lt = LTDraftBegin($lt, $o['order'], $o['leaderPacks'], $o['cardPacks'], $o['aspects'], $seed * 1000 + $p, 300, $now, $o['facts']);
    $lt['setsInPlay'] = SWULimitedSetsInPlay($settings);
    $lt['draft']['splash'] = SWULimitedSplashInPlay($lt['setsInPlay']);
    $fresh = array_fill_keys(range(1, 8), $now);
    for ($guard = 0; $guard < 5000 && $lt['phase'] === 'drafting'; $guard++) {
        $lt = LTDraftTick($lt, $fresh, $now);   // the expert bots pick at once
        foreach ([5, 6, 7, 8] as $s) {
            $f = LTDraftFront($lt['draft'], $s);
            if ($f !== null && $lt['phase'] === 'drafting') $lt = LTDraftPick($lt, $s, $f['id'], LTDraftAutoPickCard($lt, $s), $now) ?? $lt;
        }
    }
    if ($lt['phase'] !== 'building') { echo "pod $p: the draft did not finish\n"; continue; }
    $decks = [];
    foreach (range(1, 8) as $s) {
        $arm = $s <= 4 ? 'expert' : 'autopick';
        $d = SWULimitedBotBuildDeck($lt['pools'][$s], $lt['setsInPlay']);
        $shape[$arm][1]++;
        if ($d === null) { echo "pod $p seat $s ($arm): no legal deck\n"; continue; }
        $sh = podShape($d);
        if ($sh['ok']) $shape[$arm][0]++;
        if ($arm === 'expert' && $sh['target']) $target++;
        $decks[$s] = $d;
        $path = "$out/pod{$p}_seat{$s}.txt";
        file_put_contents($path, podFixture($d, "pod $p seat $s ($arm)"));
        if ($arm === 'expert' && count($samples) < 3) $samples[] = ['pod' => $p, 'leader' => CardTitle($d['leader']), 'base' => CardTitle($d['base']), 'style' => $d['style']] + $sh;
    }
    for ($i = 1; $i <= 4; $i++) if (isset($decks[$i], $decks[4 + $i])) $pairs[] = [$p, $i, 4 + $i, $decks[$i]['style'], $decks[4 + $i]['style']];
    printf("pod %d: expert shape %d/4, auto-pick shape %d/4\n", $p,
        count(array_filter([1, 2, 3, 4], fn($s) => isset($decks[$s]) && podShape($decks[$s])['ok'])),
        count(array_filter([5, 6, 7, 8], fn($s) => isset($decks[$s]) && podShape($decks[$s])['ok'])));
}
foreach ($shape as $arm => [$ok, $n]) printf("SHAPE %s: %d/%d = %.1f%%\n", $arm, $ok, $n, $n ? 100 * $ok / $n : 0);
printf("TARGET (9–10 turn-1 plays) expert: %d/%d\n", $target, $shape['expert'][1]);
echo "SAMPLES " . json_encode($samples) . "\n";
if (isset($opt['no-games'])) exit(0);

// The games: expert seat i (deck 1) vs auto-pick seat 4+i (deck 2), each pair G games, the first player alternating.
$won = 0; $played = 0; $failed = 0;
foreach ($pairs as [$p, $a, $b, $styleA, $styleB]) {
    for ($g = 0; $g < $games; $g++) {
        $fp = $g % 2 === 0 ? 1 : 2;
        $cmd = sprintf('php -d apc.enable_cli=1 -d xdebug.mode=off DevTools/SWUSimBotSelfPlayTest.php --deck=%s --deck2=%s --chooser=heuristic-%s --chooser2=heuristic-%s --seed=ltpod%d_%d_%d --first-player=%d 2>&1',
            escapeshellarg("$out/pod{$p}_seat{$a}.txt"), escapeshellarg("$out/pod{$p}_seat{$b}.txt"), $styleA, $styleB, $p, $a, $g, $fp);
        $res = (string)shell_exec($cmd);
        if (!preg_match('/SWUBOT_METRICS (\{.*\})/', $res, $mm)) { $failed++; continue; }
        $w = intval(json_decode($mm[1], true)['winner'] ?? 0);
        if ($w !== 1 && $w !== 2) { $failed++; continue; }
        $played++;
        if ($w === 1) $won++;
    }
    printf("pair pod %d seat %d vs %d: expert %d / %d so far\n", $p, $a, $b, $won, $played);
}
$z = 1.96; $n = max(1, $played); $ph = $won / $n;
$den = 1 + $z * $z / $n; $c = ($ph + $z * $z / (2 * $n)) / $den; $h = $z * sqrt($ph * (1 - $ph) / $n + $z * $z / (4 * $n * $n)) / $den;
printf("GAMES expert %d / %d = %.1f%% (95%% Wilson %.1f–%.1f%%), %d failed\n", $won, $played, 100 * $ph, 100 * ($c - $h), 100 * ($c + $h), $failed);
