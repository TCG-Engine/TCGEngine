<?php
/** Authoring-only canonical current Agent forms; no gameplay/replay behavior changes. */
function FCRInitialAgentForms(array $case, array $activeSeats): array {
    $allowEntryTrigger = $case['allowInitialAgentEntryTrigger'] ?? false;
    if (!is_bool($allowEntryTrigger)) {
        throw new RuntimeException('allowInitialAgentEntryTrigger must be a boolean.');
    }
    $bases = $case['initialAgentBases'] ?? [];
    if (!is_array($bases) || ($bases !== [] && array_is_list($bases))) {
        throw new RuntimeException('initialAgentBases must map active integer seats to base Arakni Hero IDs.');
    }
    $forms = []; $entryTriggers = 0;
    foreach ($bases as $seat => $base) {
        if (!is_int($seat) || !in_array($seat, $activeSeats, true)) {
            throw new RuntimeException('initialAgentBases must name only active integer seats.');
        }
        if (!is_string($base) || !in_array('Hero', (array)CardTypes($base), true)
            || strncasecmp((string)CardName($base), 'Arakni', 6) !== 0
            || (count($activeSeats) === 4 && !in_array('Young', (array)CardTypes($base), true))) {
            throw new RuntimeException('initialAgentBases requires an Arakni Hero base, young for four live seats.');
        }
        $form = $case['heroes'][$seat - 1] ?? null;
        $index = is_string($form) ? array_search($form, FaBHNTAgents(), true) : false;
        if ($index === false || ($form === 'arakni_trap_door' && !$allowEntryTrigger)) {
            throw new RuntimeException('initialAgentBases requires a supported Agent form; Trap Door requires an explicit initial entry-trigger opt-in.');
        }
        if ($form === 'arakni_trap_door') ++$entryTriggers;
        $forms[$seat] = ['base' => $base, 'form' => $form, 'index' => $index];
    }
    if ($allowEntryTrigger && ($entryTriggers !== 1 || count($forms) !== 1)) {
        throw new RuntimeException('Initial entry-trigger opt-in requires exactly one mapped Trap Door and no other mapped form.');
    }
    return $forms;
}
