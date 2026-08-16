<?php

/**
 * Shared transient game history for simulator roots.
 *
 * Roots opt in with `Module: SimHistory=...` and a hidden, global object zone named
 * SimHistory. Snapshot payloads deliberately reuse the generated Versions serializer,
 * but the timeline itself does not use either player's Versions zone for storage.
 */

if (!defined('SIM_HISTORY_FORMAT_VERSION')) define('SIM_HISTORY_FORMAT_VERSION', 1);

function SimHistoryCapabilityEnabled(): bool {
    return function_exists('GetModuleConfig')
        && GetModuleConfig('SimHistory') !== null
        && function_exists('GetSimHistory')
        && class_exists('SimHistory')
        && class_exists('Versions')
        && method_exists('Versions', 'GetSerializedZones');
}

function SimHistoryConfig(): array {
    $config = [
        'Policy' => 'casual',
        'MaxEntries' => 101,
        'MaxBytes' => 2097152,
    ];
    if (!SimHistoryCapabilityEnabled()) return $config;

    $raw = strval(GetModuleConfig('SimHistory'));
    foreach (explode(',', $raw) as $part) {
        $pair = explode('=', trim($part), 2);
        if (count($pair) !== 2) continue;
        $key = trim($pair[0]);
        $value = trim($pair[1]);
        if ($key !== '') $config[$key] = $value;
    }

    // Phase 1 deliberately implements only the casual policy. Keep the field so later
    // consent modes can be introduced without changing the persisted timeline format.
    $config['Policy'] = 'casual';
    $config['MaxEntries'] = max(2, intval($config['MaxEntries'] ?? 101));
    $config['MaxBytes'] = max(65536, intval($config['MaxBytes'] ?? 2097152));
    return $config;
}

function SimHistoryEmptyState(): array {
    return [
        'version' => SIM_HISTORY_FORMAT_VERSION,
        'policy' => 'casual',
        'entries' => [],
        'cursor' => -1,
        'pending' => null,
        'lastOperation' => 'init',
    ];
}

function SimHistoryReadState(): array {
    if (!SimHistoryCapabilityEnabled()) return SimHistoryEmptyState();
    $zone = &GetSimHistory();
    $stored = '';
    foreach ((array)$zone as $entry) {
        if (!is_object($entry) || !empty($entry->removed)) continue;
        $stored = strval($entry->Version ?? '');
        break;
    }
    if ($stored === '') return SimHistoryEmptyState();
    $raw = base64_decode($stored, true);
    if ($raw === false || $raw === '') return SimHistoryEmptyState();
    $decoded = json_decode($raw, true);
    if (!is_array($decoded) || intval($decoded['version'] ?? 0) !== SIM_HISTORY_FORMAT_VERSION) {
        return SimHistoryEmptyState();
    }
    if (!is_array($decoded['entries'] ?? null)) $decoded['entries'] = [];
    $decoded['cursor'] = intval($decoded['cursor'] ?? -1);
    if ($decoded['cursor'] >= count($decoded['entries'])) $decoded['cursor'] = count($decoded['entries']) - 1;
    if ($decoded['cursor'] < -1) $decoded['cursor'] = -1;
    if (!array_key_exists('pending', $decoded) || !is_array($decoded['pending'])) $decoded['pending'] = null;
    if (!isset($decoded['lastOperation'])) $decoded['lastOperation'] = 'action';
    $decoded['policy'] = 'casual';
    return $decoded;
}

