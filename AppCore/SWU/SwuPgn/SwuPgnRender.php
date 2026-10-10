<?php
// SWU-PGN storyteller — events → the %%% STORY text (SWU-PGN/1.0 spec §16).
//
// Numbered lines are the actions a player chose; every other printed record is indented under the
// action above it; a round banner (with the board from the round's keyframe) introduces each round.
// Names come from the file's own %%% CARDS index unless the caller supplies one; an id the index
// does not cover prints as the id itself.
//
// Where §16 says "+ X if field", the part is printed when the field is present and usable: a
// non-empty string for zone/target/by/defeatedBy, any integer (0 included) for cost. A field the
// wording needs but the record lacks prints as "?".

const SWUPGN_RULE_WIDTH = 78;

function SwuPgnRender(array $doc, ?array $names = null): string
{
    return SwuPgnRenderEvents($doc['events'] ?? [], $names ?? SwuPgnCardIndex($doc));
}

function SwuPgnRenderEvents(array $events, array $names): string
{
    $out = [];
    $n = 0;
    $rule = str_repeat('═', SWUPGN_RULE_WIDTH);
    $count = 0;
    foreach ($events as $e) {
        if (++$count > SWUPGN_MAX_EVENTS) break;
        if (!is_array($e)) continue;
        $t = $e['t'] ?? null;
        if ($t === 'ROUND_START') {
            $out[] = '';
            $out[] = $rule;
            foreach (_SwuPgnRoundBanner($e, $names) as $line) $out[] = $line;
            $out[] = $rule;
            $out[] = '';
            $n = 0;
            continue;
        }
        if ($t === 'PHASE_START') {
            $out[] = ' ── ' . _SwuPgnTxt($e['phase'] ?? null) . ' ──';
            $n = 0;
            continue;
        }
        if ($t === 'UNDO') {
            $who = SwuPgnWho($e['by'] ?? null);
            $out[] = '    ·· ' . ($who === '' ? 'A player' : $who) . ' undid back to ' . _SwuPgnTxt($e['at'] ?? null) . ' ··';
            continue;
        }
        $text = _SwuPgnStoryLine($e, $names);
        if ($text === null) continue;
        if (_SwuPgnIsNumbered($e)) {
            $n++;
            $out[] = ' ' . str_pad((string)$n, 2, ' ', STR_PAD_LEFT) . '. ' . $text;
        } else {
            $out[] = '       ↳ ' . $text;
        }
    }
    return implode("\n", $out);
}

// Compares the file's %%% STORY with a fresh render. Trailing blank lines in the file are layout
// before the next banner, not story. `line` is the first differing line (1-based).
function SwuPgnStoryMatches(array $doc, ?array $names = null): array
{
    if (!is_array($doc['story'] ?? null)) return ['present' => false, 'matches' => false, 'line' => null];
    $trim = function (array $lines): array {
        while ($lines && trim(end($lines)) === '') array_pop($lines);
        return $lines;
    };
    $file = $trim($doc['story']);
    $render = $trim(explode("\n", SwuPgnRender($doc, $names)));
    $max = max(count($file), count($render));
    for ($i = 0; $i < $max; $i++) {
        if (($file[$i] ?? null) !== ($render[$i] ?? null)) return ['present' => true, 'matches' => false, 'line' => $i + 1];
    }
    return ['present' => true, 'matches' => true, 'line' => null];
}

// ── helpers ─────────────────────────────────────────────────────────────────────────────────────

function _SwuPgnTxt($v): string
{
    if (is_string($v)) return $v;
    if (is_int($v)) return (string)$v;
    if (is_float($v) && is_finite($v)) return (string)$v;
    return '?';
}

function _SwuPgnHasText($v): bool
{
    return is_string($v) && $v !== '';
}

