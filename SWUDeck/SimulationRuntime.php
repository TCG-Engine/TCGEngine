<?php
/** Local SWUSim process runner and replay store for saved SWUDeck lists. */
declare(strict_types=1);

function swuSimulationConfig(): array {
    $path = __DIR__ . '/SimulationConfig.local.php';
    $config = is_file($path) ? require $path : [];
    return is_array($config) ? $config : [];
}

// The fixture directories the simulator offers as opponents, in order. ONE list, because the two functions
// below must agree: swuSimulationOpponents() advertises the ids and swuSimulationOpponentPath() resolves
// them, and a disagreement between them makes an advertised opponent un-pickable. Guarded by
// SWUSim/DevTools/tests/simulation_opponents_test.php ("every offered opponent RESOLVES").
// meta-2026-09-field/ was dropped 2026-09-29 — its 61 unreviewed-style decks existed only for this list, and
// their archetype data now lives in SWUSim/docs/premier-meta-2026-09-archetypes.md. Adding force-fam/ (the
// creator decks) is a one-line change here, once they should be pickable.
const SWU_SIMULATION_OPPONENT_GROUPS = ['meta-2026-09'];

function swuSimulationOpponents(): array {
    $result = [];
    foreach (SWU_SIMULATION_OPPONENT_GROUPS as $group) {
        foreach (glob(__DIR__ . '/../SWUSim/Tests/BotFixtures/' . $group . '/*.txt') ?: [] as $path) {
            $id = pathinfo($path, PATHINFO_FILENAME);
            if (isset($result[$id])) continue;
            $file = fopen($path, 'rb');
            $first = $file ? trim((string)fgets($file)) : '';
            if ($file) fclose($file);
            $label = ltrim($first, '# ') ?: $id;
            $label = preg_replace('/^Real-deck self-play fixture\s*[—–-]\s*/u', '', $label);
            $result[$id] = ['id' => $id, 'label' => $label, 'group' => $group];
        }
    }
    return array_values($result);
}

function swuSimulationOpponentPath(string $id): string {
    // ⚠ The HYPHEN is required, not cosmetic: fixture filenames became
    // <leader-title>_<set>_<base-archetype> on 2026-09-29 ("director-krennic_law_blue-splash"), and this
    // class previously omitted '-', which rejected 82 of the 83 opponents this very file lists — the whole
    // feature 500'd on every pick. Still no '.' and no '/', so an id cannot escape the fixture directory.
    if (!preg_match('/^[a-z0-9_-]+$/', $id)) throw new InvalidArgumentException('Choose an available opponent.');
    foreach (SWU_SIMULATION_OPPONENT_GROUPS as $group) {
        $path = __DIR__ . '/../SWUSim/Tests/BotFixtures/' . $group . '/' . $id . '.txt';
        if (is_file($path)) return $path;
    }
    throw new InvalidArgumentException('Choose an available opponent.');
}

function swuSimulationDeckText(array $deck): string {
    $leader = (string)($deck['leader']['id'] ?? '');
    $base = (string)($deck['base']['id'] ?? '');
    $valid = fn(string $id): bool => preg_match('/^[A-Z0-9]{2,5}_(?:T[0-9]{2}|[0-9]{2,3})$/', $id) === 1;
    if (!$valid($leader) || !$valid($base)) throw new InvalidArgumentException('The saved leader or base has an unmapped card ID.');
    $cards = [];
    foreach ($deck['deck'] ?? [] as $row) {
        if (!is_array($row)) throw new InvalidArgumentException('The saved deck contains an invalid card row.');
        $id = (string)($row['id'] ?? '');
        $count = $row['count'] ?? null;
        if (!$valid($id) || !is_int($count) || $count < 1 || $count > 99) {
            throw new InvalidArgumentException('The saved deck contains an invalid card ID or count.');
        }
        $cards[$id] = ($cards[$id] ?? 0) + $count;
    }
    $minimum = $base === 'JTL_024' ? 60 : ($base === 'JTL_025' ? 45 : 50);
    if (array_sum($cards) < $minimum) throw new InvalidArgumentException('This deck needs at least ' . $minimum . ' main-deck cards.');
    ksort($cards);
    $lines = ['Leader', '1 ' . $leader, '', 'Base', '1 ' . $base, '', 'Deck'];
    foreach ($cards as $id => $count) $lines[] = $count . ' ' . $id;
    return implode("\n", $lines) . "\n";
}