function SimHistoryWriteState(array $state): void {
    if (!SimHistoryCapabilityEnabled()) return;
    $state['version'] = SIM_HISTORY_FORMAT_VERSION;
    $state['policy'] = 'casual';
    $encoded = json_encode($state, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($encoded === false) return;
    $zone = &GetSimHistory();
    $zone = [new SimHistory(base64_encode($encoded), 'SimHistory', 0, 0)];
}

function SimHistoryPayloadEncode(string $payload): string {
    $compressed = function_exists('gzdeflate') ? @gzdeflate($payload, 6) : false;
    if ($compressed === false) return '!' . base64_encode($payload);
    return '~' . base64_encode($compressed);
}

function SimHistoryPayloadDecode(string $field): ?string {
    if ($field === '') return null;
    $marker = $field[0];
    if ($marker === '~') {
        $compressed = base64_decode(substr($field, 1), true);
        if ($compressed === false || !function_exists('gzinflate')) return null;
        $payload = @gzinflate($compressed);
        return $payload === false ? null : $payload;
    }
    if ($marker === '!') {
        $payload = base64_decode(substr($field, 1), true);
        return $payload === false ? null : $payload;
    }
    return null;
}

function SimHistoryCapturePayload(): string {
    global $gRandomCounter, $gDecisionQueueVariables, $playerID;
    $savedPlayerID = $playerID ?? 1;
    $savedDecisionVariables = $gDecisionQueueVariables ?? '-';
    $decisionVariables = json_decode(strval($savedDecisionVariables), true);
    if (is_array($decisionVariables)) {
        foreach (array_keys($decisionVariables) as $key) {
            if (str_starts_with(strval($key), 'SIM_HISTORY_')) unset($decisionVariables[$key]);
        }
        $gDecisionQueueVariables = empty($decisionVariables)
            ? '-'
            : json_encode($decisionVariables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    // Relative my/their version schemas must always serialize from one canonical
    // perspective or a P2 checkpoint would restore both players into opposite seats.
    $playerID = 1;
    $payload = Versions::GetSerializedZones();
    $playerID = $savedPlayerID;
    $gDecisionQueueVariables = $savedDecisionVariables;
    // AzukiSim's Versions module predates a schema-owned RandomCounter zone, so
    // generated LoadVersion expects the deterministic counter as the final segment.
    $payload .= '<v0>' . intval($gRandomCounter ?? 0);
    return $payload;
}

function SimHistoryEntry(string $payload, int $actor, string $label): array {
    global $updateNumber;
    return [
        'actor' => $actor,
        'label' => trim($label) === '' ? 'Action' : trim($label),
        'update' => intval($updateNumber ?? 0),
        'snapshot' => SimHistoryPayloadEncode($payload),
        'hash' => hash('sha256', $payload),
    ];
}

function SimHistoryRestorePayload(string $payload): bool {
    if (!SimHistoryCapabilityEnabled() || !function_exists('GetVersions') || !function_exists('LoadVersion')) return false;
    $versions = &GetVersions(1);
    $temporaryIndex = count($versions);
    $versions[] = new Versions('0:' . $payload, 'Versions', 1, $temporaryIndex);
    LoadVersion(1, $temporaryIndex);
    $versions = &GetVersions(1);
    if (isset($versions[$temporaryIndex])) array_splice($versions, $temporaryIndex, 1);
    return true;
}

function SimHistoryRestoreEntry(array $entry): bool {
    $payload = SimHistoryPayloadDecode(strval($entry['snapshot'] ?? ''));
    if ($payload === null) return false;
    $expectedHash = strval($entry['hash'] ?? '');
    if ($expectedHash !== '' && !hash_equals($expectedHash, hash('sha256', $payload))) return false;
    return SimHistoryRestorePayload($payload);
}

function SimHistoryTrim(array &$state): void {
    $config = SimHistoryConfig();
    $maxEntries = intval($config['MaxEntries']);
    $maxBytes = intval($config['MaxBytes']);
    $serializedBytes = function() use (&$state): int {
        $encoded = json_encode($state, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return $encoded === false ? PHP_INT_MAX : strlen(base64_encode($encoded));
    };

    while (count($state['entries']) > 1
        && (count($state['entries']) > $maxEntries || $serializedBytes() > $maxBytes)) {
        array_shift($state['entries']);
        $state['cursor']--;
    }
    if ($state['cursor'] < 0 && !empty($state['entries'])) $state['cursor'] = 0;
}

function SimHistoryInitialize(string $label = 'Game start'): bool {
    if (!SimHistoryCapabilityEnabled()) return false;
    $state = SimHistoryEmptyState();
    $state['entries'][] = SimHistoryEntry(SimHistoryCapturePayload(), 0, $label);
    $state['cursor'] = 0;
    SimHistoryWriteState($state);
    SimHistorySyncClientVariables($state);
    return true;
}

function SimHistoryHasPendingAction(): bool {
    $state = SimHistoryReadState();
    return is_array($state['pending'] ?? null);
}

function SimHistoryBeginAction(int $actor, string $label = 'Action'): bool {
    if (!SimHistoryCapabilityEnabled()) return false;
    $state = SimHistoryReadState();
    if (is_array($state['pending'] ?? null)) return false;

    $prePayload = SimHistoryCapturePayload();
    if (empty($state['entries'])) {
        $state['entries'][] = SimHistoryEntry($prePayload, 0, 'Game start');
        $state['cursor'] = 0;
    }
    if ($state['cursor'] < count($state['entries']) - 1) {
        $state['entries'] = array_slice($state['entries'], 0, $state['cursor'] + 1);
    }
    $state['pending'] = [
        'actor' => $actor,
        'label' => trim($label) === '' ? 'Action' : trim($label),
        'snapshot' => SimHistoryPayloadEncode($prePayload),
        'hash' => hash('sha256', $prePayload),
    ];
    $state['lastOperation'] = 'action';
    SimHistoryTrim($state);
    SimHistoryWriteState($state);
    SimHistorySyncClientVariables($state);
    return true;
}

function SimHistoryIsStableBoundary(): bool {
    if (function_exists('GameSimHistoryIsStable')) return (bool)GameSimHistoryIsStable();
    if (class_exists('DecisionQueueController')) {
        $controller = new DecisionQueueController();
        if (!$controller->AllQueuesEmpty()) return false;
    }
    if (function_exists('GetEffectStack')) {
        foreach ((array)GetEffectStack() as $entry) {
            if (is_object($entry) && empty($entry->removed)) return false;
        }
    }
    return true;
}

function SimHistoryCommitPending(bool $force = false): bool {
    if (!SimHistoryCapabilityEnabled()) return false;
    $state = SimHistoryReadState();
    $pending = $state['pending'] ?? null;
    if (!is_array($pending) || (!$force && !SimHistoryIsStableBoundary())) return false;

    $currentPayload = SimHistoryCapturePayload();
    $prePayload = SimHistoryPayloadDecode(strval($pending['snapshot'] ?? ''));
    $state['pending'] = null;
    if ($prePayload !== null && hash_equals(hash('sha256', $prePayload), hash('sha256', $currentPayload))) {
        SimHistoryWriteState($state);
        SimHistorySyncClientVariables($state);
        return false;
    }

    $state['entries'][] = SimHistoryEntry(
        $currentPayload,
        intval($pending['actor'] ?? 0),
        strval($pending['label'] ?? 'Action')
    );
    $state['cursor'] = count($state['entries']) - 1;
    SimHistoryTrim($state);
    SimHistoryWriteState($state);
    SimHistorySyncClientVariables($state);
    return true;
}

function SimHistoryCanUndo(?array $state = null): bool {
    if (!SimHistoryCapabilityEnabled()) return false;
    $state = $state ?? SimHistoryReadState();
    return is_array($state['pending'] ?? null) || intval($state['cursor'] ?? -1) > 0;
}

function SimHistoryCanRedo(?array $state = null): bool {
    if (!SimHistoryCapabilityEnabled()) return false;
    $state = $state ?? SimHistoryReadState();
    return !is_array($state['pending'] ?? null)
        && intval($state['cursor'] ?? -1) >= 0
        && intval($state['cursor']) < count($state['entries']) - 1;
}

function SimHistoryUndo(int $requester): bool {
    if (!SimHistoryCapabilityEnabled()) return false;
    $state = SimHistoryReadState();
    $pending = $state['pending'] ?? null;
    if (is_array($pending)) {
        if (!SimHistoryRestoreEntry($pending)) return false;
        $label = strval($pending['label'] ?? 'action');
        $state['pending'] = null;
        $state['lastOperation'] = 'undo';
        SimHistoryWriteState($state);
        SimHistorySyncClientVariables($state);
        if (function_exists('SetFlashMessage')) SetFlashMessage('Cancelled: ' . $label . '.');
        return true;
    }
    $cursor = intval($state['cursor'] ?? -1);
    if ($cursor <= 0 || !isset($state['entries'][$cursor - 1])) return false;
    $undoneLabel = strval($state['entries'][$cursor]['label'] ?? 'action');
    if (!SimHistoryRestoreEntry($state['entries'][$cursor - 1])) return false;
    $state['cursor'] = $cursor - 1;
    $state['lastOperation'] = 'undo';
    SimHistoryWriteState($state);
    SimHistorySyncClientVariables($state);
    if (function_exists('SetFlashMessage')) SetFlashMessage('Undid: ' . $undoneLabel . '.');
    return true;
}

function SimHistoryRedo(int $requester): bool {
    if (!SimHistoryCapabilityEnabled()) return false;
    $state = SimHistoryReadState();
    $cursor = intval($state['cursor'] ?? -1);
    if (is_array($state['pending'] ?? null) || $cursor < 0 || !isset($state['entries'][$cursor + 1])) return false;
    $entry = $state['entries'][$cursor + 1];
    if (!SimHistoryRestoreEntry($entry)) return false;
    $state['cursor'] = $cursor + 1;
    $state['lastOperation'] = 'redo';
    SimHistoryWriteState($state);
    SimHistorySyncClientVariables($state);
    if (function_exists('SetFlashMessage')) SetFlashMessage('Redid: ' . strval($entry['label'] ?? 'action') . '.');
    return true;
}

function SimHistoryUndoLabel(array $state): string {
    if (is_array($state['pending'] ?? null)) return 'Cancel ' . strval($state['pending']['label'] ?? 'action');
    $cursor = intval($state['cursor'] ?? -1);
    return $cursor > 0 ? strval($state['entries'][$cursor]['label'] ?? 'action') : '';
}

function SimHistoryRedoLabel(array $state): string {
    $cursor = intval($state['cursor'] ?? -1);
    return isset($state['entries'][$cursor + 1]) ? strval($state['entries'][$cursor + 1]['label'] ?? 'action') : '';
}

function SimHistoryUndoToken(array $state): string {
    $pending = $state['pending'] ?? null;
    if (is_array($pending)) {
        return implode('|', [
            'pending',
            intval($pending['actor'] ?? 0),
            strval($pending['label'] ?? ''),
            strval($pending['hash'] ?? ''),
        ]);
    }
    $cursor = intval($state['cursor'] ?? -1);
    $entry = $state['entries'][$cursor] ?? null;
    if (!is_array($entry) || $cursor <= 0) return '';
    return implode('|', [
        'entry',
        $cursor,
        intval($entry['update'] ?? 0),
        strval($entry['hash'] ?? ''),
    ]);
}

function SimHistorySyncClientVariables(?array $state = null): void {
    if (!class_exists('DecisionQueueController')) return;
    $state = $state ?? SimHistoryReadState();
    DecisionQueueController::StoreVariable('SIM_HISTORY_POLICY', 'casual');
    DecisionQueueController::StoreVariable('SIM_HISTORY_CAN_UNDO', SimHistoryCanUndo($state) ? 'true' : 'false');
    DecisionQueueController::StoreVariable('SIM_HISTORY_CAN_REDO', SimHistoryCanRedo($state) ? 'true' : 'false');
    DecisionQueueController::StoreVariable('SIM_HISTORY_UNDO_LABEL', SimHistoryUndoLabel($state));
    DecisionQueueController::StoreVariable('SIM_HISTORY_REDO_LABEL', SimHistoryRedoLabel($state));
    DecisionQueueController::StoreVariable('SIM_HISTORY_UNDO_TOKEN', SimHistoryUndoToken($state));
    DecisionQueueController::StoreVariable('SIM_HISTORY_LAST_OPERATION', strval($state['lastOperation'] ?? 'action'));
}

?>
