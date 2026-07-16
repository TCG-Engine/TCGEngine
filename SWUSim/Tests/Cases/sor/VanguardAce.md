# FirstCard_NoExperience
#// SOR_191 Vanguard Ace — guard: played as the FIRST card this phase → 0 other cards → no Experience
#// tokens. Vanguard stays 1/1 with no subcards.

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_191

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_191
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:0:POWER:1
P1SPACEARENAUNIT:0:HP:1

---

# OtherCards_GetsExperience
#// SOR_191 Vanguard Ace (Space Unit 1/1, cost 2, Cunning/Heroism) — "When Played: For each other card
#// you played this phase, give an Experience token to this unit." P1 plays two throwaways (SOR_210)
#// then Vanguard → 2 other cards this phase → Vanguard gets 2 Experience tokens (+1/+1 each) → 3/3.

## GIVEN
CommonSetup: yyw/yyw/{myResources:8}
P1OnlyActions: true
WithP1Hand: SOR_210
WithP1Hand: SOR_210
WithP1Hand: SOR_191

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_191
P1SPACEARENAUNIT:0:UPGRADECOUNT:2
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:HP:3
