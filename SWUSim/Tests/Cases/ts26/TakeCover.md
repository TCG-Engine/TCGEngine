# HealAndShield
#// TS26_47 Take Cover (Event, cost 3, Vigilance) — Heal up to 3 damage from a unit and give it a Shield.
#// LAW_124 (4/7) with 3 damage is healed to 0 damage and shielded.
## GIVEN
CommonSetup: bbw/rrk/{myResources:3;handCardIds:TS26_47}
WithP1GroundArena: LAW_124:1:3
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# HealingZeroStillGivesTheShield
#// TS26_47 Take Cover — "Heal UP TO 3 damage from a unit AND give a Shield token to it". The two halves
#// are joined by "and", so an undamaged target heals nothing and still gets its Shield.

## GIVEN
CommonSetup: bbw/rrk/{myResources:3;handCardIds:TS26_47}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# CostsOneLessPerFriendlyLeaderUnit
#// TS26_47 Take Cover — "costs 1 resource less for each friendly LEADER UNIT". With the leader deployed
#// the event costs 2 instead of 3, leaving 1 of the 3 resources, and LAW_124's 3 damage is fully healed.

## GIVEN
CommonSetup: bbw/rrk/{myResources:3;handCardIds:TS26_47;myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:3
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1RESAVAILABLE:1
P1GROUNDARENAUNIT:0:DAMAGE:0
