# SOR_108 Vanguard Infantry (1/2) — When Defeated: you may give an Experience token to
# a unit. P1's Vanguard attacks P2's Battlefield Marine (3/3) and dies to the 3 counter-
# damage. Its When Defeated triggers: YES, then give the token to P1's Consular Security
# Force (SOR_046, stable at index 0) → power 3 → 4.

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0    # Experience recipient — index 0 (stays put)
WithP1GroundArena: SOR_108:1:0    # attacker that dies — index 1
WithP2GroundArena: SOR_095:1:0    # defender (3/3)

## WHEN
- P1>AttackGroundArena:1:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