// An ABILITY_ACTIVATE's kind, with `epic: true` read as kind "epic" (§10.1 "equivalent").
function _SwuPgnAbilityKind(array $e): ?string
{
    if (($e['epic'] ?? null) === true) return 'epic';
    return is_string($e['kind'] ?? null) ? $e['kind'] : null;
}

// §16 "The numbered ones are exactly".
function _SwuPgnIsNumbered(array $e): bool
{
    $t = $e['t'] ?? null;
    if (in_array($t, ['PLAY', 'PLAY_EVENT', 'PLAY_UPGRADE', 'PLAY_SMUGGLE', 'DEPLOY_LEADER', 'ATTACK', 'PASS', 'CLAIM_INITIATIVE'], true)) return true;
    return $t === 'ABILITY_ACTIVATE' && in_array(_SwuPgnAbilityKind($e), ['action', 'epic'], true);
}

function _SwuPgnNames(array $names, $list): string
{
    if (!is_array($list)) return '';
    $out = [];
    foreach ($list as $id) $out[] = SwuPgnName($names, $id);
    return implode(', ', $out);
}

function _SwuPgnOther($p): int
{
    return $p === 1 ? 2 : 1;   // §16: "the other player" is p === 1 ? 2 : 1
}

function _SwuPgnCostPart(array $e): string
{
    return is_int($e['cost'] ?? null) ? ' (cost ' . $e['cost'] . ')' : '';
}

