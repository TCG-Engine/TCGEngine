# ⚠⚠ NOT A REGRESSION SECTION — a VISUAL fixture, built by DevTools/ui-harness/swusim-opponent-unit-action-xbrowser.mjs.
#
# VISUAL CHECK — the 2-player half: P2 controls TS26_15 C-3PO, P1 (his opponent) taps him on P2's board and gets an
# Ability-only menu; Ability opens the damage target prompt. Before 2026-10-22 the tap did nothing (see
# TwinSuns_OpponentUnitAction_C3P0.md).

## GIVEN
CommonSetup: gbw/rrk/{myResources:6}
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP1GroundArena: [SOR_046:1:0]
WithP2GroundArena: [TS26_15:1:0]

## WHEN

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:TS26_15
