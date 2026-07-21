# ExhaustSentinel
#// LOF_223 Force Illusion — Exhaust an enemy unit. A friendly unit gains Sentinel for this phase. The enemy
#// SOR_046 is exhausted; Plo Koon gains Sentinel.

## GIVEN
CommonSetup: yyw/ggk/{myResources:2;handCardIds:LOF_223}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# NoFriendlyUnit_StillExhausts
#// LOF_223 Force Illusion — with no friendly unit to receive Sentinel, the enemy-exhaust half still resolves:
#// the enemy SOR_046 is exhausted.

## GIVEN
CommonSetup: yyw/ggk/{myResources:2;handCardIds:LOF_223}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# SentinelExpiresNextPhase
#// LOF_223 Force Illusion — the granted Sentinel lasts only "for the phase." Plo Koon gains Sentinel, then
#// both players pass and regroup runs turn-effect expiry; next phase Plo Koon no longer has Sentinel. (An
#// enemy SOR_046 is present so the Sentinel is actually granted — the sibling ExhaustSentinel section asserts
#// it is present this phase.)

## GIVEN
CommonSetup: yyw/ggk/{myResources:2;handCardIds:LOF_223}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# NoEnemyUnit_StillGrantsSentinel
#// LOF_223 Force Illusion — the two sentences are independent. With no enemy unit to exhaust, the (unconditional)
#// Sentinel-grant clause must STILL resolve: Plo Koon gains Sentinel.

## GIVEN
CommonSetup: yyw/ggk/{myResources:2;handCardIds:LOF_223}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
