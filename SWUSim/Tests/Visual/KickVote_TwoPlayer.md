# VISUAL CHECK — Inactivity kick vote, 2-player (75s clock)
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
#   GN=$(curl -s -X POST http://localhost:3400/TCGEngine/SWUSim/TestSchemaSetup.php --data-urlencode "schema@SWUSim/Tests/Visual/KickVote_TwoPlayer.md" | grep -o '"gameName":[0-9]*' | grep -o '[0-9]*')
#   Seat N:    http://localhost:3400/TCGEngine/NextTurn.php?folderPath=SWUSim&gameName=$GN&playerID=N&authKey=testschema
#   Spectator: same with playerID=S · Phone: add &swuLayout=mobile
#   ⚠ DON'T WAIT 75 SECONDS — rewind the clock (dev-only endpoint, refuses outside DEVENV):
#     curl -s "http://localhost:3400/TCGEngine/SWUSim/DevTools/zz_presence_poke.php?gameName=$GN&seat=1&back=200&actedOnly=1"
#     (actedOnly=1 = a STALL: seat 1 is still polling. Drop it to simulate a DISCONNECT.)
#   Automated: node DevTools/ui-harness/swusim-kick-vote-xbrowser.mjs
#
# WHAT THIS PINS (spec docs/superpowers/specs/2026-09-17-swusim-inactivity-timer-and-kick-design.md)
#   A stalling or rage-quitting player can be removed by the others, and the game still counts.
#   ⚠ The prompt is NOT a decision-queue entry — a DQ prompt would freeze the table and block the
#   stalled player from acting, and acting is exactly how they cancel the vote.
#
# WHAT TO LOOK AT
#   • Before expiry: nothing on screen (the clock line only appears in the last 20s).
#   • After the rewind, SEAT 2 sees a purple-bordered card at top-centre: "Kick P1?" with a red
#     "Kick P1" button and a grey "Wait another 20 seconds". There is deliberately NO "No" button.
#   • SEAT 1 (the target) sees the warning line instead — "You have Ns to act — the other players can
#     vote to remove you" — and NO buttons. They are never blocked from acting.
#   • A SPECTATOR sees the waiting line and no buttons.
#   • Click "Wait another 20 seconds": the prompt disappears for BOTH players. Rewind again and it
#     returns (a lapsed extension is a real event, so the poll wakes).
#   • Click "Kick P1": the game ends, seat 2 wins, and the end-game overlay appears. Stats are recorded
#     exactly as for a concede — ⚠ except before round 2, where the existing gates record none.
#   • Instead of voting, have seat 1 ACT (play/attack/pass): the prompt closes on every screen and the
#     sticky votes are cleared. Declaring an attack without choosing a target does NOT count as acting.
#   • z-index: the prompt sits BELOW decision modals (4990 vs 5000) — the target's own prompt must win.
#
# AUTOMATED PROBE (2026-09-17): 21 checks per engine — Chromium 21/21 · Firefox 21/21 · WebKit 21/21.
#   Screenshots reviewed on desktop (1700x1050) and phone (400x860) in all three.
#
# NOT COVERED HERE: the disconnect wording (see the Twin Suns spec) and stats submission (integration
#   test DevTools/tdd-regression/test_swusim_kick_vote_flow.php).

## GIVEN
CommonSetup: bbw/rrk/{myResources:8; theirResources:8}
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP1GroundArena: [SOR_032:1:0]
WithP2GroundArena: [SOR_034:1:0]

## WHEN

## EXPECT
TURNPLAYER:1
