<?php
// Sealed replays are opt-in per root: a root that defines neither GameMatchReplaySealPolicy() nor GameMatchReplaySealKey()
// (AzukiSim; GrandArchiveSim has no MatchReplay module at all) keeps the plain format and plain command state, unchanged.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_matchreplay_plain_roots.php
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';
require_once __DIR__ . '/../../Core/MatchReplay.php';

SwuPgnTestCheck(!function_exists('GameMatchReplaySealPolicy') && !function_exists('GameMatchReplaySealKey'), 'precondition: no root seal hooks loaded');
SwuPgnTestEq(MatchReplayShouldSeal(), false, 'a root without the policy hook never seals');
SwuPgnTestEq(MatchReplaySeal(['rootName' => 'AzukiSim', 'gameName' => '1', 'initialGamestate' => "x\n", 'actions' => []], 1), null, 'and cannot: no key');
$plain = ['format' => 'tcgengine-match-replay-commands-v1', 'actions' => [], 'nextActionIndex' => 0, 'playback' => true, 'interrupted' => false,
    'createdAt' => 'c', 'updatedAt' => 'u', 'sourceGameName' => 'g', 'sourceSavedAt' => 's'];
SwuPgnTestEq(array_keys(MatchReplayNormalizeCommandState($plain)), ['format', 'createdAt', 'updatedAt', 'actions', 'nextActionIndex', 'playback', 'interrupted', 'sourceGameName', 'sourceSavedAt'],
    'a plain command state keeps exactly its fields (no sealed keys appear)');
SwuPgnTestEq(MatchReplayUnseal(['format' => 'tcgengine-match-replay-v1', 'rootName' => 'AzukiSim']), null, 'a plain record is never "unsealed"');
SwuPgnTestEq(MatchReplayValidateReplayPayload(['format' => 'tcgengine-match-replay-v1', 'rootName' => 'AzukiSim', 'initialGamestate' => "x\n", 'actions' => []]), '',
    'a plain payload still validates');
SwuPgnTestFinish();
