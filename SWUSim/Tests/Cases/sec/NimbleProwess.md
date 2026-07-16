# WhenPlayed_AllExhausted_AutoPass
#// SEC_069 Nimble Prowess — When Played "you may exhaust a unit in attached unit's arena" must AUTO-PASS
#//   when there is no READY unit to exhaust (only ready units can be exhausted). Host is attached to an
#//   already-exhausted unit and the only other arena unit is also exhausted, so no prompt should fire.
#//   Repro of game 2619: Lama Su plays Nimble Prowess on an exhausted unit, offering a meaningless prompt.

## GIVEN
CommonSetup: bbk/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SEC_041:0:0
WithP2GroundArena: SOR_046:0:0
WithP1Hand: SEC_069

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION

---

# WhenPlayed_ExhaustInArena
#// SEC_069 Nimble Prowess (upgrade, +1/+1) — Attach to a friendly unit. When Played: you may exhaust a
#//   unit in attached unit's arena. P1 attaches it to SEC_041 (ground) and exhausts the enemy SOR_046.

## GIVEN
CommonSetup: bbk/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
