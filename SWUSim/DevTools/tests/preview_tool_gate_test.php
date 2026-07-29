<?php
// The page must 404 for any root other than SWUSim, and outside local dev.
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } }

$src = file_get_contents(__DIR__ . '/../../../zzPreviewTool.php');

check(strpos($src, 'SWUIsLocalDevRequest') !== false,
      'uses the shared local-dev gate, not a bare getenv check');
check(strpos($src, 'rootName') !== false, 'reads rootName');
check(preg_match('/rootName.*!==?\s*[\'"]SWUSim[\'"]/s', $src) === 1,
      'rejects any rootName that is not SWUSim');
check(strpos($src, 'http_response_code(404)') !== false, 'returns a real 404');

// Both gates must precede any write helper being called.
$gatePos  = strpos($src, 'http_response_code(404)');
$writePos = strpos($src, 'SWUSimWriteMockCard');
check($writePos === false || $gatePos < $writePos, 'gate precedes any write');

// --- reprint path is wired ---
check(strpos($src, "'override'") !== false || strpos($src, '"override"') !== false,
      'page exposes an override action');
check(strpos($src, 'SWUSimWriteReprintOverride') !== false, 'override action calls the writer');
check(strpos($src, 'function createOverride') !== false, 'client handler exists');
check(strpos($src, 'IsSWUCardID($canonical)') !== false,
      'refuses to map onto a CardID the dictionaries do not know');

// --- management actions ---
foreach (['list', 'edit', 'delete', 'setlist', 'regen'] as $act) {
    check(strpos($src, "'" . $act . "'") !== false || strpos($src, '"' . $act . '"') !== false,
          'page exposes the ' . $act . ' action');
}
check(strpos($src, 'SWUSimDeleteMockCard') !== false, 'delete action calls the writer');
check(strpos($src, 'superseded') !== false, 'listing flags superseded mocks');
check(strpos($src, 'zzCardCodeGenerator.php') !== false, 'regen runs the dictionary generator');
check(strpos($src, 'ProcessKeywordsSWU.php') !== false, 'regen runs the keyword processor');
// PHP_BINARY is empty under apache2handler — using it directly makes regen fail with exit 127.
check(strpos($src, 'preview_tool_php_cli') !== false, 'regen resolves a real PHP CLI binary');

// --- repo lint: no native browser dialogs (StyledConfirm/StyledPrompt/StyledAlert/Toast only) ---
// The needles are ASSEMBLED at runtime on purpose: spelling them literally would make the repo's
// own native-dialog scanner flag THIS file. Keep them split.
$nativeDialogs = ['con' . 'firm', 'al' . 'ert', 'pro' . 'mpt'];
foreach ($nativeDialogs as $native) {
    check(!preg_match('/(?<![A-Za-z])' . $native . '\s*\(/', $src),
          'no native ' . $native . '() — use the Styled* dialogs');
}
check(strpos($src, 'StyledConfirm(') !== false, 'delete confirmation uses StyledConfirm');
check(strpos($src, 'Core/StyledDialog.js') !== false, 'page loads StyledDialog.js');

echo "OK\n";
