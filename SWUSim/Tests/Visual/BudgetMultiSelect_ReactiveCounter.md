# VISUAL CHECK — the weighted-budget multi-select: live counter + reactive greying-out
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor, then play Pre Vizsla from P1's hand.
#
# WHY THIS EXISTS
# "Defeat/exhaust any number of units with a combined <metric> of N or less" used to be presented as a
# re-offered one-at-a-time MZMAYCHOOSE loop: pick a unit, get asked again against the leftover budget,
# repeat. The player never saw a running total and could not revise a pick — and because the loop's
# continuation carried the payoff, a PASS mid-loop dropped it entirely (bug #972: units defeated, no
# Mandalorian tokens).
# It is now ONE MZMULTICHOOSE with a weighted budget. The per-unit weights reach the client on a
# "~BUDGET~<total>~<label>~<mzID>=<weight>…" tooltip side channel (the same mechanism as Disclose's
# "~REQ~"), parsed by ParseBudgetTooltip (Core/MZMultiChooseUI.js). Remaining HP is not derivable from a
# CardID, so nothing here can be recomputed client-side — a wrong weight is a silently wrong UI, and
# every assertion the regression suite can make is about the server half.
# Family: ASH_053 Pre Vizsla (remaining HP), LOF_201 Qui-Gon Jinn's Lightsaber (cost), LOF_202 Mind
# Trick (power). Only the HP one is set up here; the other two share the identical presentation.
#
# ⚠ WHICH UI THIS IS. An MZMULTICHOOSE has TWO presentations and they are easy to confuse. Specs that
# include popup-zone cards open the Core/MZMultiChooseUI.js MODAL (a dark overlay panel of card images);
# specs over ARENA targets — every card in this family — take the INLINE path instead: the units glow on
# the board in place and a draggable prompt bar appears mid-screen with the counter and the buttons.
# THIS CHECK IS THE INLINE ONE. The first implementation only handled the modal, which shipped a prompt
# bar displaying the raw "~BUDGET~…" text and a selection that went over budget freely — the visible
# board is the only place that shows up.
#
# THE BOARD — P2's three units have remaining HP 4, 2 and 3:
#   Consular Security Force (SOR_046, 3/7) with 3 damage -> 4 remaining
#   Vanguard Infantry       (SOR_108, 1/2)               -> 2 remaining
#   Battlefield Marine      (SOR_095, 3/3)               -> 3 remaining
# Pre Vizsla himself enters at 6/6 and is a legal target of his own ability at exactly the budget.
#
# WHAT TO LOOK AT — play ASH_053, then work through the prompt bar and the board:
#   • The prompt reads "Defeat any number of non-leader units with 6 or less combined remaining HP" and
#     NOTHING ELSE. If any "~BUDGET~" text is visible, the side channel is not being stripped.
#   • The counter reads "6 of 6 HP left" — not "0 selected / 4 max".
#   • There is NO "Select All" button. It cannot respect a budget and is removed; "Deselect All" and
#     "Confirm" remain.
#   • All four units (the three enemies + Pre Vizsla) glow as selectable at full opacity.
#   • Click the 4-remaining unit: it turns gold, the counter drops to "2 of 6 HP left", and the
#     3-remaining unit AND Pre Vizsla (6) both dim to ~45% with a grey outline and a not-allowed cursor.
#     Clicking a dimmed unit must do NOTHING — no selection, no counter change. The 2-remaining unit
#     stays bright.
#   • Click the gold unit again: it deselects, the counter returns to "6 of 6 HP left", and every unit
#     goes bright again. A SELECTED unit must never dim — revising a pick is the whole point.
#   • Click the 3-remaining unit first instead: the counter reads "3 of 6 HP left" and only the
#     4-remaining unit and Pre Vizsla dim; the 2-remaining unit is still available (3+2 = 5 <= 6).
#   • Take 4 then 2 — exactly the budget: the counter reads "0 of 6 HP left" and every unselected unit
#     is dimmed. Confirm: both are defeated and two Mandalorian tokens (ASH_T01) appear on P1's side,
#     each showing a Shield badge.
#   • Confirm with NOTHING selected: nothing is defeated and no tokens appear. Confirm must stay enabled
#     at zero picks — "any number" includes none.
#   • The dimming must survive a full board re-render, not just the click: after selecting, take any
#     action that redraws the rows and confirm the dimmed units have NOT snapped back to bright.
#   • Check in Chromium AND Firefox (repo cross-browser rule); WebKit will not launch on this machine,
#     so say so rather than implying it was covered.
#
# The GIVEN state plus the one PlayHand is the whole check — the modal is what is being inspected.

## GIVEN
CommonSetup: bbk/rrk/{myResources:8}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:3
WithP2GroundArena: SOR_108:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: ASH_053

## WHEN

## EXPECT
P2GROUNDARENACOUNT:3
P1HANDCOUNT:1
