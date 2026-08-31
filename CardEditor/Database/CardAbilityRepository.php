<?php

include_once __DIR__ . '/../../Database/ConnectionManager.php';
include_once __DIR__ . '/CardAbilityDB.php';
include_once __DIR__ . '/CardCodeConnectionConfig.php';

/**
 * CARD_CODE_REMOTE_CONFIG is a JSON object keyed by local root name:
 * {"AzukiSim":{"url":"https://host/TCGEngine/CardEditor/API/CardCodeService.php",
 *               "workspace":"AzukiSim","tokenEnv":"AZUKI_CARD_CODE_TOKEN"}}
 * This legacy environment format remains supported. The Generator Workspace writes a protected,
 * git-ignored local connection file which takes precedence when present.
 */
function CardCodeRemoteConfigs(): array
{
    $raw = trim((string)getenv('CARD_CODE_REMOTE_CONFIG'));
    $environment = $raw === '' ? [] : json_decode($raw, true);
    if (!is_array($environment)) throw new RuntimeException('CARD_CODE_REMOTE_CONFIG must be a valid JSON object');
    return array_replace($environment, CardCodeLoadLocalConnections());
}

function CardCodeConfiguredRemoteRoots(): array
{
    return array_keys(CardCodeRemoteConfigs());
}

function CardCodeRemoteConfigForRoot(string $rootName): ?array
{
    $config = CardCodeRemoteConfigs()[$rootName] ?? null;
    if (!is_array($config)) return null;
    $url = rtrim(trim((string)($config['url'] ?? '')), '/');
    $workspace = trim((string)($config['workspace'] ?? $rootName));
    $tokenEnv = trim((string)($config['tokenEnv'] ?? 'CARD_CODE_REMOTE_TOKEN'));
    $token = trim((string)($config['token'] ?? getenv($tokenEnv)));
    if ($url === '' || $workspace === '' || $token === '') {
        throw new RuntimeException("Remote Card Code backend for $rootName is missing url, workspace, or $tokenEnv");
    }
    if (!preg_match('#^https://#i', $url) && !preg_match('#^http://(localhost|127\.0\.0\.1|\[::1\])(?::\d+)?/#i', $url)) {
        throw new RuntimeException("Remote Card Code URL for $rootName must use HTTPS (HTTP is allowed only for loopback)");
    }
    return ['url' => $url, 'workspace' => $workspace, 'token' => $token];
}

function OpenCardAbilityRepository(string $rootName)
{
    $remote = CardCodeRemoteConfigForRoot($rootName);
    if ($remote) return new RemoteCardAbilityRepository($rootName, $remote);
    $conn = GetLocalMySQLConnection();
    if (!$conn) throw new RuntimeException('Could not connect to the local card ability database');
    return new LocalCardAbilityRepository($conn);
}

final class LocalCardAbilityRepository extends CardAbilityDB
{
    private $ownedConnection;
    public function __construct($conn) { $this->ownedConnection = $conn; parent::__construct($conn); }
    public function close(): void { if ($this->ownedConnection) { mysqli_close($this->ownedConnection); $this->ownedConnection = null; } }
    public function revisionForCard($rootName, $cardId): string
    {
        include_once __DIR__ . '/CardCodeServiceDB.php';
        $rows = $this->loadCardAbilities($rootName, $cardId);
        foreach ($rows as &$row) $row['card_id'] = $cardId;
        return CardCodeServiceDB::RevisionForRows($rows);
    }

