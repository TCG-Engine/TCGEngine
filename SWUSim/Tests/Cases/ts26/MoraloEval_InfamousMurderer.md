# BaseCombatDamageDeals1
#// TS26_73 Moralo Eval (Unit 3/2) — Shielded + "When your base is dealt combat damage: you may deal 1
#// damage to a unit." When P2's SEC_080 attacks P1's base, Moralo's controller (P1) deals 1 to SEC_080.
## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1GroundArena: TS26_73:1:0
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1BASEDMG:3

---

# DeclineNoDamage
#// TS26_73 Moralo Eval (Unit 3/2) — "When your base is dealt combat damage: you may deal 1 damage to a
#// unit." DECLINE branch: P1 declines, so SEC_080 takes no damage; the base still takes its 3.
## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1GroundArena: TS26_73:1:0
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:3

---

# OverwhelmExcessToYourBaseCountsAsCombatDamage
#// TS26_73 Moralo Eval — Overwhelm's spillover IS combat damage, so it opens the window. P2's Wampa
#// (SOR_164, 4 power, Overwhelm) attacks P1's 1/1 Battle Droid token: 1 kills it and the 3 excess hits
#// P1's base, and Moralo answers by putting 1 damage on the Wampa.

## GIVEN
CommonSetup: yyk/rrk
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: [TS26_73:1:0 TS26_T01:1:0]
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:1
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# TheOPPONENTSBaseTakingCombatDamageDoesNotTrigger
#// TS26_73 Moralo Eval — "when YOUR base is dealt combat damage". P1's own SEC_080 attacking P2's base
#// for 3 is the wrong base entirely: no offer appears and P2's SOR_095 is untouched.

## GIVEN
CommonSetup: yyk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [TS26_73:1:0 SEC_080:1:0]
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# ABILITYDamageToYourBaseDoesNotTrigger
#// TS26_73 Moralo Eval — "dealt COMBAT damage". P1 plays Urgent Mission (TS26_64), which deals 2 to their
#// OWN base: the base is damaged, but by an ability, so the window never opens and no offer is raised.
#// Discriminating against a naive "your base took damage" reading, which this would satisfy.

## GIVEN
CommonSetup: yyk/rrk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TS26_64
WithP1GroundArena: TS26_73:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