// §16 "The exact wording". Null = prints nothing.
function _SwuPgnStoryLine(array $e, array $names): ?string
{
    $p = $e['p'] ?? null;
    $who = SwuPgnWho($p);
    $nm = function ($id) use ($names) { return SwuPgnName($names, $id); };
    switch ($e['t'] ?? null) {
        case 'PLAY':
        case 'PLAY_SMUGGLE':
            return "$who plays " . $nm($e['card'] ?? null)
                . (_SwuPgnHasText($e['zone'] ?? null) ? ' to ' . $e['zone'] : '') . _SwuPgnCostPart($e);
        case 'PLAY_UPGRADE':
            $where = _SwuPgnHasText($e['target'] ?? null) ? ' on ' . $nm($e['target'])
                : (_SwuPgnHasText($e['zone'] ?? null) ? ' to ' . $e['zone'] : '');
            return "$who plays " . $nm($e['card'] ?? null) . $where . _SwuPgnCostPart($e);
        case 'PLAY_EVENT':
            return "$who plays " . $nm($e['card'] ?? null) . _SwuPgnCostPart($e);
        case 'DEPLOY_LEADER':
            return "$who deploys " . $nm($e['card'] ?? null)
                . (_SwuPgnHasText($e['target'] ?? null) ? ' as a pilot on ' . $nm($e['target']) : '');
        case 'LEADER_FLIP':
            return "$who flips " . $nm($e['card'] ?? null);
        case 'ATTACK':
            $isBase = ($e['defenderType'] ?? null) === 'base' || SwuPgnSeatOfBaseRef($e['def'] ?? null) !== null;
            return $isBase
                ? "$who attacks " . SwuPgnWho(_SwuPgnOther($p)) . "'s base with " . $nm($e['atk'] ?? null)
                : "$who attacks " . $nm($e['def'] ?? null) . ' with ' . $nm($e['atk'] ?? null);
        case 'PASS':
            return "$who passes";
        case 'CLAIM_INITIATIVE':
            return "$who claims initiative";
        case 'DAMAGE':
            return _SwuPgnTxt($e['amt'] ?? null) . ' damage to ' . $nm($e['tgt'] ?? null) . ' — ' . _SwuPgnTxt($e['hp'] ?? null) . ' HP left';
        case 'OVERWHELM':
            return _SwuPgnTxt($e['amt'] ?? null) . ' Overwhelm damage to ' . SwuPgnWho(_SwuPgnOther($p)) . "'s base — " . _SwuPgnTxt($e['hp'] ?? null) . ' HP left';
        case 'HEAL':
            return _SwuPgnTxt($e['amt'] ?? null) . ' healed on ' . $nm($e['tgt'] ?? null) . ' — ' . _SwuPgnTxt($e['hp'] ?? null) . ' HP left';
        case 'DEFEAT':
            return $nm($e['card'] ?? null) . ' is defeated' . (_SwuPgnHasText($e['defeatedBy'] ?? null) ? ' by ' . $nm($e['defeatedBy']) : '');
        case 'ABILITY_ACTIVATE':
            $kind = _SwuPgnAbilityKind($e);
            if ($kind === 'epic') return "$who uses " . $nm($e['card'] ?? null) . "'s Epic Action";
            if ($kind === 'action') return "$who uses " . $nm($e['card'] ?? null);
            return $nm($e['card'] ?? null) . ' uses an ability';
        case 'TRIGGER':
            return $nm($e['card'] ?? null) . ' triggers';
        case 'STATUS_TOKEN':
            return $nm($e['card'] ?? null) . ' ' . _SwuPgnGainsLoses($e['count'] ?? null) . ' ' . _SwuPgnTxt($e['token'] ?? null);
        case 'SHIELD_GAIN':
            return $nm($e['card'] ?? null) . ' gains ' . _SwuPgnTxt($e['count'] ?? 1) . ' shield';
        case 'SHIELD_USE':
            return $nm($e['card'] ?? null) . ' loses ' . _SwuPgnTxt($e['count'] ?? 1) . ' shield';
        case 'EXPERIENCE_GAIN':
            return $nm($e['card'] ?? null) . ' ' . _SwuPgnGainsLoses($e['count'] ?? null) . ' experience';
        case 'DRAW':
            $list = $e['cards'] ?? null;
            return "$who draws " . _SwuPgnTxt($e['count'] ?? null) . ((is_array($list) && $list) ? ': ' . _SwuPgnNames($names, $list) : '');
        case 'DISCARD':
            return "$who discards " . _SwuPgnNames($names, $e['cards'] ?? null);
        case 'RESOURCE':
            return "$who resources " . $nm($e['card'] ?? null);
        case 'REVEAL':
            return "$who reveals " . _SwuPgnNames($names, $e['cards'] ?? null);
        case 'SEARCH':
            $found = $e['found'] ?? null;
            return (is_array($found) && $found) ? "$who searches, finds " . _SwuPgnNames($names, $found) : "$who searches their deck";
        case 'CREATE_TOKEN':
            return "$who creates " . $nm($e['token'] ?? null) . ' in ' . _SwuPgnTxt($e['zone'] ?? null);
        case 'CAPTURE':
            return "$who captures " . $nm($e['card'] ?? null) . (_SwuPgnHasText($e['by'] ?? null) ? ' with ' . $nm($e['by']) : '');
        case 'RESCUE':
            return "$who rescues " . $nm($e['card'] ?? null);
        case 'TAKE_CONTROL':
            return "$who takes control of " . $nm($e['card'] ?? null);
        case 'MULLIGAN':
            return "$who mulligans";
        case 'KEEP_HAND':
            return "$who keeps their hand";
        case 'GAME_END':
            $reason = _SwuPgnTxt($e['reason'] ?? null);
            if (($e['winner'] ?? null) === 'Draw') return "*** Game ends in a draw — $reason ***";
            return '*** ' . SwuPgnWho($e['winner'] ?? null) . " wins — $reason ***";
    }
    return null;   // MOVE, EXHAUST, READY, the resource counters, STATS, CHOICE, … and unknown types
}

function _SwuPgnGainsLoses($count): string
{
    if (!is_int($count)) return 'gains ?';
    return ($count < 0 ? 'loses ' : 'gains ') . abs($count);
}

