# VISUAL CHECK — Sabé's peek shows the two cards, each with its own Discard button
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression runner). Load it in the Test
# Schema Editor, or run the automated probe:
#   cd DevTools/ui-harness && node swusim-optchoose-card-choice-xbrowser.mjs
#
# WHAT CHANGED (2026-10-19, owner request, UI ONLY)
#   SEC_017 Sabé — Queen's Shadow: "look at the top 2 cards of the defending player's deck. Discard 1 of those
#   cards." The prompt is an OPTIONCHOOSE whose two options are the CardIDs. Core/OptionChooseUI.js drew them
#   as text buttons ("SHD 043" / "LOF 093"), so the player picked a set number without seeing the cards.
#   Now, when EVERY option is a CardID (and there is no "@" art segment), the banner draws each option as its
#   card with a button under it, labelled by the prompt's leading verb — "DISCARD" here. One click answers;
#   there is no separate confirm, by design (speed).
#   ⚠ NOTHING MECHANICAL CHANGED: same decision type, same Param, and each button submits the same raw CardID
#   the old text button did. The un-picked card stays on top exactly as before (SEC_017#4 is untouched).
#   JTL_039 Chimaera's "Choose a When Defeated ability to use" is the other OPTIONCHOOSE whose options are
#   CardIDs, so it gets the same card view (button "CHOOSE").
#
# WHAT TO LOOK AT (and what the probe asserts)
#   1. The banner shows the prompt "Discard 1 of the top 2 (other stays on top)" on its own row, then TWO
#      cards side by side: LAW_075 and TWI_110 (the top two of P2's deck, in deck order).
#   2. Each card has ONE button directly beneath it reading DISCARD. No "SEC 080"-style text buttons remain.
#   3. Every button is fully on screen and is the topmost element at its own centre — desktop 1600x900 AND
#      phone 390x844 (?swuLayout=mobile). The card art has decoded (non-zero width) in both.
#   4. Clicking DISCARD under LAW_075 answers at once: the banner disappears, P2's discard pile gains LAW_075,
#      P2's deck goes 4 -> 3, and TWI_110 is still P2's TOP card.
#   5. Other OPTIONCHOOSE shapes are UNCHANGED: "Ground&Space" still renders text buttons, and an "@CardID"
#      prompt (Watchful / Improvise "Play / Discard / Leave") still shows its card beside text buttons.
#
# BOARD SHAPE
#   • P1 leader SEC_017 Sabé (ready, undeployed) — the ability's source.
#   • P1 SOR_046 in the ground arena, ready — the friendly unit whose combat damage to P2's base triggers it.
#   • P2 deck [LAW_075 TWI_110 SOR_063 SOR_063] — two DIFFERENT cards on top so the tiles are distinguishable
#     and the "other stays on top" half is observable; two more beneath so a wrong-card discard shows.
#   • The decision is left PENDING: the moment under test is the prompt.
#
# CROSS-BROWSER: Chromium + Firefox + WebKit, desktop and ?swuLayout=mobile (CLAUDE.md). If WebKit cannot
#   launch on the machine running it, that engine is a stated gap, not a pass.

## GIVEN
CommonSetup: gbk/brk/{myLeader:SEC_017;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2Deck: [LAW_075 TWI_110 SOR_063 SOR_063]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Discard_1_of_the_top_2_(other_stays_on_top)
P2DECKCOUNT:4
P2DISCARDCOUNT:0
