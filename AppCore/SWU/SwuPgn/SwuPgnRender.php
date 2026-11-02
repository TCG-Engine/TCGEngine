<?php
// SWU-PGN storyteller — events → the %%% STORY text (SWU-PGN/1.0 spec §16, the strict renderer [R-02]).
//
// Numbered lines are the actions a player chose; every other printed record is indented under the
// action above it; a round banner (with the board from the round's keyframe) introduces each round.
// Names come from the file's own %%% CARDS index unless the caller supplies one; an id the index
// does not cover prints as the id itself. A card with a backSideName reads by the side that is up, a
// copy unit by its copied name (both tracked per full id by SwuPgnFacesStep); back names always come
// from the file.
//
// Where §16 says "+ X if field", the part is printed when the field is present and usable: a
// non-empty string for zone/target/by/defeatedBy/grantedBy/usedBy/text, a non-empty list for
// cards/targets, any integer (0 included) for cost. A field the wording needs but the record lacks
// prints as "?". Every printed string from the file has its line breaks, other controls and bidi
// controls turned into spaces (§16 layout, [R-04]); nm() text is also cut at 120 UTF-16 units.

const SWUPGN_RULE_WIDTH = 78;
const SWUPGN_NAME_CUT = 120;            // §16 nm(): longer → first 119 UTF-16 units + "…"
const SWUPGN_STORY_CAP = 16777216;      // §16: a story is at most this many UTF-16 units (a line feed per line)
const SWUPGN_STORY_STOP = '… the story stops here: it is longer than 16777216 characters';

function SwuPgnRender(array $doc, ?array $names = null): string
{
    return SwuPgnRenderEvents($doc['events'] ?? [], $names ?? SwuPgnCardIndex($doc), SwuPgnCardBackIndex($doc));
}

function SwuPgnRenderEvents(array $events, array $names, array $backs = []): string
{
    $out = [];
    $n = 0;
    $len = 0;   // the story so far in UTF-16 units, one line feed counted per line (§16 cap)
    $rule = str_repeat('═', SWUPGN_RULE_WIDTH);
    $count = 0;
    $faces = SwuPgnFacesStart($backs);
    foreach ($events as $e) {
        if (++$count > SWUPGN_MAX_EVENTS) break;
        if (!is_array($e)) continue;
        $t = $e['t'] ?? null;
        $lines = [];
        if ($t === 'ROUND_START') {
            $faces = SwuPgnFacesStep($faces, $e);   // its keyframe says which side is up and what each copy is
            $lines = array_merge(['', $rule], _SwuPgnRoundBanner($e, $names, $faces), [$rule, '']);
            $n = 0;
        } elseif ($t === 'PHASE_START') {
            $lines[] = ' ── ' . _SwuPgnTxt($e['phase'] ?? null) . ' ──';
            $n = 0;
        } elseif ($t === 'UNDO') {
            $who = SwuPgnWho($e['by'] ?? null);
            $lines[] = '    ·· ' . ($who === '' ? 'A player' : $who) . ' undid back to ' . _SwuPgnTxt($e['at'] ?? null) . ' ··';
        } else {
            $text = _SwuPgnStoryLine($e, $names, $faces);
            $faces = SwuPgnFacesStep($faces, $e);
            if ($text !== null) {
                if (_SwuPgnIsNumbered($e)) $lines[] = ' ' . str_pad((string)++$n, 2, ' ', STR_PAD_LEFT) . '. ' . $text;
                else $lines[] = '       ↳ ' . $text;
            }
        }
        if (!$lines) continue;
        // §16: before a record is printed while the story is already over the cap, it ends with the stop line.
        if ($len > SWUPGN_STORY_CAP) {
            $out[] = SWUPGN_STORY_STOP;
            break;
        }
        foreach ($lines as $l) {
            $out[] = $l;
            $len += _SwuPgnUtf16Len($l) + 1;
        }
    }
    return implode("\n", $out);
}

