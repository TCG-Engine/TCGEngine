# Decline_Reprint092
#// IBH_092 Hoth Lieutenant (reprint of IBH_064) — the attack is optional. Decline → no attack happens.

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: IBH_092
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:0
P1GROUNDARENAUNIT:0:READY
P1NODECISION

---

# WhenPlayed_AttackAnotherPlusTwo
#// IBH_064 Hoth Lieutenant (Ground, 3/4, Aggression/Villainy, cost 4) — When Played: you may attack with
#//   ANOTHER unit; it gets +2/+0 for this attack. P1 has a ready 3-power unit; it attacks the enemy base
#//   for 3+2 = 5. (The Lieutenant itself just entered exhausted and is excluded.)

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: IBH_064
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION
