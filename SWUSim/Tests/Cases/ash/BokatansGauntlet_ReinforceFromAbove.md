# GrantsWhenDefeatedToken
#// ASH_063 Bo-Katan's Gauntlet (Ground, 4/5, Restore 1) — "Each OTHER friendly non-token unit gains: When
#// Defeated: create a Mandalorian token." P1's Stormtrooper (a non-token unit) attacks and dies; because
#// P1 controls ASH_063, that death creates a Mandalorian token.

## GIVEN
CommonSetup: yrw/grw
WithP1GroundArena: ASH_063:1:0
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:1:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:ASH_063
P1GROUNDARENAUNIT:1:CARDID:ASH_T01
P2GROUNDARENACOUNT:0

---

# GrantedWhenDefeated_CreatesToken
#// ASH_063 Bo-Katan's Gauntlet — "Each other friendly non-token unit gains: When Defeated: create a
#// Mandalorian token." SOR_095 dies attacking SOR_046; the granted When Defeated creates a Mandalorian token.
## GIVEN
CommonSetup: ggk/ggk
WithP1SpaceArena: ASH_063:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_T01