function swuSimulationReplayDir(): string {
    return sys_get_temp_dir() . '/swudeck-simulation-replays';
}

function swuSimulationStoreReplay(array $replay, string $owner, string $deckID): string {
    if (($replay['format'] ?? '') !== 'tcgengine-match-replay-v1' || ($replay['rootName'] ?? '') !== 'SWUSim') {
        throw new RuntimeException('SWUSim returned an invalid replay.');
    }
    $directory = swuSimulationReplayDir();
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Could not save the simulation replay.');
    }
    foreach (glob($directory . '/*.json') ?: [] as $old) {
        if (time() - (int)filemtime($old) > 86400) @unlink($old);
    }
    $token = bin2hex(random_bytes(20));
    $path = $directory . '/' . $token . '.json';
    $data = json_encode(['owner' => $owner, 'deckID' => $deckID, 'replay' => $replay], JSON_THROW_ON_ERROR);
    if (file_put_contents($path, $data, LOCK_EX) === false) throw new RuntimeException('Could not save the simulation replay.');
    @chmod($path, 0600);
    return $token;
}

function swuSimulationRunLocal(array $deck, string $opponent, string $seed, int $samples, string $owner, string $deckID, bool $inlineReplays = false): array {
    if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $seed) || $samples < 1 || $samples > 20) {
        throw new InvalidArgumentException('Choose a seed and 1–20 games.');
    }
    $opponentPath = swuSimulationOpponentPath($opponent);
    $text = swuSimulationDeckText($deck);
    $config = swuSimulationConfig();
    $phpName = 'php' . (PHP_OS_FAMILY === 'Windows' ? '.exe' : '');
    $defaultPhp = dirname(PHP_BINARY) . '/' . $phpName;
    if (!is_file($defaultPhp)) $defaultPhp = PHP_BINDIR . '/' . $phpName;
    $php = (string)($config['php_bin'] ?? $defaultPhp);
    $runner = __DIR__ . '/../SWUSim/DevTools/DeckSimulationRunner.php';
    if (!is_file($php) || !is_file($runner) || !function_exists('proc_open')) {
        throw new RuntimeException('The local SWUSim CLI runner is unavailable. Configure php_bin if needed.');
    }
    $temporary = sys_get_temp_dir() . '/swudeck-simulation-' . bin2hex(random_bytes(8));
    if (!mkdir($temporary, 0700)) throw new RuntimeException('Could not create simulation workspace.');
    $deckPath = $temporary . '/deck.txt';
    $jobs = [];
    $results = [];
    try {
        if (file_put_contents($deckPath, $text) === false) throw new RuntimeException('Could not export this deck.');
        for ($offset = 0; $offset < $samples; $offset += 4) {
          $batchEnd = min($offset + 4, $samples);
          for ($index = $offset; $index < $batchEnd; ++$index) {
            $sampleSeed = $seed . '-' . str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT);
            $stdout = $temporary . '/out-' . $index . '.json';
            $stderr = $temporary . '/err-' . $index . '.txt';
            $command = [$php, '-d', 'apc.enable_cli=1', '-d', 'xdebug.mode=off', $runner,
                '--deck=' . $opponentPath, '--deck2=' . $deckPath, '--seed=' . $sampleSeed,
                '--mode=full_game', '--memory-only'];
            $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['file', $stdout, 'w'], 2 => ['file', $stderr, 'w']], $pipes, dirname(__DIR__));
            if (!is_resource($process)) throw new RuntimeException('Could not start a SWUSim game.');
            fclose($pipes[0]);
            $jobs[] = ['process' => $process, 'seed' => $sampleSeed, 'stdout' => $stdout, 'stderr' => $stderr, 'started' => microtime(true), 'finished' => false];
          }
          do {
            $pending = false;
            for ($position = $offset; $position < $batchEnd; ++$position) {
                $job = &$jobs[$position];
                if ($job['finished']) { unset($job); continue; }
                $status = proc_get_status($job['process']);
                if (!$status['running']) {
                    $job['finished'] = true;
                } elseif (microtime(true) - $job['started'] > 120) {
                    proc_terminate($job['process']);
                    $job['finished'] = true;
                    $job['timeout'] = true;
                } else {
                    $pending = true;
                }
                unset($job);
            }
            if ($pending) usleep(100000);
          } while ($pending);
          for ($position = $offset; $position < $batchEnd; ++$position) {
            $job = $jobs[$position];
            if (!empty($job['timeout'])) {
                $sample = ['status' => 'timeout', 'seed' => $job['seed'], 'engineError' => 'SWUSim timed out after 120 seconds.'];
            } else {
                $sample = json_decode((string)@file_get_contents($job['stdout']), true);
                if (!is_array($sample)) $sample = ['status' => 'engine_error', 'seed' => $job['seed'],
                    'engineError' => 'SWUSim returned no JSON result: ' . substr((string)@file_get_contents($job['stderr']), -400)];
            }
            $replay = $sample['replay'] ?? null;
            unset($sample['replay']);
            if (($sample['status'] ?? '') === 'completed' && is_array($replay)) {
                if ($inlineReplays) $sample['replay'] = $replay;
                else $sample['replayId'] = swuSimulationStoreReplay($replay, $owner, $deckID);
            }
            $results[] = $sample;
          }
        }
    } finally {
        foreach ($jobs as $job) if (is_resource($job['process'])) proc_close($job['process']);
        foreach (glob($temporary . '/*') ?: [] as $path) @unlink($path);
        @rmdir($temporary);
    }
    $counts = ['ours' => 0, 'opponent' => 0, 'incomplete' => 0];
    foreach ($results as $sample) {
        if (($sample['status'] ?? '') !== 'completed') ++$counts['incomplete'];
        elseif ((int)($sample['winner'] ?? 0) === 2) ++$counts['ours'];
        elseif ((int)($sample['winner'] ?? 0) === 1) ++$counts['opponent'];
        else ++$counts['incomplete'];
    }
    $completed = $counts['ours'] + $counts['opponent'];
    return ['deckHash' => hash('sha256', $text), 'samples' => $results,
        'aggregate' => ['counts' => $counts, 'ourWinRate' => $completed ? $counts['ours'] / $completed : null]];
}

