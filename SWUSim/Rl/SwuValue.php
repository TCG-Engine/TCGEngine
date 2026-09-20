<?php
// The @value chooser — spec docs/superpowers/specs/2026-09-19-swusim-value-model-design.md §7.
// On only for 'heuristic-<style>@value', only at FREE-PLAY decisions that reach the fallback: layer-2 rules still run
// first, and prompts outside a planned line, resourcing and mulligans stay heuristic. Every candidate (pass and the
// initiative included) is applied by the sequence lookahead; each line's end is scored by the learned model
// (P(win) from this seat's view); the best line wins, ties to the lowest index, and its follow-up answers become the
// seat's plan for the planned-answer rule.
// HAND GUARD: hand features come from the hand BEFORE the move minus the card played (SwuValueFeatures.php header).
// Env: SWU_VALUE_MODEL = path to value-model.json. Missing/unreadable/version-mismatched ⇒ heuristic + 'value:off'.

function SWUValueModel(): ?array {
    static $cache = [];
    $path = strval(getenv('SWU_VALUE_MODEL') ?: '');
    if ($path === '' || !is_file($path)) return null;
    $k = $path . '|' . @filemtime($path) . '|' . @filesize($path);
    if (!array_key_exists($k, $cache)) {
        $m = json_decode(strval(@file_get_contents($path)), true);
        // The model names the features it uses (the trainer may drop collinear columns); every one must exist in
        // this extractor, whose version must match the one the data was logged with.
        $names = (array)($m['names'] ?? []);
        $ok = is_array($m) && ($m['featureVersion'] ?? '') === SWUValueFeatureVersion() && !empty($names)
              && !array_diff($names, SWUValueFeatureNames())
              && count($m['weights'] ?? []) === count($names) && count($m['means'] ?? []) === count($names)
              && count($m['stds'] ?? []) === count($names);
        if (!$ok) fwrite(STDERR, "[value] model $path refused (missing fields or featureVersion != " . SWUValueFeatureVersion() . ")\n");
        $cache = [$k => $ok ? $m : null];
    }
    return $cache[$k];
}

function SWUValueScore(array $features, array $model): float {
    $z = floatval($model['bias']);
    foreach ($model['names'] as $i => $n) {
        $z += floatval($model['weights'][$i]) * (floatval($features[$n]) - floatval($model['means'][$i])) / floatval($model['stds'][$i]);
    }
    return 1.0 / (1.0 + exp(-$z));
}

function SWUValueChoose(array $ctx, ?array $fallbackPick): ?array {
    if (($ctx['kind'] ?? '') !== 'free-play' || $fallbackPick === null || count($ctx['actions']) < 2) return $fallbackPick;
    $seat = intval($ctx['seat']); $style = strval($ctx['style']);
    $model = SWUValueModel();
    if ($model === null) { SWUBotRecordCoverage($seat, 'value:off'); return $fallbackPick; }
    $hand = SWUValueHandSnapshot($seat);   // BEFORE any lookahead — the hand guard
    $handIdx = []; $i = 0;
    foreach (GetHand($seat) as $zi => $o) { if ($o !== null && empty($o->removed)) $handIdx[$zi] = $i++; }
    $cands = array_values($ctx['actions']);
    $left = SWU_BOT_LOOKAHEAD_BUDGET;
    $best = null; $bestLine = null;
    foreach ($cands as $ci => $a) {
        $h = $hand;
        if (SWUBotActionKind($a) === 'play') {
            $zi = intval(substr(SWUBotActionMz($a), strlen('myHand-')));
            if (isset($handIdx[$zi])) array_splice($h, $handIdx[$zi], 1);
        }
        $read = fn() => ['f' => SWUValueFeatures($seat, $h, $style)];
        $score = fn(array $r) => SWUValueScore($r['f'], $model);
        $share = max(1, intdiv($left, count($cands) - $ci));
        $before = intval($GLOBALS['SWUBotLookaheadCalls'] ?? 0);
        $line = SWUBotLookaheadBest($seat, $a, $read, $score, SWU_BOT_LOOKAHEAD_DEPTH, $share);
        $left -= intval($GLOBALS['SWUBotLookaheadCalls'] ?? 0) - $before;
        if ($line === null) continue;
        if ($bestLine === null || $line['_score'] > $bestLine['_score']) { $best = $a; $bestLine = $line; }
    }
    if ($best === null) { SWUBotRecordCoverage($seat, 'value:off'); return $fallbackPick; }
    SWUBotRecordCoverage($seat, 'value:chose');
    if (strval($best['cardID'] ?? '') !== strval($fallbackPick['cardID'] ?? '')) SWUBotRecordCoverage($seat, 'value:disagree');
    if (!empty($bestLine['_path'])) $GLOBALS['SWUBotPlan'][$seat] = $bestLine['_path'];
    else unset($GLOBALS['SWUBotPlan'][$seat]);
    return $best;
}
