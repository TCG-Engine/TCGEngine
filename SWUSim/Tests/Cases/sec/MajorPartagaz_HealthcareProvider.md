# OfficialAttacks_SelfBuff
#// SEC_081 Major Partagaz (Ground, 0/6) — Overwhelm + "When another friendly Official unit attacks:
#//   this unit gets +2/+2 for this phase." P1's SEC_041 (an Official, power 1) attacks P2's base →
#//   SEC_081 reacts and becomes 2/8 for the phase.

## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_081:1:0
WithP1GroundArena: SEC_041:1:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P2BASEDMG:1
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:8

---

# SelfAttack_NoBuff
#// SEC_081 Major Partagaz — the reaction is "another friendly Official"; Partagaz attacking on its own
#// does NOT count. It attacks P2's base and stays 0/6.

## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_081:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:POWER:0
P1GROUNDARENAUNIT:0:HP:6

---

# NonOfficialAttack_NoBuff
#// SEC_081 Major Partagaz — a friendly NON-Official attacking does not trigger the buff. SOR_164 Wampa
#// (Creature) attacks P2's base; SEC_081 stays 0/6.

## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_081:1:0
WithP1GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P1GROUNDARENAUNIT:0:POWER:0
P1GROUNDARENAUNIT:0:HP:6

---

# EnemyOfficialAttack_NoBuff
#// SEC_081 Major Partagaz — only a FRIENDLY Official triggers it. An enemy Official (SEC_041) attacking
#// P1's base does not buff P1's Partagaz; it stays 0/6.

## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 2
WithP1GroundArena: SEC_081:1:0
WithP2GroundArena: SEC_041:1:0

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:POWER:0
P1GROUNDARENAUNIT:0:HP:6

---

# Buff_ExpiresNextPhase
#// SEC_081 Major Partagaz — the +2/+2 is "for this phase". A friendly SEC_041 Official attacks (Partagaz
#// becomes 2/8), then both players pass to end the action phase → the buff expires and Partagaz returns
#// to its printed 0/6. (Decks seeded so the round-end resolves cleanly.)

## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 1
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_046 SOR_046]
WithP1GroundArena: SEC_081:1:0
WithP1GroundArena: SEC_041:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P2>Pass
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:0
P1GROUNDARENAUNIT:0:HP:6

---

# TwoOfficialAttacks_BuffStacks
#// SEC_081 Major Partagaz — the +2/+2 is "for this phase" and STACKS: when two friendly Official units
#//   each attack in the same phase, Partagaz gains +2/+2 twice → 4/10. (Regression: identical phase-buff
#//   tokens must not de-dupe.)

## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 1
P1OnlyActions: true
WithP1GroundArena: [SEC_081:1:0 SEC_041:1:0 SEC_237:1:0]

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AttackGroundArena:2:BASE

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:10