    public function replaceCardAbilities($rootName, $cardId, array $abilities, bool $cardImplemented, ?string $baseRevision): array
    {
        mysqli_begin_transaction($this->ownedConnection);
        try {
            $current = $this->loadCardAbilities($rootName, $cardId);
            $currentRevision = $this->revisionForCard($rootName, $cardId);
            if ($baseRevision !== null && $baseRevision !== '' && !hash_equals($currentRevision, $baseRevision)) {
                mysqli_rollback($this->ownedConnection);
                throw new CardCodeConflictException('Card abilities changed since they were loaded', ['revision' => $currentRevision, 'abilities' => $current]);
            }
            foreach ($current as $row) $this->deleteAbility((int)$row['id'], $rootName, $cardId);
            foreach ($abilities as $ability) {
                $macro = trim((string)($ability['macroName'] ?? ''));
                $code = (string)($ability['abilityCode'] ?? '');
                if ($macro === '' || $code === '') throw new InvalidArgumentException('Every ability requires a macro and code');
                $type = ($ability['abilityType'] ?? 'macro') === 'listener' ? 'listener' : 'macro';
                $zones = $ability['listenerZones'] ?? null;
                if ($type === 'listener') {
                    $zones = is_array($zones) ? implode(',', array_filter(array_map('trim', $zones))) : trim((string)$zones);
                    if ($zones === '') throw new InvalidArgumentException('Listener abilities require at least one active zone');
                } else $zones = null;
                if (!$this->saveAbility(null, $rootName, $cardId, $macro, $code, $ability['prereqCode'] ?? null, $ability['abilityName'] ?? null, !empty($ability['isImplemented']) ? 1 : 0, $type, $zones)) {
                    throw new RuntimeException('Could not save ability');
                }
            }
            if ($cardImplemented && count($abilities) === 0 && !$this->saveAbility(null, $rootName, $cardId, '', '', null, '[Card Implemented]', 1)) {
                throw new RuntimeException('Could not save implementation marker');
            }
            mysqli_commit($this->ownedConnection);
            $saved = $this->loadCardAbilities($rootName, $cardId);
            return ['abilities' => $saved, 'revision' => $this->revisionForCard($rootName, $cardId)];
        } catch (Throwable $error) {
            mysqli_rollback($this->ownedConnection);
            throw $error;
        }
    }

    public function ensureCards($rootName, array $cardIds): int
    {
        $inserted = 0;
        foreach ($cardIds as $cardId) {
            if (!$this->cardHasAbilities($rootName, $cardId) && $this->saveAbility(null, $rootName, $cardId, '', '', null)) ++$inserted;
        }
        return $inserted;
    }

}

final class RemoteCardAbilityRepository
{
    private string $localRoot;
    private array $config;
    private ?array $snapshot = null;

    public function __construct(string $localRoot, array $config) { $this->localRoot = $localRoot; $this->config = $config; }
    public function close(): void {}
    public function isRemote(): bool { return true; }

    private function remoteRoot(string $rootName): string
    {
        return $rootName === $this->localRoot ? (string)$this->config['workspace'] : $rootName;
    }

