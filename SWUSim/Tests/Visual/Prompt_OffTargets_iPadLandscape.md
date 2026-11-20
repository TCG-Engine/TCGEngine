# Prompt_OffTargets_iPadLandscape
#// VISUAL CHECK — the target prompt ("Defeat a friendly and an enemy unit? / PASS") never covers one of its own targets.
#//
#// iPad Pro report 2026-11-06 (Discord, landscape): "Chimaera wouldn't let me sac my Mando token because the window to
#// pass was blocking the unit" and "Grievous: couldn't do 4 damage to his base because the window didn't let me click
#// on it". Two layout bugs, neither in the cards:
#//   · STRETCH — SWUSim/Custom/GameLayout.php's lift-above-the-hand rule (`bottom … !important`) beat the drag helper's
#//     inline bottom:auto, so dragging the prompt UP stretched it into a tall see-through slab, and that position was
#//     saved (localStorage `mzchoose-inline-position-v1`) for every later prompt on the device — over both bases.
#//     Fixed by `#selection-message[style*="bottom: auto"] { bottom: auto !important; }`.
#//   · BLIND PLACEMENT — bottom-centre covered the SECOND ROW of a go-wide ground arena at the iPad Pro 11" landscape
#//     Safari viewport (~1194x760). Fixed by swuKeepPromptOffTargets (GameLayoutShared.php): after Core places the
#//     prompt, it slides to the NEAREST spot that covers no .selectable-card. The saved drag position is not rewritten.
#//
#// HOW TO RUN
#//   cd DevTools/ui-harness && node swusim-prompt-off-targets-xbrowser.mjs   # Chromium / Firefox / WebKit, seeds its own boards
#//   By hand: load this schema, resize the window to ~1194x760, play Chimaera from hand.
#//
#// WHAT TO LOOK AT (the prompt is left PENDING — the moment under test IS the prompt)
#//   1. All six friendly ground units + Chimaera glow, and NO part of the prompt sits on any of them. The second row
#//      (Marine · Mandalorian · Marine) is the row it used to cover — the prompt now sits below that row.
#//   2. The prompt is one line tall (~58px) and its PASS button is visible and clickable.
#//   3. Drag the prompt up by its text: it MOVES and keeps its height. A prompt that grows taller as you drag is the bug.
#//   4. Reload with the dragged position saved: the prompt reopens at that spot, still one line tall — unless the spot
#//      covers a glowing unit, in which case it shifts to the nearest clear spot.
#//   5. CONTROL — on a board with room (a wide desktop window), the prompt stays bottom-centre above the hand.
#//
#// BOARD SHAPE — why each piece is here
#//   · six friendly ground units, Mandalorian tokens (ASH_T01) and Marines (SOR_095) alternating: at the iPad width the
#//     arena wraps 3 + 3, and the second row is what the prompt covered. Tokens because the report was about a token.
#//   · three enemy Dark Troopers (SEC_080): Chimaera needs an enemy non-leader unit or it never asks at all.
#//
#// NOT COVERED: with SEVEN or more units, the third row wraps under the HAND band — a separate arena-overflow bug, not
#// the prompt. Phone layout (?swuLayout=mobile): the wrapper runs there too and leaves Core's placement alone when
#// nothing is covered (checked 2026-11-06).
#// ⚠ Cross-browser: Chromium + Firefox + WebKit via the harness above. Playwright's WebKit is not a real iPad Safari —
#//   touch-to-hover emulation in particular cannot be reproduced there.

## GIVEN
CommonSetup: bbk/bbk/{myResources:7;handCardIds:ASH_052}
P1OnlyActions: true
WithP1GroundArena: [ASH_T01:1:0 SOR_095:1:0 ASH_T01:1:0 SOR_095:1:0 ASH_T01:1:0 SOR_095:1:0]
WithP2GroundArena: [SEC_080:1:0 SEC_080:1:0 SEC_080:1:0]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:6
P1SPACEARENACOUNT:1
P1SELECTABLEHAS:myGroundArena-3
