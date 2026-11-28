<?php
/**
 * Explicit, reviewed FINAL-snapshot compatibility; never used by live gameplay,
 * initial-state assertions or noop auditing. Default runner comparison is strict.
 */
function FaBRegressionReviewedFinalCompatibility(string $expectedRaw, string $actual, array $policy): array {
    if (($policy['version'] ?? '') !== 'resolved-source-accounting-v1'
        || !is_string($policy['expected_raw_sha256'] ?? null)
        || !hash_equals($policy['expected_raw_sha256'], hash('sha256', $expectedRaw))
        || !is_string($policy['card_id'] ?? null)
        || $policy['card_id'] === '') return [false, 'Reviewed compatibility policy/hash missing or stale.'];
    $normal = [];
    foreach ([$expectedRaw, $actual] as $text) {
        $text = RegressionNormalizeNewlines(RegressionNormalizeGamestateTextForComparison('FaBSim', $text));
        $lines = explode("\n", $text);
        if (end($lines) === '') array_pop($lines);
        $layout = RegressionGamestateSchemaLayout('FaBSim');
        $consumed = RegressionConsumeGamestateLayout($layout, $lines, $blocks, 4);
        // This FaBSim schema includes its final RandomCounter scalar.
        if ($consumed !== count($lines) || !ctype_digit((string)end($lines)))
            return [false, 'Unrecognized complete FaBSim snapshot shape.'];
        $game = null; $vars = null; $queues = 0; $stacks = 0;
        foreach ($blocks as $block) {
            $name = $block['name']; $at = $block['start'];
            if ($name === 'Stack' || $name === 'DecisionQueue') {
                if ($block['length'] !== 1 || ($lines[$at] ?? null) !== '0')
                    return [false, 'Stack or a decision queue is active.'];
                if ($name === 'Stack') ++$stacks; else ++$queues;
            }
            if ($name === 'GameState') {
                if ($game !== null || $block['length'] !== 1) return [false, 'Ambiguous GameState.'];
                $game = $at;
            }
            if ($name === 'DecisionQueueVariables') {
                if ($vars !== null || $block['length'] !== 1) return [false, 'Ambiguous source context.'];
                $vars = $at;
            }
        }
        if ($game === null || $vars === null || $queues !== 4 || $stacks !== 1)
            return [false, 'Required all-seat quiet-state blocks missing.'];
        try {
            $state = json_decode($lines[$game], false, 512, JSON_THROW_ON_ERROR);
            $context = json_decode($lines[$vars], false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $error) { return [false, 'Invalid state/context JSON.']; }
        if (!is_object($state) || !is_object($context)
            || ($state->window ?? null) !== 'ACTION'
            || ($state->combatOpen ?? null) !== false
            || ($state->combatStep ?? null) !== 'NONE'
            || ($state->attackUID ?? null) !== 0
            || ($state->chainLink ?? null) !== 0)
            return [false, 'Compatibility requires completed quiet Action state.'];
        foreach (get_object_vars($state) as $key => $value) {
            if ((str_starts_with($key, 'pending') || str_starts_with($key, 'resolving'))
                && $value !== null && $value !== false && $value !== [])
                return [false, 'A resolution or payment continuation is pending.'];
        }
        // This exact six-field capsule has no await frames or other variables.
        if (array_keys(get_object_vars($context)) !== ['mzID','mzIDCardID','cardID','fromZone','fabSourceZone','evoEvent']
            || !in_array($context->mzID, ['p1Graveyard-0','Stack-0'], true)
            || $context->mzIDCardID !== $policy['card_id'] || $context->cardID !== $policy['card_id']
            || $context->fromZone !== 'Hand' || $context->fabSourceZone !== 'Hand' || $context->evoEvent !== '')
            return [false, 'Source context is not the exact reviewed retired capsule.'];
        $context->mzID = '<completed-source-reference>';
        // These exact full accounting rows are read by amount/type consumers.
        // Do NOT merge amounts, discard duplicates, reorder real effects, or
        // reorder the live state. All other state/zone/UID data remains exact.
        if (!is_object($state->turnEffects ?? null)) return [false, 'Unrecognized accounting container.'];
        $effects = new stdClass();
        foreach (get_object_vars($state->turnEffects) as $seat => $rows) {
            if (!in_array((string)$seat, ['1','2','3','4'], true) || !is_array($rows))
                return [false, 'Unrecognized accounting seat/list.'];
            $ordered = []; $accounting = [];
            foreach ($rows as $row) {
                if (!is_object($row)) return [false, 'Unrecognized effect row.'];
                if (in_array($row->type ?? null, ['EVR_GRAVEYARD','SEA_BLUE_GRAVE','ROS_LIFE'], true)) {
                    if (array_keys(get_object_vars($row)) !== ['type','amount','uses']
                        || !is_int($row->amount) || $row->amount <= 0 || $row->uses !== 1)
                        return [false, 'Accounting row has noncanonical payload.'];
                    $accounting[] = json_encode($row, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
                } else $ordered[] = $row;
            }
            sort($accounting, SORT_STRING);
            $effects->{(string)$seat} = (object)['orderedEffects'=>$ordered, 'accountingRows'=>$accounting];
        }
        $state->turnEffects = $effects;
        $lines[$game] = json_encode($state, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
        $lines[$vars] = json_encode($context, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
        $normal[] = $lines;
    }
    return $normal[0] === $normal[1]
        ? [true, 'Passed explicit reviewed final-only accounting/retired-source compatibility; live state and assertions unchanged.']
        : [false, 'A difference remains outside the reviewed accounting/source-reference policy.'];
}
