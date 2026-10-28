<?php
// Explicit allowlist for the experimental React view. Never serialize engine objects.
// Temporary reveals and arbitrary choice payloads remain excluded.
function GAUIProjectState(array $state, ?int $seat): array {
    if ($seat !== null && $seat !== 1 && $seat !== 2) throw new InvalidArgumentException('Invalid viewer seat');
    $players = [];
    foreach ([1, 2] as $owner) {
        $zones = [];
        foreach (['Field', 'Intent', 'Graveyard', 'Banish', 'Hand', 'Memory', 'Material', 'Deck', 'Mastery'] as $zone) {
            $objects = array_filter($state['p' . $owner . $zone] ?? [], fn($o) => is_object($o) && empty($o->removed));
            $visible = in_array($zone, ['Field', 'Intent', 'Graveyard', 'Banish', 'Mastery'], true)
                || ($seat === $owner && in_array($zone, ['Hand', 'Memory', 'Material'], true));
            $cards = [];
            foreach ($objects as $index => $object) {
                // Concealed banish identities are omitted even for owners in this first version.
                if (!$visible || ($zone === 'Banish' && GABanishFaceDown($object))) continue;
                $card = ['id' => "p{$owner}{$zone}-{$index}", 'cardID' => (string)$object->CardID,
                    'name' => (string)CardName($object->CardID), 'printedText' => GAUIPrintedCardText((string)$object->CardID)];
                if ($zone === 'Field') {
                    $card['rested'] = intval($object->Status ?? 2) === 1;
                    $card['damage'] = intval($object->Damage ?? 0);
                }
                $cards[] = $card;
            }
            $zones[$zone] = ['count' => count($objects), 'cards' => $cards];
        }
        $players[] = ['seat' => $owner, 'damage' => intval($state['p' . $owner . 'Health'] ?? 0), 'zones' => $zones];
    }
    $choices = GAUIProjectChoices($state, $seat, $players);
    $attention = GAUIProjectAttention($state, $seat);
    if ($choices !== []) $attention['message'] = 'Choose a card below. Review the effect in the existing match screen first.';
    return ['revision' => intval($state['updateNumber']), 'viewerSeat' => $seat,
        'turn' => intval($state['gTurnNumber'] ?? 0), 'turnPlayer' => intval($state['gTurnPlayer'] ?? 1),
        'choices' => $choices, 'effectStack' => GAUIProjectEffectStack($state),
        'players' => $players, 'attention' => $attention, 'commands' => GAUICommands($state, $seat, $choices)];
}

function GAUICommands(array $state, ?int $seat, array $choices = []): array {
    if ($seat === null || intval($state['currentPlayer'] ?? 0) !== $seat) return [];
    // Execution consumes index zero, so do not skip removed entries here.
    $head = $state['p' . $seat . 'DecisionQueue'][0] ?? null;
    if (!is_object($head) || !empty($head->removed) || !in_array($head->Type ?? '', ['MZCHOOSE', 'MZMAYCHOOSE'], true)) return [];
    $commands = [];
    foreach ($choices as $choice) {
        $id = 'select:' . $choice['id'];
        $commands[] = ['id' => $id, 'label' => 'Choose ' . ($choice['name'] ?: $choice['cardID']) . ' · Player ' . $choice['owner'] . ' · ' . $choice['zone'],
            'mode' => '100', 'params' => http_build_query(['command' => $id])];
    }
    if ($head->Type === 'MZMAYCHOOSE') $commands[] = ['id' => 'decline-choice', 'label' => IsFastOpportunityDecision($head) ? 'Pass priority' : 'Decline optional choice',
        'mode' => '100', 'params' => 'command=decline-choice'];
    return $commands;
}

// Describe only the viewer's queue head. Never expose queue parameters, tooltips,
// future continuations or another player's private decision context.
function GAUIProjectAttention(array $state, ?int $seat): array {
    if ($seat === null) return ['kind' => 'spectating', 'message' => 'Watching the match.'];
    if (intval($state['currentPlayer'] ?? 0) !== $seat) {
        return ['kind' => 'waiting', 'message' => 'Waiting for the other player.'];
    }
    $head = null;
    foreach ($state['p' . $seat . 'DecisionQueue'] ?? [] as $decision) {
        if (is_object($decision) && empty($decision->removed)) { $head = $decision; break; }
    }
    if ($head === null) return ['kind' => 'active', 'message' => 'You are the active player. Continue in the match screen.'];
    if (IsFastOpportunityDecision($head)) {
        return ['kind' => 'opportunity', 'message' => 'You have an opportunity to respond. Review the match before passing.'];
    }
    $messages = [
        'MZCHOOSE' => 'Choose a card in the match screen.',
        'MZMAYCHOOSE' => 'Choose a card or decline in the match screen.',
        'MZMULTICHOOSE' => 'Choose cards in the match screen.',
        'MZREARRANGE' => 'Arrange cards in the match screen.',
    ];
    return ['kind' => 'decision', 'message' => $messages[strval($head->Type ?? '')] ?? 'Continue the pending decision in the match screen.'];
}

