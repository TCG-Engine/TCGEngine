# DefendingAttackerDebuff
#// LAW_108 Lando Calrissian (4/5, Sentinel) — While this unit is defending, the attacker gets -1/-0.
#// P2's SOR_046 (3 power) is forced to attack Lando and deals only 2 (3-1); Lando counters 4.
#// COVERAGE: offer=N/A (passive while-defending aura, no target offer of its own; the attack-target pick
#//           is core Sentinel targeting) · reqboundary=N/A (static aura, no decision inside its effect)
#//           control=N/A (the debuff reads the live attacker/defender objects of the current attack, not
#//           a seat) · boundary=ZeroPowerAttacker_NoNegativeDamage (0-power floor) vs
#//           DefendingAttackerDebuff (normal 3-power case) · decline=N/A (no "may" anywhere on the card)

## GIVEN
CommonSetup: bgw/bgw/{}
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithActivePlayer: 2
WithP1GroundArena: LAW_108:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_108
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# LandoAttacks_NoDebuff
#// LAW_108 Lando — the -1/-0 applies only WHILE HE IS DEFENDING. When Lando (4 power) attacks the enemy
#// SOR_046 (3/7) himself, no debuff to his own power: he deals a full 4, and takes the full 3 counter.

## GIVEN
CommonSetup: bgw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_108:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# CrossArenaDefend_NoDebuff
#// Guard: the debuff is Lando's OWN "while defending" aura, not an arena-wide effect. A space combat
#// between two SOR_237 X-Wings (2/3) with Lando sitting in the ground arena is unaffected — the space
#// defender takes the full 2, the space attacker takes the full 2 counter.

## GIVEN
CommonSetup: bgw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_108:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:2
P1SPACEARENAUNIT:0:DAMAGE:2

---

# BaseAttacked_NoDebuff
#// Guard: attacking the base is not "attacking Lando", so no -1/-0. Lando (P2, ground Sentinel) does not
#// restrict a SPACE attacker; P1's SOR_237 (2 power) hits P2's base for a full 2.

## GIVEN
CommonSetup: bgw/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: LAW_108:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:2

---

# ZeroPowerAttacker_NoNegativeDamage
#// LAW_108 Lando Calrissian — the -1/-0 while defending cannot take an attacker below 0 power (a
#// negative-power attack never HEALS or otherwise changes damage). A 0-power SHD_055 Moisture Farmer
#// (0/4) is forced onto the Sentinel and deals 0 to Lando; Lando's full 4 counter defeats it exactly.

## GIVEN
CommonSetup: bgw/bgw/{}
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithActivePlayer: 2
WithP1GroundArena: LAW_108:1:0
WithP2GroundArena: SHD_055:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_108
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:0
