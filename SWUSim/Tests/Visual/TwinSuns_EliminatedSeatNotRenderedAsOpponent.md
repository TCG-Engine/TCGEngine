# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT. It checks the LIVE BOARD RENDER, which a gamestate test cannot see.
#
# VISUAL CHECK — a seat that has just been ELIMINATED must never stay on screen as your opponent
# (game 846502, fixed 2026-09-20)
#
#   REPORTED: 3-player Twin Suns. P3 attacked P1's base with Red Five (dealing the On Attack 2 to P2's
#   Bo-Katan). P1 was eliminated by that attack — and the board switched to "P3 vs P1": an empty enemy
#   arena and a bare "Base" placeholder where P1's base had been. It stayed that way.
#
#   ROOT CAUSE — everything in the opponent binding was frozen at PAGE LOAD. NextTurnRender.php emits
#       var otherPlayerIndex = (window.swuView && window.swuView.oppSeat) ? window.swuView.oppSeat : <fallback>;
#   NextTurn.php includes that file ONCE, when the page loads, but the function it lands in re-runs on
#   EVERY RenderUpdate. So both halves were stale after an elimination:
#     • the override — swuView.oppSeat still named P1 (it is opps[0], so it is 1 even on the HOME view);
#     • the fallback — a PHP literal baked as NextLiveSeat(3) = 1, correct only while P1 was alive.
#   Both named the dead seat, whose base and arenas _SWUEliminationCleanup had just cleared, so the
#   enemy half rendered completely empty.
#   ⚠ THREE FIXES FAILED BEFORE THIS ONE, ALL THE SAME MISTAKE — reading a value that is fixed at page
#   load instead of per render: window.LiveSeatsData (assigned ~35 lines BELOW this one, so stale on the
#   one poll that matters), then a server-baked live list, then guarding the override while leaving the
#   baked fallback in place. The fix reads the live-seat list out of responseArr — the render's own
#   input, fresh every repaint — and computes the fallback in JS, mirroring NextLiveSeat().
#   FIX lives in zzGameCodeGenerator.php (the SWUSim branch). ⚠ NextTurnRender.php is GENERATED and
#   gitignored — a prod deploy needs the SWUSim regen or the old frozen binding is still live.

## HOW TO RUN

Automated (Chromium), against a 3-seat game where P3 can attack P1's base:
    cd DevTools/ui-harness && node swusim-eliminated-opponent-view.mjs <srcGameId> [<scratchGameId>]
It clones the source game, rewinds the clone with the engine's own undo until all three seats are live,
opens the board as P3, causes the elimination over HTTP, and watches what the ALREADY-OPEN session
repaints. It SKIPS cleanly if the clone cannot be rewound to three live seats, and deletes the clone.

⚠ TWO TRAPS THIS HARNESS EXISTS TO AVOID — do not "simplify" either away:
  • Do NOT load an already-eliminated game and set window.swuView by hand. That version PASSED against
    a broken build, because a settled page has none of the page-load staleness the bug depends on.
  • Do NOT drive the elimination with `docker exec php`. The container runs apc.enable_cli=0, so a CLI
    process cannot touch the APCu cache piece the web long-poll watches: the gamestate changes and the
    open session never hears about it, which looks exactly like "the client didn't repaint".

To rebuild the state by hand from a 3P game where P1 is about to die:
  1. as P3, attack P1's base; answer the On Attack by damaging a P2 unit
  2. P1 is eliminated — watch the board WITHOUT reloading

## WHAT TO LOOK AT

1. **The board shows the surviving opponent, not the dead one.** After P1 dies, P3's enemy half must be
   P2's board — P2's base with its damage, P2's units. Never an empty arena with a bare "Base" label.
2. **⚠ DO NOT RELOAD BEFORE LOOKING.** A reload clears swuView and hides the bug completely. The whole
   failure only exists in the session that was open when the elimination happened.
3. **The Twin Suns chrome retires cleanly.** With 2 live seats swuBuildViews() returns [] by design, so
   the home strip and pair-switcher arrows go away and the board becomes a normal two-player layout.
   That part is intended — it is the OPPONENT IDENTITY that was wrong, not the collapse itself.
4. **The eliminated player is still whisperable.** The whisper row keeps P1 (it reads SeatOrder, not
   LiveSeats — deliberate). Seeing P1 there is correct and is not this bug.
5. **The game is still playable.** The surviving seats can act, and two passes end the phase and score
   the game by highest remaining base HP (CR §12.7). The engine was never stuck — this was render-only.
6. **2-player games are unaffected.** They never set swuView, so the override branch is dead there and
   the guard changes nothing. Check any Premier game still renders its opponent normally.

## THE ELIMINATED PLAYER'S OWN VIEW (owner, 2026-09-20)

A player knocked out of a 3-seat game keeps watching, as a read-only spectator of the last two.

7. **P1's board becomes P2 vs P3.** Not their own emptied board — swuBuildViews() now hands an
   eliminated-but-seated viewer a single `{viewSeat: <first survivor>, oppSeat: <second>}` view. One
   view only (owner): with two players left both orientations show the same two boards, mirrored.
8. **They are visibly read-only.** `body.swu-spectating`, the "👁 Read-only — viewing P2 vs P3" badge,
   and the existing capture-phase click guard that swallows board clicks. No new wiring — giving
   viewSeat to a seat that is not you is exactly what swuApplySpectate() keys on.
9. **No hands.** Neither half may show a card identity. Face-DOWN backs are expected and fine: hand
   SIZE is public on any board. This is enforced SERVER-side ($canSeePrivatePlayerN is strictly "the
   viewer IS seat N"), so the client cannot leak it however the view is pointed — assert it anyway.
10. **Living players are untouched.** In a 2-player game and in a 3-player game with everyone alive,
    every seat still sees its OWN board and its OWN hand, and `swuSpectating` stays false.
11. **4-PLAYER IS DELIBERATELY UNCHANGED** (owner). With 3 survivors swuBuildViews() does not bail, so
    an eliminated player still gets Home plus a matchup per survivor and can follow the game. ⚠ Two
    known rough edges there, accepted for now: each matchup is THEIR OWN empty board vs that opponent
    rather than opponent-vs-opponent, and they are NOT flagged read-only (viewSeat is still them), so
    no badge and no click guard — the server refuses their actions regardless.
