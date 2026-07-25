# InitiativeBuff
#// SEC_108 Senator's Aide (Ground, 0/3) — "While you have the initiative, this unit gets +2/+0."
#//   P1 claims the initiative → SEC_108 becomes 2/3 for as long as P1 holds it.

## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_108:1:0

## WHEN
- P1>Claim

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:3

---

# NoInitiative_NoBuff
#// SEC_108 Senator's Aide (Ground, 0/3) — the +2/+0 only applies WHILE you have the initiative. Here
#//   P2 holds the initiative, so SEC_108 is a plain 0/3. It attacks P2's base and deals 0 damage.

## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SEC_108:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:POWER:0
P2BASEDMG:0
