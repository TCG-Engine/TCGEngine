<?php
/** File-backed status for one active/recent simulation per saved deck. */
declare(strict_types=1);

function swuSimulationJobFile(string $owner, string $deckID): string {
    $directory = sys_get_temp_dir() . '/swudeck-simulation-jobs';
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Could not create the simulation job directory.');
    }
    return $directory . '/' . hash('sha256', $owner . "\0" . $deckID) . '.json';
}

function swuSimulationReadJob(string $path): ?array {
    if (!is_file($path)) return null;
    $handle = fopen($path, 'rb');
    if (!$handle) return null;
    try {
        if (!flock($handle, LOCK_SH)) return null;
        $data = json_decode((string)stream_get_contents($handle), true);
        flock($handle, LOCK_UN);
    } finally {
        fclose($handle);
    }
    if (!is_array($data)) return null;
    if (($data['status'] ?? '') !== 'running' && time() - (int)($data['finishedAt'] ?? 0) > 86400) return null;
    if (($data['status'] ?? '') === 'running' && time() - (int)($data['startedAt'] ?? 0) > 900) {
        return ['status' => 'failed', 'error' => 'This run stopped before it finished. Please start another simulation.'];
    }
    return $data;
}

function swuSimulationWriteJob(string $path, array $data): void {
    $handle = fopen($path, 'c+b');
    if (!$handle) throw new RuntimeException('Could not save the simulation status.');
    try {
        if (!flock($handle, LOCK_EX)) throw new RuntimeException('Could not lock the simulation status.');
        ftruncate($handle, 0);
        rewind($handle);
        $json = json_encode($data, JSON_THROW_ON_ERROR);
        if (fwrite($handle, $json) !== strlen($json)) throw new RuntimeException('Could not save the simulation status.');
        fflush($handle);
        flock($handle, LOCK_UN);
    } finally {
        fclose($handle);
    }
    @chmod($path, 0600);
}
