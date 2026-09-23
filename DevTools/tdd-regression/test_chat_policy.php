<?php
// The "may this viewer send?" matrix, per sim and per scope.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off DevTools/tdd-regression/test_chat_policy.php
//
// The refusal STRINGS are asserted verbatim, not just their truthiness: SubmitChat.php echoes them
// straight to the player and the panel shows them, so a reworded string is a user-visible change.
error_reporting(E_ALL & ~E_DEPRECATED);
require_once __DIR__ . '/../../Core/ChatPolicy.php';

$FAILS = 0;
function check($cond, $msg) { global $FAILS; echo ($cond ? '  ok: ' : '  BAD: ') . "$msg\n"; if (!$cond) $FAILS++; }

function viewer($seat, $userId, $spectator = false) {
    return ['viewerID' => strval($seat), 'viewerSeat' => $seat, 'isSpectator' => $spectator,
            'canAct' => !$spectator, 'label' => 'P' . $seat, 'userId' => $userId];
}

echo "── SWUSim: an account is required to CHAT, never to play (owner, 2026-09-21) ──\n";
check(ChatSendRefusal('SWUSim', viewer(1, 42), '4242') === null, 'a logged-in seat may chat in a game');
check(ChatSendRefusal('SWUSim', viewer(1, 0),  '4242') === 'Log in to chat.', 'a guest is refused, with the existing wording');
check(ChatSendRefusal('SWUSim', viewer(1, 0),  'l:abc') === 'Log in to chat.', 'the guest gate holds in a LOBBY too');
check(ChatSendRefusal('SWUSim', viewer(1, 0),  'm:M1') === 'Log in to chat.', 'and in a MATCH');
check(ChatSendRefusal('SWUSim', viewer(1, 42), 'l:abc') === null, 'a logged-in seat may chat in a lobby');

echo "── a sim with no policy file allows everyone ──\n";
check(ChatSendRefusal('FaBSim', viewer(1, 0), 'l:abc') === null, 'FaB has no login gate');
check(ChatSendRefusal('', viewer(1, 0), '4242') === null, 'an empty folderPath allows');
check(ChatSendRefusal('../etc', viewer(1, 0), '4242') === null, 'a path-shaped folderPath is not loaded and allows');

echo $FAILS === 0 ? "\nALL PASS\n" : "\n$FAILS FAILED\n";
exit($FAILS === 0 ? 0 : 1);
