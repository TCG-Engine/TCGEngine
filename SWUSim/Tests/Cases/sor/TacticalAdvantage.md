# BuffsUnit
#// SOR_124 Tactical Advantage — "Give a unit +2/+2 for this phase." (Event, cost 1, Command)
#// Single unit in play (Blizzard Assault AT-AT SOR_088, 9/9) → auto-target.
#// Power 9+2=11, HP 9+2=11.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1;handCardIds:SOR_124}
WithP1GroundArena: SOR_088:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_088
P1GROUNDARENAUNIT:0:POWER:11
P1GROUNDARENAUNIT:0:HP:11

---

# CanTargetEnemyUnit
#// SOR_124 Tactical Advantage — "a unit" means ANY unit, enemy included
#// (unlike Attack Pattern Delta, which is friendly-only).
#// Only an enemy unit in play (SOR_088, 9/9) → auto-target it: power 9+2=11, HP 9+2=11.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1;handCardIds:SOR_124}
WithP2GroundArena: SOR_088:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_088
P2GROUNDARENAUNIT:0:POWER:11
P2GROUNDARENAUNIT:0:HP:11

---

# SimulateRequestBoundary_PhaseBuffSurvivesTheChoose
#// SOR_124 Tactical Advantage — BuffsUnit has a single unit in play, so the target auto-resolves and no
#// request ever ends. A second friendly unit keeps the choose interactive, and the boundary goes before
#// the answer: in production the choose ends the request and the answer arrives in a fresh process.
#// The AT-AT is chosen → +2/+2 for this phase → 11/11, and the untargeted Marine stays 3/3.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1;handCardIds:SOR_124}
WithP1GroundArena: SOR_088:1:0
WithP1GroundArena: SOR_095:1:0    # 2nd legal target, keeps the choose interactive

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1DISCARDCOUNT:1
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_088
P1GROUNDARENAUNIT:0:POWER:11
P1GROUNDARENAUNIT:0:HP:11
P1GROUNDARENAUNIT:1:POWER:3
