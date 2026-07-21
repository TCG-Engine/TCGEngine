# WhenDefeated
#// ASH_080 Covert Believers (Ground, 4/5) — When Defeated: create a Mandalorian token. Pre-damaged to
#// 1 HP, attacks SEC_080 (3/3) and dies to the counter (resolves inline as attacker) → 1 Mando token.

## GIVEN
CommonSetup: yrw/grw
WithP1GroundArena: ASH_080:1:4
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_T01
P2GROUNDARENACOUNT:0

---

# WhenDefeated_CreatesMandalorianToken
#// ASH_080 Covert Believers — When Defeated: create a Mandalorian token. Pre-damaged Covert Believers dies
#// attacking SOR_046 and leaves a Mandalorian token behind.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: ASH_080:1:3
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_T01