// Compares the file's %%% STORY with a fresh render. Trailing blank lines in the file are layout
// before the next banner, not story. `line` is the first differing line (1-based).
function SwuPgnStoryMatches(array $doc, ?array $names = null): array
{
    if (!is_array($doc['story'] ?? null)) return ['present' => false, 'matches' => false, 'line' => null];
    $trim = function (array $lines): array {   // §4: blank lines at the very start and end are banner spacing
        while ($lines && trim(end($lines)) === '') array_pop($lines);
        while ($lines && trim($lines[0]) === '') array_shift($lines);
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

// ── text from the file: controls → space, nm() cut (§16 helpers, layout rules) ─────────────────────

// §16: each line break or other control character (C0, C1, U+2028, U+2029) and each bidirectional control
// (U+061C, U+200E/F, U+202A–E, U+2066–9) becomes a space. DEL (U+007F) is neither C0 nor C1 and is kept.
function SwuPgnStoryText(string $s): string
{
    $re = '/[\x{0}-\x{1F}\x{80}-\x{9F}\x{2028}\x{2029}\x{61C}\x{200E}\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}]/u';
    $r = preg_replace($re, ' ', $s);
    if ($r === null) $r = preg_replace($re, ' ', mb_scrub($s, 'UTF-8')) ?? '';   // not UTF-8: scrub first
    return $r;
}

// §16 "cut like a card name": controls → space, then longer than 120 UTF-16 units → the first 119 and "…".
// A cut through a surrogate pair leaves its lone first half, which UTF-8 writes as U+FFFD.
function SwuPgnCutName(string $s): string
{
    $s = SwuPgnStoryText($s);
    if (strlen($s) <= SWUPGN_NAME_CUT || _SwuPgnUtf16Len($s) <= SWUPGN_NAME_CUT) return $s;
    $kept = '';
    $units = 0;
    foreach (mb_str_split($s, 1, 'UTF-8') as $ch) {
        $w = strlen($ch) === 4 ? 2 : 1;
        if ($units + $w > SWUPGN_NAME_CUT - 1) {
            if ($units < SWUPGN_NAME_CUT - 1) $kept .= "\u{FFFD}";
            break;
        }
        $kept .= $ch;
        $units += $w;
    }
    return $kept . '…';
}

function _SwuPgnUtf16Len(string $s): int
{
    $cp = mb_strlen($s, 'UTF-8');
    return strlen($s) === $cp ? $cp : $cp + preg_match_all('/[\xF0-\xF7]/', $s);
}

// §10.1 icon markup → plain text: {keyword:<name>[:<n>]} → "Name n", {resource:<n>} → "n resource(s)",
// :<aspect>: → the aspect capitalised, aspect tokens side by side joined by one space.
function SwuPgnPlainMarkup(string $s): string
{
    $s = preg_replace_callback('/\{keyword:([^{}:]+)(?::(\d+))?\}/', fn($m) => ucfirst($m[1]) . (isset($m[2]) ? ' ' . $m[2] : ''), $s) ?? $s;
    $s = preg_replace_callback('/\{resource:(\d+)\}/', fn($m) => $m[1] . ($m[1] === '1' ? ' resource' : ' resources'), $s) ?? $s;
    $aspect = 'vigilance|command|aggression|cunning|villainy|heroism';
    return preg_replace_callback("/(?::(?:$aspect):)+/i", function ($m) use ($aspect) {
        preg_match_all("/:($aspect):/i", $m[0], $a);
        return implode(' ', array_map(fn($x) => ucfirst(strtolower($x)), $a[1]));
    }, $s) ?? $s;
}

// A printed field: a string with controls made spaces, an integer as digits; anything else "?".
function _SwuPgnTxt($v): string
{
    if (is_string($v)) return SwuPgnStoryText($v);
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

// §9.1 a lettered step (R2.A.3a): a consequence, never a player's own action.
function _SwuPgnIsLetteredSeq($seq): bool
{
    return is_string($seq) && preg_match('/^R\d+\.[SAG]\.\d+[A-Za-z]+(-undo)?\z/', $seq) === 1;
}

// §16 "Numbering": the action types, each only when it is a player's action for the turn — a lettered seq is never
// numbered, whatever its type (a play / attack / action ability an ability makes, a leader's own Epic Action deploy).
function _SwuPgnIsNumbered(array $e): bool
{
    if (_SwuPgnIsLetteredSeq($e['seq'] ?? null)) return false;
    $t = $e['t'] ?? null;
    if (in_array($t, ['PLAY', 'PLAY_EVENT', 'PLAY_UPGRADE', 'PLAY_SMUGGLE', 'DEPLOY_LEADER', 'ATTACK', 'PASS', 'CLAIM_INITIATIVE'], true)) return true;
    return $t === 'ABILITY_ACTIVATE' && in_array(_SwuPgnAbilityKind($e), ['action', 'epic'], true);
}

// ── what nm() needs to know as the story goes (§16 nm()) ─────────────────────────────────────────
// $faces = ['backs' => [baseId => backSideName], 'up' => [fullId => true] (back side up),
//           'copy' => [fullId => copied name], 'fx' => [effect id => src]].

function SwuPgnFacesStart(array $backs): array
{
    return ['backs' => $backs, 'up' => [], 'copy' => [], 'fx' => []];
}

function _SwuPgnFaceSet(array $faces, $id, bool $back): array
{
    if (!is_string($id) || !isset($faces['backs'][SwuPgnBaseId($id)])) return $faces;
    if ($back) $faces['up'][$id] = true; else unset($faces['up'][$id]);
    return $faces;
}

// The faces after event $e. Side up: the back from a DEPLOY_LEADER (or a MOVE from base into an arena) until the MOVE
// back to base; a double-sided leader per its LEADER_FLIP. A copy's name: the last STATS carrying copyOf AND name,
// until a STATS without them or a MOVE out of the arenas. A ROUND_START keyframe re-syncs both (a ROUND_END one does
// not). EFFECT_START notes its src for the EFFECT_END wording.
function SwuPgnFacesStep(array $faces, array $e): array
{
    $faces += ['copy' => [], 'fx' => []];
    $card = $e['card'] ?? null;
    switch ($e['t'] ?? null) {
        case 'DEPLOY_LEADER':
            return _SwuPgnFaceSet($faces, $card, true);
        case 'LEADER_FLIP':
            return is_bool($e['onStartingSide'] ?? null) ? _SwuPgnFaceSet($faces, $card, !$e['onStartingSide']) : $faces;
        case 'MOVE':
            if (SwuPgnIsArena($e['from'] ?? null) && !SwuPgnIsArena($e['to'] ?? null) && is_string($card)) unset($faces['copy'][$card]);
            if (($e['from'] ?? null) === 'base' && SwuPgnIsArena($e['to'] ?? null)) return _SwuPgnFaceSet($faces, $card, true);
            if (SwuPgnIsArena($e['from'] ?? null) && ($e['to'] ?? null) === 'base') return _SwuPgnFaceSet($faces, $card, false);
            return $faces;
        case 'STATS':
            if (!is_string($card)) return $faces;
            if (is_string($e['copyOf'] ?? null) && is_string($e['name'] ?? null)) $faces['copy'][$card] = $e['name'];
            else unset($faces['copy'][$card]);
            return $faces;
        case 'EFFECT_START':
            if (is_string($e['id'] ?? null) && (is_string($e['src'] ?? null) || is_int($e['src'] ?? null))) $faces['fx'][$e['id']] = $e['src'];
            return $faces;
        case 'ROUND_START':
            if (!array_key_exists('keyframe', $e) || SwuPgnKeyframeProblem($e['keyframe']) !== null) return $faces;
            $faces['copy'] = [];
            foreach ([1, 2] as $s) {
                $pl = $e['keyframe']['players'][$s] ?? null;
                if (!is_array($pl)) continue;
                $l = $pl['leader'] ?? null;
                if (is_array($l)) $faces = _SwuPgnFaceSet($faces, $l['id'] ?? null, ($l['deployed'] ?? null) === true || ($l['onStartingSide'] ?? null) === false);
                foreach (is_array($pl['cards'] ?? null) ? $pl['cards'] : [] as $c) {
                    if (is_array($c) && is_string($c['id'] ?? null) && is_string($c['copyOf'] ?? null) && is_string($c['name'] ?? null)) $faces['copy'][$c['id']] = $c['name'];
                }
            }
            return $faces;
    }
    return $faces;
}

// §16 nm(): `Player N's base` for base@N; else the copy's name, the side that is up, the index name or the id itself,
// with ` #N` for a copy — the whole cut like a card name.
function _SwuPgnNm(array $names, $id, ?array $faces = null): string
{
    if (is_int($id)) $id = (string)$id;
    if (!is_string($id)) return '?';
    $seat = SwuPgnSeatOfBaseRef($id);
    if ($seat !== null) return "Player $seat's base";
    $base = SwuPgnBaseId($id);
    $name = $faces['copy'][$id] ?? (isset($faces['up'][$id]) ? ($faces['backs'][$base] ?? null) : null) ?? $names[$base] ?? $base;
    if (!is_string($name)) $name = $base;
    $copy = SwuPgnCopyNumber($id);
    return SwuPgnCutName($copy === null ? $name : "$name #$copy");
}

// front(id) / back(id): the index's name or backSideName, with the copy's ` #N`, cut the same way.
function _SwuPgnSideName(array $names, array $faces, $id, bool $back): string
{
    if (!is_string($id)) return '?';
    return _SwuPgnNm($names, $id, $back ? ['backs' => $faces['backs'], 'up' => [$id => true]] : null);
}

// {names}: nm of each id joined by " · ".
function _SwuPgnNames(array $names, $list, ?array $faces = null): string
{
    if (!is_array($list)) return '';
    $out = [];
    foreach ($list as $id) $out[] = _SwuPgnNm($names, $id, $faces);
    return implode(' · ', $out);
}

function _SwuPgnOther($p): int
{
    return $p === 1 ? 2 : 1;   // §16: "the other player" is p === 1 ? 2 : 1
}

function _SwuPgnCostPart(array $e): string
{
    return is_int($e['cost'] ?? null) ? ' (cost ' . $e['cost'] . ')' : '';
}

// §16 EFFECT_START {lasts}, EFFECT_END {why}: the closed values' words; any other value printed as itself.
function _SwuPgnLasts(array $e, callable $nm): string
{
    $d = $e['duration'] ?? null;
    switch ($d) {
        case 'attack': return 'for this attack';
        case 'phase': return 'for this phase';
        case 'round': return 'for this round';
        case 'whileSourceInPlay': return 'while ' . $nm($e['src'] ?? null) . ' is in play';
        case 'until': return 'until ' . (_SwuPgnHasText($e['untilText'] ?? null) ? SwuPgnCutName($e['untilText']) : 'a condition is met');
        case 'delayed': return 'delayed';
        case 'game': return 'for the rest of the game';
    }
    return _SwuPgnTxt($d);
}

function _SwuPgnWhy($reason): string
{
    $words = ['expired' => 'expired', 'fired' => 'it resolved', 'sourceLeft' => 'its source left play', 'targetLeft' => 'its target changed zone', 'cancelled' => 'cancelled'];
    return is_string($reason) && isset($words[$reason]) ? $words[$reason] : _SwuPgnTxt($reason);
}

// §16 "The exact wording". Null = prints nothing. $faces: what nm() knows at this event (SwuPgnFacesStep).
function _SwuPgnStoryLine(array $e, array $names, ?array $faces = null): ?string
{
    $p = $e['p'] ?? null;
    $who = SwuPgnWho($p);
    $faces ??= SwuPgnFacesStart([]);
    $nm = function ($id) use ($names, $faces) { return _SwuPgnNm($names, $id, $faces); };
    $list = function ($ids) use ($names, $faces) { return _SwuPgnNames($names, $ids, $faces); };
    $nonEmpty = fn($v) => is_array($v) && $v;
    $twoSided = fn($id) => is_string($id) && isset($faces['backs'][SwuPgnBaseId($id)]);
    switch ($e['t'] ?? null) {
        case 'PLAY':
        case 'PLAY_SMUGGLE':
            return "$who plays " . $nm($e['card'] ?? null)
                . (_SwuPgnHasText($e['zone'] ?? null) ? ' to ' . _SwuPgnTxt($e['zone']) : '') . _SwuPgnCostPart($e);
        case 'PLAY_UPGRADE':
            $where = _SwuPgnHasText($e['target'] ?? null) ? ' on ' . $nm($e['target'])
                : (_SwuPgnHasText($e['zone'] ?? null) ? ' to ' . _SwuPgnTxt($e['zone']) : '');
            return "$who plays " . $nm($e['card'] ?? null) . $where . _SwuPgnCostPart($e);
        case 'PLAY_EVENT':
            return "$who plays " . $nm($e['card'] ?? null) . _SwuPgnCostPart($e);
        case 'DEPLOY_LEADER':
            $card = $e['card'] ?? null;
            return "$who deploys " . _SwuPgnSideName($names, $faces, $card, false)
                . ($twoSided($card) ? ' as ' . _SwuPgnSideName($names, $faces, $card, true) : '')
                . (_SwuPgnHasText($e['target'] ?? null) ? ' as a pilot on ' . $nm($e['target']) : '');
        case 'LEADER_FLIP':
            $card = $e['card'] ?? null;
            $landed = is_bool($e['onStartingSide'] ?? null) && $twoSided($card) ? _SwuPgnSideName($names, $faces, $card, !$e['onStartingSide']) : null;
            return "$who flips " . $nm($card) . ($landed !== null ? " to $landed" : '');
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
            $tail = (_SwuPgnHasText($e['grantedBy'] ?? null) ? ' (granted by ' . $nm($e['grantedBy']) . ')' : '')
                . (_SwuPgnHasText($e['usedBy'] ?? null) ? ' (used by ' . $nm($e['usedBy']) . ')' : '');
            if ($kind === 'epic') return "$who uses " . $nm($e['card'] ?? null) . "'s Epic Action$tail";
            if ($kind === 'action') return "$who uses " . $nm($e['card'] ?? null) . $tail;
            return $nm($e['card'] ?? null) . ' uses an ability' . $tail;
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
            return "$who draws " . _SwuPgnTxt($e['count'] ?? null) . ($nonEmpty($e['cards'] ?? null) ? ': ' . $list($e['cards']) : '');
        case 'DISCARD':
            return "$who discards " . $list($e['cards'] ?? null);
        case 'RESOURCE':
            return "$who resources " . $nm($e['card'] ?? null);
        case 'REVEAL':
            return "$who reveals " . $list($e['cards'] ?? null);
        case 'SEARCH':
            return $nonEmpty($e['found'] ?? null) ? "$who searches, finds " . $list($e['found']) : "$who searches their deck";
        case 'LOOK':
            $count = $e['count'] ?? null;
            return "$who looks at " . _SwuPgnTxt($count) . ($count === 1 ? ' card' : ' cards') . ' of ' . SwuPgnWho($e['owner'] ?? null) . "'s " . _SwuPgnTxt($e['zone'] ?? null)
                . ($nonEmpty($e['cards'] ?? null) ? ': ' . $list($e['cards']) : '');
        case 'NAME':
            return "$who names " . (is_string($e['name'] ?? null) ? SwuPgnCutName($e['name']) : '?');
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
        case 'EFFECT_START':
            $seat = $e['seat'] ?? null;
            return "$who: " . $nm($e['src'] ?? null)
                . (_SwuPgnHasText($e['text'] ?? null) ? ' — ' . SwuPgnCutName(SwuPgnPlainMarkup($e['text'])) : '')
                . ($nonEmpty($e['targets'] ?? null) ? ' on ' . $list($e['targets'])
                    : ($seat === 1 || $seat === 2 ? ' for ' . SwuPgnWho($seat) : ($seat === 'both' ? ' for both players' : '')))
                . ' (' . _SwuPgnLasts($e, $nm) . ')';
        case 'EFFECT_END':
            $id = $e['id'] ?? null;
            $src = is_string($id) && array_key_exists($id, $faces['fx'] ?? []) ? $faces['fx'][$id] : null;
            return ($src !== null ? 'Effect from ' . $nm($src) . ' ends' : 'Effect ' . _SwuPgnTxt($id) . ' ends')
                . ($nonEmpty($e['targets'] ?? null) ? ' on ' . $list($e['targets']) : '')
                . ' (' . _SwuPgnWhy($e['reason'] ?? null) . ')';
    }
    return null;   // MOVE, EXHAUST, READY, the resource counters, STATS, CHOICE, ABILITY_RESET, … and unknown types
}

function _SwuPgnGainsLoses($count): string
{
    if (!is_int($count)) return 'gains ?';
    return ($count < 0 ? 'loses ' : 'gains ') . abs($count);
}

// §16 "The round banner and board summary".
function _SwuPgnRoundBanner(array $e, array $names, ?array $faces = null): array
{
    $kf = array_key_exists('keyframe', $e) && SwuPgnKeyframeProblem($e['keyframe']) === null ? $e['keyframe'] : null;
    $left = ' ROUND ' . _SwuPgnTxt($e['round'] ?? null);
    $holder = $kf !== null ? SwuPgnWho($kf['initiative'] ?? null) : '';
    // "initiative: Player N " (trailing space) ends at column 78; two parts that do not fit join with no space.
    $right = "initiative: $holder ";
    $banner = $holder === '' ? $left : $left . str_repeat(' ', max(0, SWUPGN_RULE_WIDTH - _SwuPgnUtf16Len($left) - strlen($right))) . $right;
    $lines = [$banner];
    if ($kf === null) return $lines;
    foreach ([1, 2] as $s) {
        $pl = $kf['players'][$s];   // both seats are there: a keyframe missing one is damaged (§13)
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
        // What the base holds comes first: all of it is on the base.
        $held = [];
        foreach (_SwuPgnList($pl['baseUpgrades'] ?? null) as $u) $held[] = _SwuPgnNm($names, $u, $faces);
        foreach (_SwuPgnList($pl['baseCaptured'] ?? null) as $cap) $held[] = 'holds ' . _SwuPgnNm($names, $cap, $faces);
        if ($held) $lines[] = '      base: ' . implode('  ·  ', $held);
        foreach (['ground', 'space'] as $zone) {
            $shown = [];
            foreach ($pl['cards'] as $c) {
                if (is_array($c) && ($c['zone'] ?? null) === $zone) $shown[] = _SwuPgnBoardCard($c, $names, $faces);
            }
            if ($shown) $lines[] = "      $zone: " . implode('  ·  ', $shown);
        }
    }
    return $lines;
}

// A board card: its name (a copy's when the keyframe card has copyOf and name — SwuPgnFacesStep took them from this
// very keyframe), ` power/hp`, then its non-default state.
function _SwuPgnBoardCard(array $c, array $names, ?array $faces = null): string
{
    $text = _SwuPgnNm($names, $c['id'] ?? null, $faces);
    if (is_int($c['power'] ?? null) && is_int($c['hp'] ?? null)) $text .= ' ' . $c['power'] . '/' . $c['hp'];
    $state = [];
    if (is_int($c['damage'] ?? null) && $c['damage'] > 0) $state[] = $c['damage'] . ' dmg';
    if (($c['exhausted'] ?? null) === true) $state[] = 'exhausted';
    if (is_int($c['shields'] ?? null) && $c['shields'] > 0) $state[] = $c['shields'] . ' shield';
    if (is_int($c['experience'] ?? null) && $c['experience'] > 0) $state[] = $c['experience'] . ' xp';
    if (is_array($c['statusTokens'] ?? null) || is_object($c['statusTokens'] ?? null)) {
        foreach ((array)$c['statusTokens'] as $token => $count) {
            if (is_int($count) && $count !== 0) $state[] = "$count " . _SwuPgnTxt((string)$token);
        }
    }
    foreach (_SwuPgnList($c['upgrades'] ?? null) as $u) $state[] = _SwuPgnNm($names, $u, $faces);
    foreach (_SwuPgnList($c['captured'] ?? null) as $cap) $state[] = 'holds ' . _SwuPgnNm($names, $cap, $faces);
    return $state ? "$text [" . implode(', ', $state) . ']' : $text;
}

// §16 — public for the replay viewer's step list.
function SwuPgnIsNumberedAction(array $e): bool { return _SwuPgnIsNumbered($e); }
function SwuPgnStoryLine(array $e, array $names, ?array $faces = null): ?string { return _SwuPgnStoryLine($e, $names, $faces); }
