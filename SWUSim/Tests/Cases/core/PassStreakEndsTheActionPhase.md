# TwoConsecutivePasses_EndTheActionPhase
#// CR: the action phase ends when every player passes consecutively. Guarding it because the
#// action-close work (docs/action-close-ownership.md) moved when and where an action ends, and a pass
#// is the ONE action that does not go through SWUAfterAction — SWUPassAction swaps the turn itself.
#//
#// ⚠ The phase lands in RES, not RGS. RGS and DRAW are AUTO transitions that pass straight through;
#// RES is the first regroup step that stops for input ("Resource up to 1 card"). Asserting RGS here
#// looks right and fails — that mistake is why a live browser run appeared to show passes NOT ending
#// the phase, when the engine was correct all along.

## GIVEN
CommonSetup: rrk/bbw/{myResources:6,theirResources:6}
WithActivePlayer: 1
WithInitiativePlayer: 1

## WHEN
- P1>Pass
- P2>Pass

## EXPECT
PHASE:RES

---

# SinglePass_PassesTheTurnWithoutEndingThePhase
#// The discriminating half: one pass must NOT end the phase, only hand the turn over. Without this,
#// an engine that ended the phase on the FIRST pass would still satisfy the section above.

## GIVEN
CommonSetup: rrk/bbw/{myResources:6,theirResources:6}
WithActivePlayer: 1
WithInitiativePlayer: 1

## WHEN
- P1>Pass

## EXPECT
TURNPLAYER:2
PHASE:MAIN
