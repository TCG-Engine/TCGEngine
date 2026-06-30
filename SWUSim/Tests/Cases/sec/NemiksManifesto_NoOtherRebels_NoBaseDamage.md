# SEC_156 Nemik's Manifesto — fizzle guard: with no OTHER friendly Rebel unit, the granted When
# Defeated deals 0. Host A = SEC_080 + Nemik's (4/4, Rebel-by-grant) attacks the 8/8 SOR_039 and dies.
# The only other friendly unit is D = SEC_080 (non-Rebel, no Nemik's) → 0 other Rebels → no base damage.
# (A's own granted Rebel doesn't self-count — it's "OTHER friendly Rebel units".)

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:SEC_156
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_039:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2BASEDMG:0
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
