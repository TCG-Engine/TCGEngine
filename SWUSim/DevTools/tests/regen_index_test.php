<?php
// Runs the extracted SWUSimBuildCardIndex against the real cards dir and asserts
// it maps a known reprint CID to the earliest-printing file (scan-based, not guessed).
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } }

require __DIR__ . '/../regen-card-index.php';   // must expose the functions without running the CLI

$cardsDir = __DIR__ . '/../../Custom/cards';
$idx = SWUSimBuildCardIndex($cardsDir);

check(is_array($idx), 'index is an array');
check(($idx['SEC_030'] ?? '') === 'sor/DeathTrooper.php', 'reprint SEC_030 -> sor/DeathTrooper.php, got: ' . ($idx['SEC_030'] ?? 'MISSING'));
check(($idx['SOR_033'] ?? '') === 'sor/DeathTrooper.php', 'primary SOR_033 -> sor/DeathTrooper.php');
echo "OK\n";
