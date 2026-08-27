<?php
/**
 * Export the non-sensitive implementation inventory used by semantic fixture coverage.
 *
 * The exporter intentionally excludes ability_code, prereq_code, and credentials. It
 * records only card IDs and aggregate ability metadata, enough for CI to determine
 * whether every implemented card has a rules-derived regression contract.
 *
 * Run in the GrandArchiveSim PHP/Docker environment:
 *   php DevTools/export-ga-implemented-abilities.php \
 *     --output=Tests/Integration/GrandArchiveSim/implemented-cards.json
 */
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

$repoRoot = dirname(__DIR__);
$output = $repoRoot . '/Tests/Integration/GrandArchiveSim/implemented-cards.json';
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--output=')) $output = substr($arg, 9);
}

// DatabaseResolution deliberately refuses to infer a root from a DevTools
// script. Set the root explicitly so this exporter cannot silently query a
// different simulator database.
$GLOBALS['rootName'] = 'GrandArchiveSim';
require_once $repoRoot . '/Database/ConnectionManager.php';

try {
    $conn = GetLocalMySQLConnection();
    if (!$conn) throw new RuntimeException('Could not connect to GrandArchiveSim MySQL.');
    $sql = "SELECT card_id,
                   COUNT(*) AS ability_count,
                   SUM(CASE WHEN ability_type = 'listener' THEN 1 ELSE 0 END) AS listener_count
            FROM card_abilities
            WHERE root_name = 'GrandArchiveSim' AND is_implemented = 1
            GROUP BY card_id
            ORDER BY card_id";
    $result = mysqli_query($conn, $sql);
    if (!$result) throw new RuntimeException('Could not read card_abilities: ' . mysqli_error($conn));

    $cards = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $cards[] = [
            'cardId' => strval($row['card_id']),
            'abilityCount' => intval($row['ability_count']),
            'listenerCount' => intval($row['listener_count']),
        ];
    }
    mysqli_free_result($result);
    mysqli_close($conn);

    $directory = dirname($output);
    if (!is_dir($directory) && !mkdir($directory, 0775, true)) {
        throw new RuntimeException("Could not create output directory: {$directory}");
    }
    $payload = [
        'format' => 'grand-archive-implemented-cards/v1',
        'rootName' => 'GrandArchiveSim',
        'exportedAt' => gmdate('c'),
        'cards' => $cards,
    ];
    if (file_put_contents($output, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n") === false) {
        throw new RuntimeException("Could not write {$output}");
    }
    echo 'Exported ' . count($cards) . " implemented GrandArchiveSim cards to {$output}\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'Export failed: ' . $error->getMessage() . "\n");
    exit(1);
}
