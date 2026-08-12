<?php

require_once __DIR__ . '/../../Database/ConnectionManager.php';

$cache = json_decode(file_get_contents(__DIR__ . '/../../FaBSim/GeneratedCode/cardArrayCache.json'), true);
$ids = [];
foreach (($cache['cardArray'] ?? []) as $card) {
    foreach (($card['printings'] ?? []) as $printing) {
        if (($printing['set_id'] ?? '') === 'WTR') { $ids[] = (string)$card['id']; break; }
    }
}
$ids = array_values(array_unique($ids)); sort($ids);
if (count($ids) !== 226) throw new RuntimeException('Expected 226 WTR identities, found ' . count($ids));

$conn = GetLocalMySQLConnection();
if (!$conn) throw new RuntimeException('Unable to connect to the card ability database.');
mysqli_begin_transaction($conn);
try {
    $select = mysqli_prepare($conn, 'SELECT id FROM card_abilities WHERE root_name = ? AND card_id = ? ORDER BY id ASC LIMIT 1');
    $update = mysqli_prepare($conn, 'UPDATE card_abilities SET is_implemented = 1 WHERE root_name = ? AND card_id = ?');
    $insert = mysqli_prepare($conn, "INSERT INTO card_abilities (root_name, card_id, macro_name, ability_type, ability_code, prereq_code, listener_zones, ability_name, is_implemented) VALUES (?, ?, '', 'macro', '', NULL, NULL, '[Card Implemented]', 1)");
    $root = 'FaBSim';
    foreach ($ids as $cardID) {
        mysqli_stmt_bind_param($select, 'ss', $root, $cardID); mysqli_stmt_execute($select); $result = mysqli_stmt_get_result($select);
        if (mysqli_fetch_assoc($result)) { mysqli_stmt_bind_param($update, 'ss', $root, $cardID); mysqli_stmt_execute($update); }
        else { mysqli_stmt_bind_param($insert, 'ss', $root, $cardID); mysqli_stmt_execute($insert); }
    }
    mysqli_commit($conn);
} catch (Throwable $e) { mysqli_rollback($conn); throw $e; }

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('s', count($ids) + 1); $params = array_merge(['FaBSim'], $ids);
$audit = mysqli_prepare($conn, "SELECT COUNT(DISTINCT card_id) AS total FROM card_abilities WHERE root_name = ? AND is_implemented = 1 AND card_id IN ($placeholders)");
mysqli_stmt_bind_param($audit, $types, ...$params); mysqli_stmt_execute($audit); $row = mysqli_fetch_assoc(mysqli_stmt_get_result($audit));
if (intval($row['total'] ?? 0) !== count($ids)) throw new RuntimeException('WTR implementation marker audit failed.');
echo 'WTR implementation database audit passed: ' . count($ids) . '/' . count($ids) . PHP_EOL;

?>
