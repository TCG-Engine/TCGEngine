# Minus8
#// LOF_217 Force Slow — Give an exhausted unit -8/-0 for this phase. The exhausted enemy SOR_046 (power 3)
#// drops to power 0.

## GIVEN
CommonSetup: yyw/ggk/{myResources:1;handCardIds:LOF_217}
P1OnlyActions: true
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:0

---

# FriendlyExhausted_Minus8
#// LOF_217 Force Slow — the -8/-0 can target a FRIENDLY exhausted unit. With a friendly exhausted SOR_046
#// (power 3) and an enemy exhausted SOR_046 both selectable, P1 targets its own unit, dropping it to power 0.

## GIVEN
CommonSetup: yyw/ggk/{myResources:1;handCardIds:LOF_217}
P1OnlyActions: true
WithP1GroundArena: SOR_046:0:0
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:0

---

# SelectableExhaustedOnly
#// LOF_217 Force Slow — only EXHAUSTED units are legal targets; ready units are never offered. P1's exhausted
#// SOR_046 and the enemy exhausted Wampa are selectable, but P1's ready SOR_095 is excluded — exactly the
#// two exhausted units are offered.

## GIVEN
CommonSetup: yyw/ggk/{myResources:1;handCardIds:LOF_217}
P1OnlyActions: true
WithP1GroundArena: SOR_046:0:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# ExpiresNextPhase
#// LOF_217 Force Slow — the -8/-0 lasts only "for this phase." The lone exhausted enemy SOR_046 (power 3) is
#// reduced to 0; after both players pass and regroup runs turn-effect expiry, next phase it is back to its
#// printed power 3.

## GIVEN
CommonSetup: yyw/ggk/{myResources:1;handCardIds:LOF_217}
P1OnlyActions: true
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>Pass

## EXPECT
P2GROUNDARENAUNIT:0:POWER:3

---

# NoExhaustedUnits_SoftPass
#// LOF_217 Force Slow — with no exhausted units in play the event resolves as a soft pass: it is discarded and
#// no unit is modified (both ready units keep their printed power) (Play anyway → discard).

## GIVEN
CommonSetup: yyw/ggk/{myResources:1;handCardIds:LOF_217}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:POWER:4