// §16 "The round banner and board summary".
function _SwuPgnRoundBanner(array $e, array $names): array
{
    $kf = array_key_exists('keyframe', $e) && SwuPgnKeyframeProblem($e['keyframe']) === null ? $e['keyframe'] : null;
    $left = ' ROUND ' . _SwuPgnTxt($e['round'] ?? null);
    $holder = $kf !== null ? SwuPgnWho($kf['initiative'] ?? null) : '';
    // Appendix A (normative) right-aligns "initiative: Player N " — trailing space included — so
    // the line is exactly the rule's width.
    $banner = $holder === '' ? $left : $left . str_pad("initiative: $holder ", max(0, SWUPGN_RULE_WIDTH - strlen($left)), ' ', STR_PAD_LEFT);
    $lines = [$banner];
    if ($kf === null) return $lines;
    foreach ([1, 2] as $s) {
        $pl = $kf['players'][$s] ?? null;
        if (!is_array($pl)) {
            $lines[] = " P$s  (not recorded)";
            continue;
        }
        $ready = $pl['resourcesReady'] ?? null;
        $exh = $pl['resourcesExhausted'] ?? null;
        $total = (is_int($ready) && is_int($exh)) ? (string)($ready + $exh) : '?';
        $line = " P$s  base " . _SwuPgnTxt($pl['baseHp'] ?? null) . '/' . _SwuPgnTxt($pl['baseMaxHp'] ?? null)
            . '   hand ' . _SwuPgnTxt($pl['handSize'] ?? null)
            . '   resources ' . _SwuPgnTxt($ready) . '/' . $total;
        if (array_key_exists('deckSize', $pl) && $pl['deckSize'] !== null) $line .= '   deck ' . _SwuPgnTxt($pl['deckSize']);
        if (is_array($pl['leader'] ?? null)) {
            $l = $pl['leader'];
            $line .= '   leader ' . (($l['deployed'] ?? null) === true ? 'deployed' : (($l['exhausted'] ?? null) === true ? 'exhausted' : 'ready'));
        }
        $lines[] = $line;
        foreach (['ground', 'space'] as $zone) {
            $shown = [];
            foreach ($pl['cards'] as $c) {
                if (is_array($c) && ($c['zone'] ?? null) === $zone) $shown[] = _SwuPgnBoardCard($c, $names);
            }
            if ($shown) $lines[] = "      $zone: " . implode('  ·  ', $shown);
        }
    }
    return $lines;
}

function _SwuPgnBoardCard(array $c, array $names): string
{
    $text = SwuPgnName($names, $c['id'] ?? null);
    if (is_int($c['power'] ?? null) && is_int($c['hp'] ?? null)) $text .= ' ' . $c['power'] . '/' . $c['hp'];
    $state = [];
    if (is_int($c['damage'] ?? null) && $c['damage'] > 0) $state[] = $c['damage'] . ' dmg';
    if (($c['exhausted'] ?? null) === true) $state[] = 'exhausted';
    if (is_int($c['shields'] ?? null) && $c['shields'] > 0) $state[] = $c['shields'] . ' shield';
    if (is_int($c['experience'] ?? null) && $c['experience'] > 0) $state[] = $c['experience'] . ' xp';
    if (is_array($c['statusTokens'] ?? null)) {
        foreach ($c['statusTokens'] as $token => $count) {
            if (is_int($count) && $count !== 0) $state[] = "$count $token";
        }
    }
    foreach (_SwuPgnList($c['upgrades'] ?? null) as $u) $state[] = SwuPgnName($names, $u);
    foreach (_SwuPgnList($c['captured'] ?? null) as $cap) $state[] = 'holds ' . SwuPgnName($names, $cap);
    return $state ? "$text [" . implode(', ', $state) . ']' : $text;
}

// §16 — public for the replay viewer's step list.
function SwuPgnIsNumberedAction(array $e): bool { return _SwuPgnIsNumbered($e); }
function SwuPgnStoryLine(array $e, array $names): ?string { return _SwuPgnStoryLine($e, $names); }