function swuSimulationRun(array $deck, string $opponent, string $seed, int $samples, string $owner, string $deckID): array {
    $config = swuSimulationConfig();
    $url = (string)($config['simulation_url'] ?? 'https://soulmastersdb.net/TCGEngine/APIs/SWUSimulation.php');
    $parsed = parse_url($url);
    if (!is_array($parsed) || ($parsed['scheme'] ?? '') !== 'https' || empty($parsed['host']) || !empty($parsed['user']) || !empty($parsed['pass'])) {
        throw new RuntimeException('Configure an HTTPS simulation_url for the bot server.');
    }
    $secret = (string)($config['simulation_secret'] ?? getenv('SWU_SIMULATION_API_SECRET'));
    if (strlen($secret) < 32) throw new RuntimeException('The remote simulation secret is not configured.');
    if (!function_exists('curl_init')) throw new RuntimeException('PHP cURL is required for remote simulations.');
    // The deck is captured before the request; edits made while the bots play do not change this run.
    swuSimulationDeckText($deck);
    swuSimulationOpponentPath($opponent);
    $body = json_encode(['deck' => $deck, 'opponent' => $opponent, 'seed' => $seed, 'samples' => $samples], JSON_THROW_ON_ERROR);
    $timestamp = (string)time();
    $signature = hash_hmac('sha256', $timestamp . "\n" . $body, $secret);
    $handle = curl_init($url);
    $response = '';
    $limit = 64 * 1024 * 1024;
    curl_setopt_array($handle, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json',
            'X-SWU-Sim-Timestamp: ' . $timestamp, 'X-SWU-Sim-Signature: ' . $signature],
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 60 + 125 * (int)ceil($samples / 4),
        CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use (&$response, $limit): int {
            if (strlen($response) + strlen($chunk) > $limit) return 0;
            $response .= $chunk;
            return strlen($chunk);
        },
    ]);
    $success = curl_exec($handle);
    $status = (int)curl_getinfo($handle, CURLINFO_HTTP_CODE);
    $curlError = curl_error($handle);
    curl_close($handle);
    if ($success === false) throw new RuntimeException('The bot server did not finish the simulation: ' . $curlError);
    $payload = json_decode($response, true);
    if (!is_array($payload) || $status < 200 || $status >= 300 || empty($payload['success']) || !is_array($payload['result'] ?? null)) {
        throw new RuntimeException((string)($payload['message'] ?? 'The bot server returned an invalid simulation response.'));
    }
    $result = $payload['result'];
    if (!is_array($result['samples'] ?? null) || count($result['samples']) !== $samples || !is_array($result['aggregate'] ?? null)) {
        throw new RuntimeException('The bot server returned incomplete results.');
    }
    foreach ($result['samples'] as &$sample) {
        if (!is_array($sample)) throw new RuntimeException('The bot server returned an invalid game result.');
        $replay = $sample['replay'] ?? null;
        unset($sample['replay']);
        if (($sample['status'] ?? '') === 'completed' && is_array($replay)) {
            $sample['replayId'] = swuSimulationStoreReplay($replay, $owner, $deckID);
        }
    }
    unset($sample);
    return $result;
}

