# Deal1x3
#// LOF_171 Heavy Blaster Cannon — When Played: may deal 1 to a ground unit, then 1, then 1 (same unit).
#// Played onto SOR_095, it deals 3 total to the enemy 3/7.

## GIVEN
CommonSetup: rrk/ggw/{myResources:4;handCardIds:LOF_171}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0    # attach to the friendly host (enemy is now a legal host too, CR 2.e)
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# OptionalPass_NoDamage
#// LOF_171 Heavy Blaster Cannon — the When Played "may deal 1 damage..." is optional. Played onto SOR_095,
#// P1 declines, so no damage is dealt to anyone; the cannon stays attached. Intended: "is optional and can be
#// passed."

## GIVEN
CommonSetup: rrk/ggw/{myResources:4;handCardIds:LOF_171}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0    # attach to the friendly host (enemy is now a legal host too, CR 2.e)
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# ThreeHits_PopsTwoShields
#// LOF_171 Heavy Blaster Cannon — the three separate 1-damage hits interact with Shields: a defender with two
#// Shield tokens loses one Shield to each of the first two hits, then takes 1 damage from the third. Intended: #// "deals 1 damage to the same ground unit three times" (Reinforcement Walker with 2 shields → 1 damage).

## GIVEN
CommonSetup: rrk/ggw/{myResources:4;handCardIds:LOF_171}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0    # attach to the friendly host (enemy is now a legal host too, CR 2.e)
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# DefeatedAfterFirstHit
#// LOF_171 Heavy Blaster Cannon — if the target is defeated by the first hit, the remaining two hits fizzle
#// harmlessly (same unit is gone). Target is SOR_046 pre-damaged to 1 remaining HP; the first hit defeats it.
#// Intended: "works if the unit is defeated after the first damage."

## GIVEN
CommonSetup: rrk/ggw/{myResources:4;handCardIds:LOF_171}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:6

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0    # attach to the friendly host (enemy is now a legal host too, CR 2.e)
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
