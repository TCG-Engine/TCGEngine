# BonusPerDefenderDamage
#// TWI_139 Corner the Prey (Event, cost 1, Aggression/Villainy) — "Attack with a unit. It gets +1/+0 for
#// this attack for each damage on the defender at the start of this attack." SEC_080 (3 power) attacks
#// SOR_046 (3/7) which has 3 damage on it → +3/+0 → deals 6 → SOR_046's total 9 ≥ 7 → defeated. (Without
#// the bonus it would deal only 3 and SOR_046 would survive.)

## GIVEN
CommonSetup: ryk/grw/{myResources:1;handCardIds:TWI_139}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:3

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
