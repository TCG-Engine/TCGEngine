# NoForce_NoEffect
#// LOF_035 Talzin's Assassin — without the Force the optional "use the Force" is not offered (you can't
#// use a Force you don't control): the unit just enters play and no debuff happens.

## GIVEN
CommonSetup: bbk/rrk/{myResources:4;handCardIds:LOF_035}
P1OnlyActions: true
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NOFORCE
P1GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:POWER:4
P1NODECISION

---

# UseForce_Debuff3
#// LOF_035 Talzin's Assassin (4/4) — When Played: you may use the Force → give a unit -3/-3 for this
#// phase. P1 plays it with the Force, uses the Force, and debuffs the enemy 4/7 (power 4 → 1).

## GIVEN
CommonSetup: bbk/rrk/{myResources:4;handCardIds:LOF_035}
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1NOFORCE
P2GROUNDARENAUNIT:0:POWER:1
