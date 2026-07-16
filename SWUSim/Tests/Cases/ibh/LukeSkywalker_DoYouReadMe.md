# WhenPlayed_DealThreeToGround
#// IBH_020 Luke Skywalker (Ground, 6/6, Command/Heroism, cost 7) — Restore 2 (keyword) + When Played: you
#//   may deal 3 damage to a ground unit. Targets the enemy 4/7 wall → DAMAGE:3 (Luke is also a valid
#//   target, so the pick is explicit).

## GIVEN
CommonSetup: ggw/rrk/{myResources:7}
P1OnlyActions: true
WithP1Hand: IBH_020
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION

---

# WhenPlayed_DeclineDamage
#// IBH_020 Luke Skywalker — the When Played damage is optional ("you may"). Decline → no damage.

## GIVEN
CommonSetup: ggw/rrk/{myResources:7}
P1OnlyActions: true
WithP1Hand: IBH_020
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
