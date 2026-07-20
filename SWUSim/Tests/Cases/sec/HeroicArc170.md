# DamagedUnit_Deal2
#// SEC_254 Heroic ARC-170 (Space, 4/3, Heroism, cost 4) — When Played: If you control a damaged unit,
#//   you may deal 2 damage to an enemy unit. A damaged friendly is in play → deal 2 to the enemy.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:2
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_254

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION

---

# NoDamagedUnit_NoDeal
#// SEC_254 Heroic ARC-170 — no damaged friendly unit → no damage offered.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_254

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