// Accept complete pools of ordinary card selectors already visible on
// this board. Never partially render a pool or interpret filters/ability selectors.
function GAUIProjectChoices(array $state, ?int $seat, array $players): array {
    if ($seat === null || intval($state['currentPlayer'] ?? 0) !== $seat) return [];
    $head = $state['p' . $seat . 'DecisionQueue'][0] ?? null;
    if (!is_object($head) || !empty($head->removed)
        || !in_array($head->Type ?? '', ['MZCHOOSE', 'MZMAYCHOOSE'], true)
        || IsFastOpportunityDecision($head)) return [];
    $visible = [];
    foreach ($players as $player) foreach ($player['zones'] as $zone) {
        foreach ($zone['cards'] as $card) $visible[$card['id']] = $card;
    }
    $choices = [];
    foreach (explode('&', strval($head->Param ?? '')) as $spec) {
        if (!preg_match('/^(my|their)(Field|Intent|Graveyard|Banish|Hand|Memory|Material)(?:-(0|[1-9][0-9]*))?$/', $spec, $match)) return [];
        $owner = $match[1] === 'my' ? $seat : 3 - $seat;
        $zone = $match[2];
        // Reject private zones even when empty; absence must not disclose a pool.
        if ($owner !== $seat && in_array($zone, ['Hand', 'Memory', 'Material'], true)) return [];
        $objects = $state["p{$owner}{$zone}"] ?? [];
        $indices = isset($match[3]) ? [$match[3]] : array_keys($objects);
        foreach ($indices as $index) {
            $object = $objects[$index] ?? null;
            if (!is_object($object) || !empty($object->removed)) {
                if (isset($match[3])) return [];
                continue;
            }
            $id = "p{$owner}{$zone}-{$index}";
            if (!isset($visible[$id])) return [];
            $choices[$id] = $visible[$id] + ['owner' => $owner, 'zone' => $zone];
        }
    }
    return array_values($choices);
}

// The schema explicitly makes EffectStack public. Project only printed identity,
// controller, printed reference text and a fixed category; counters may contain private captured DQ data.
function GAUIProjectEffectStack(array $state): array {
    $entries = [];
    foreach (array_reverse($state['gEffectStack'] ?? [], true) as $index => $entry) {
        if (!is_object($entry) || !empty($entry->removed)) continue;
        $cardID = strval($entry->CardID ?? '');
        $controller = intval($entry->Controller ?? 0);
        $entries[] = [
            'id' => 'EffectStack-' . $index,
            'cardID' => $cardID,
            'name' => (string)CardName($cardID),
            'printedText' => GAUIPrintedCardText($cardID),
            'triggerLabel' => GAUIPublicTriggerLabel($entry->TriggerType ?? null),
            'controller' => in_array($controller, [1, 2], true) ? $controller : null,
            'kind' => in_array($entry->TriggerType ?? '', ['', '-'], true) ? 'Activation' : 'Triggered ability',
        ];
    }
    return $entries;
}

// Static dictionary text only, never the runtime ability/captured decision data.
// Return plain text; clients must render it as text, not HTML.
function GAUIPrintedCardText(string $cardID): string {
    $text = (string)CardEffect($cardID);
    $text = preg_replace('/<br\s*\/?\s*>/i', "\n", $text);
    return trim(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

// Fixed public descriptions of resolver branches, never arbitrary engine tags.
// These identify the trigger event, not the exact (possibly copied) ability.
function GAUIPublicTriggerLabel($type): ?string {
    if (!is_string($type)) return null;
    return [
        'ENTER' => 'On Enter',
        'BREW' => 'Brew',
        'WITHER' => 'Wither',
        'ALLY_DESTROYED' => 'Ally destroyed',
        'LEAVE_FIELD' => 'Leave field',
        'ON_BANISH' => 'Card banished',
        'DISCARD_CARD' => 'Card discarded',
        'REVEAL_CARD' => 'Card revealed',
        'PLAY_CARD' => 'Card played',
        'REST_CARD' => 'Card rested',
        'DEAL_DAMAGE' => 'Damage dealt',
        'ON_ATTACK' => 'On Attack',
        'ON_HIT' => 'On Hit',
        'ON_KILL' => 'On Kill',
    ][$type] ?? null;
}
