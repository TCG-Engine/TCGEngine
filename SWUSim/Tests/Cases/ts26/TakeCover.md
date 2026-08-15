# HealAndShield
#// TS26_47 Take Cover (Event, cost 3, Vigilance) — Heal up to 3 damage from a unit and give it a Shield.
#// LAW_124 (4/7) with 3 damage is healed to 0 damage and shielded.
## GIVEN
CommonSetup: bbw/rrk/{myResources:3;handCardIds:TS26_47}
WithP1GroundArena: LAW_124:1:3
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Heal3
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
#// (Amount answer added 2026-08-14: "heal up to 3" now offers the amount, Heal0..Heal3.
#// USER RULING — the target pick is mandatory and an amount of zero is the soft pass.)

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
- P1>AnswerDecision:Heal3

## EXPECT
P1RESAVAILABLE:1
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# SoftPass_Heal0_ShieldStillGiven
#// USER RULING (2026-08-14) applied to every "up to N" effect: the TARGET pick is mandatory and the
#// soft pass is an amount of ZERO. Here that matters because the Shield is UNCONDITIONAL — "heal up to
#// 3 damage from a unit AND give a Shield token to it" — so a player who wants the Shield without
#// healing (e.g. to keep damage on a unit that cares about it) picks Heal0 and still gets the token.

## GIVEN
CommonSetup: bbw/bbw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:3
WithP1Hand: TS26_47

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Heal0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1NODECISION
