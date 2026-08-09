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

---

# ExcludesITSELFFromTheOffer_EvenWhenItEntersPlayREADY
#// IBH_064 Hoth Lieutenant — "attack with ANOTHER unit" must exclude the Lieutenant itself. Normally he
#// enters exhausted, so the exclusion is incidental and unproven; here SOR_219 Sneak Attack plays him
#// for 3 less AND READY, so he is a perfectly legal attacker in every respect except the word "another".
#// The offer is exactly the two seated SOR_046 — he is absent from his own pool while standing ready at
#// index 2. Two other units are seated so the choice prompts instead of auto-resolving.
#// Sneak Attack's "play a unit from your hand" auto-resolves here (the Lieutenant is the only other card
#// in hand), so a single PlayHand drives the whole chain.

## GIVEN
CommonSetup: yyk/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: SOR_219
WithP1Hand: IBH_064
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1
P1GROUNDARENAUNIT:2:CARDID:IBH_064
P1GROUNDARENAUNIT:2:READY