    private function request(string $action, string $method = 'GET', array $parameters = []): array
    {
        $url = $this->config['url'] . '?action=' . rawurlencode($action);
        $headers = ['Authorization: Bearer ' . $this->config['token'], 'Accept: application/json'];
        if ($method === 'GET') {
            $url .= '&' . http_build_query($parameters);
            $body = null;
        } else {
            $headers[] = 'Content-Type: application/json';
            $body = json_encode($parameters, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        $curl = curl_init($url);
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $headers, CURLOPT_TIMEOUT => 30, CURLOPT_CONNECTTIMEOUT => 10]);
        if ($body !== null) curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        $raw = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        if ($raw === false) throw new RuntimeException('Remote Card Code request failed: ' . $error);
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) throw new RuntimeException('Remote Card Code service returned invalid JSON');
        if ($status < 200 || $status >= 300 || empty($decoded['success'])) {
            $exception = new RuntimeException((string)($decoded['error'] ?? "Remote Card Code service returned HTTP $status"), $status);
            if ($status === 409) $exception = new CardCodeConflictException($exception->getMessage(), $decoded['conflict'] ?? []);
            throw $exception;
        }
        return $decoded;
    }

    private function snapshot(string $rootName, bool $refresh = false): array
    {
        if ($this->snapshot === null || $refresh) $this->snapshot = $this->request('snapshot', 'GET', ['root' => $this->remoteRoot($rootName)]);
        return $this->snapshot;
    }

    public function loadCardAbilities($rootName, $cardId): array
    {
        return $this->request('card', 'GET', ['root' => $this->remoteRoot($rootName), 'card' => $cardId])['abilities'] ?? [];
    }

    public function loadCardWithRevision($rootName, $cardId): array
    {
        return $this->request('card', 'GET', ['root' => $this->remoteRoot($rootName), 'card' => $cardId]);
    }

    public function revisionForCard($rootName, $cardId): string
    {
        return (string)$this->loadCardWithRevision($rootName, $cardId)['revision'];
    }

    public function replaceCardAbilities($rootName, $cardId, array $abilities, bool $cardImplemented, ?string $baseRevision): array
    {
        $result = $this->request('save', 'POST', ['root' => $this->remoteRoot($rootName), 'cardId' => $cardId, 'abilities' => $abilities, 'cardImplemented' => $cardImplemented, 'baseRevision' => $baseRevision]);
        $this->snapshot = null;
        return $result;
    }

    public function getAbilitiesByMacro($rootName, $macroName): array
    {
        return array_values(array_filter($this->snapshot($rootName)['abilities'] ?? [], fn($row) => ($row['ability_type'] ?? 'macro') === 'macro' && ($row['macro_name'] ?? '') === $macroName));
    }

    public function getListenerAbilities($rootName): array
    {
        return array_values(array_filter($this->snapshot($rootName)['abilities'] ?? [], fn($row) => ($row['ability_type'] ?? 'macro') === 'listener'));
    }

    public function cardHasAbilities($rootName, $cardId): bool
    {
        foreach ($this->snapshot($rootName)['abilities'] ?? [] as $row) if (($row['card_id'] ?? '') === $cardId) return true;
        return false;
    }

    public function ensureCards($rootName, array $cardIds): int
    {
        $result = $this->request('ensure-cards', 'POST', ['root' => $this->remoteRoot($rootName), 'cardIds' => array_values($cardIds)]);
        $this->snapshot = null;
        return (int)($result['insertedCount'] ?? 0);
    }

    public function listCards($rootName): array
    {
        return $this->request('cards', 'GET', ['root' => $this->remoteRoot($rootName)])['cards'] ?? [];
    }

    public function saveAbility($id, $rootName, $cardId, $macroName, $abilityCode, $prereqCode = null, $abilityName = null, $isImplemented = 0, $abilityType = 'macro', $listenerZones = null)
    {
        $loaded = $this->loadCardWithRevision($rootName, $cardId);
        $abilities = [];
        $updated = false;
        foreach ($loaded['abilities'] ?? [] as $row) {
            if ($id !== null && (int)$row['id'] === (int)$id) {
                $row = ['macroName' => $macroName, 'abilityCode' => $abilityCode, 'prereqCode' => $prereqCode, 'abilityName' => $abilityName, 'isImplemented' => (bool)$isImplemented, 'abilityType' => $abilityType, 'listenerZones' => $listenerZones];
                $updated = true;
            } else {
                $row = ['macroName' => $row['macro_name'], 'abilityCode' => $row['ability_code'], 'prereqCode' => $row['prereq_code'], 'abilityName' => $row['ability_name'], 'isImplemented' => (bool)$row['is_implemented'], 'abilityType' => $row['ability_type'], 'listenerZones' => $row['listener_zones']];
            }
            $abilities[] = $row;
        }
        if (!$updated) $abilities[] = ['macroName' => $macroName, 'abilityCode' => $abilityCode, 'prereqCode' => $prereqCode, 'abilityName' => $abilityName, 'isImplemented' => (bool)$isImplemented, 'abilityType' => $abilityType, 'listenerZones' => $listenerZones];
        $result = $this->replaceCardAbilities($rootName, $cardId, $abilities, false, $loaded['revision'] ?? null);
        $saved = end($result['abilities']);
        return $saved['id'] ?? true;
    }
}

final class CardCodeConflictException extends RuntimeException
{
    public array $conflict;
    public function __construct(string $message, array $conflict) { parent::__construct($message, 409); $this->conflict = $conflict; }
}
