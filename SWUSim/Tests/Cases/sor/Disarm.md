# ChoosesAmongEnemies
#// SOR_216 Disarm — with 2 enemy units, player chooses which one is shrunk.
#// AT-AT (idx 0, 9/9) and Imperial Dark Trooper (idx 1, 3/3). Choose idx 1.
#// Only the chosen unit gets −4/−0; the other is untouched.

## GIVEN
CommonSetup: yyk/yyk/{myResources:1;handCardIds:SOR_216}
WithP2GroundArena: SOR_088:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SOR_088
P2GROUNDARENAUNIT:0:POWER:9
P2GROUNDARENAUNIT:1:CARDID:SEC_080
P2GROUNDARENAUNIT:1:POWER:0

---

# PowerFloorAtZero
#// SOR_216 Disarm — −4/−0 cannot push power below 0.
#// Battlefield Marine (3/3): power 3 − 4 floors at 0 (not −1). HP unchanged at 3.

## GIVEN
CommonSetup: yyk/yyk/{myResources:1;handCardIds:SOR_216}
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:POWER:0
P2GROUNDARENAUNIT:0:HP:3

---

# ReducesEnemyPower
#// SOR_216 Disarm — Give an enemy unit −4/−0 for this phase.
#// Single enemy unit (Blizzard Assault AT-AT, 9/9) → auto-target.
#// Power 9 − 4 = 5; HP unchanged at 9 (−0).

## GIVEN
CommonSetup: yyk/yyk/{myResources:1;handCardIds:SOR_216}
WithP2GroundArena: SOR_088:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_088
P2GROUNDARENAUNIT:0:POWER:5
P2GROUNDARENAUNIT:0:HP:9
