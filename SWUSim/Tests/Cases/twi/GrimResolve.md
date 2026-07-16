# AttackWithGrit
#// TWI_172 Grim Resolve (Event, cost 2, Aggression) — "Attack with a non-leader unit. It gains Grit for
#// this attack." SOR_046 (3/7) with 3 damage on it attacks P2's base; Grit gives +1/+0 per damage (+3) →
#// deals 6.

## GIVEN
CommonSetup: rrk/grw/{myResources:2;handCardIds:TWI_172}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:3

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:6