function swuSimulationImportReplay(string $token, string $owner, string $deckID): string {
    if (!preg_match('/^[a-f0-9]{40}$/', $token)) throw new InvalidArgumentException('Invalid replay.');
    $path = swuSimulationReplayDir() . '/' . $token . '.json';
    if (!is_file($path) || time() - (int)filemtime($path) > 86400) {
        throw new InvalidArgumentException('This replay expired. Run the simulation again.');
    }
    $stored = json_decode((string)file_get_contents($path), true);
    if (!is_array($stored) || !hash_equals((string)($stored['owner'] ?? ''), $owner)
        || (string)($stored['deckID'] ?? '') !== $deckID || !is_array($stored['replay'] ?? null)) {
        throw new InvalidArgumentException('This replay is unavailable.');
    }
    $config = swuSimulationConfig();
    $base = rtrim((string)($config['replay_base'] ?? 'https://petranaki.net/TCGEngine'), '/');
    $parsed = parse_url($base);
    if (!is_array($parsed) || ($parsed['scheme'] ?? '') !== 'https' || empty($parsed['host'])) {
        throw new RuntimeException('Configure an HTTPS replay_base URL.');
    }
    if (!function_exists('curl_init')) throw new RuntimeException('PHP cURL is required for replay import.');
    $handle = curl_init($base . '/APIs/MatchReplay.php?action=import');
    curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['replay' => $stored['replay']], JSON_THROW_ON_ERROR),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_TIMEOUT => 30, CURLOPT_FOLLOWLOCATION => false]);
    $body = curl_exec($handle);
    $status = (int)curl_getinfo($handle, CURLINFO_HTTP_CODE);
    $error = curl_error($handle);
    curl_close($handle);
    if ($body === false) throw new RuntimeException('Replay host is unavailable: ' . $error);
    $result = json_decode($body, true);
    if (!is_array($result) || $status < 200 || $status >= 300 || empty($result['success'])) {
        throw new RuntimeException((string)($result['message'] ?? 'Petranaki could not import this replay.'));
    }
    $gameName = (string)($result['gameName'] ?? '');
    $authKey = (string)($result['authKey'] ?? '');
    if (!ctype_digit($gameName) || !preg_match('/^[a-fA-F0-9]{32}$/', $authKey)) {
        throw new RuntimeException('Petranaki returned an invalid replay link.');
    }
    return $base . '/NextTurn.php?' . http_build_query(['gameName' => $gameName, 'playerID' => 1,
        'folderPath' => 'SWUSim', 'authKey' => $authKey, 'replay' => 1]);
}
