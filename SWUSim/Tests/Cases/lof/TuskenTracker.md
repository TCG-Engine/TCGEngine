# EnemiesLoseHidden
#// LOF_209 Tusken Tracker — Raid 2 + When Played: each enemy unit loses Hidden for this phase. P1 plays
#// it; the enemy Hidden unit (LOF_228) no longer has Hidden.

## GIVEN
CommonSetup: yyk/rrw/{myResources:3;handCardIds:LOF_209}
P1OnlyActions: true
WithP2GroundArena: LOF_228:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:NOTKEYWORD:Hidden

---

# HiddenSuppressionExpiresNextPhase
#// LOF_209 Tusken Tracker — DURATION. "Each enemy unit loses Hidden FOR THIS PHASE", so after passing
#// into the next action phase the enemy LOF_228 must have Hidden back. Tusken Tracker's own READY state
#// is the control: it entered play exhausted and the Ready step of the regroup phase readies it, which
#// proves the phase boundary was actually crossed.

## GIVEN
CommonSetup: yyk/rrw/{myResources:3;handCardIds:LOF_209}
P1OnlyActions: true
WithP2GroundArena: LOF_228:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LOF_209
P1GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:CARDID:LOF_228
P2GROUNDARENAUNIT:0:HASKEYWORD:Hidden
