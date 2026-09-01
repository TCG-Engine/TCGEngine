# BuffsOtherImperials
#// SOR_230 Blizzard Force Commander / General Veers (3/3, Imperial) —
#// "Other friendly Imperial units get +1/+1." The OTHER Imperial unit (Death
#// Trooper SOR_033, 3/3) reads 4/4; Veers himself is excluded ("other") → stays 3/3.
#// COVERAGE: offer=N/A (a continuous aura names no target and queues no decision — nothing is ever
#//           offered; the "pool" is proven instead by the three exclusion sections below) ·
#//           decline=N/A (no "you may" — the aura is not optional and cannot be declined) ·
#//           boundary=VeersDefeated_BuffEnds_ExactlyLethalDamageDefeatsTheUnit (N vs N-1 in one
#//           board: 3 damage on a printed 3 HP is lethal once the aura ends, 2 damage is not) ·
#//           control=EnemyImperial_NoBuff (the aura is scoped to Veers' own side of the table — the
#//           identical enemy Imperial reads its printed 3/3) · reqboundary=N/A (a continuous
#//           ability is recomputed from the board on every read; there is no queued decision or
#//           deferred continuation that could straddle a request — BuffsFriendlyImperialsInBoth-
#//           Arenas reads the aura with no action taken at all)

## GIVEN
CommonSetup: grk/grk
WithP1GroundArena: SOR_230:1:0    # General Veers (3/3, Imperial) — index 0
WithP1GroundArena: SOR_033:1:0    # Death Trooper (3/3, Imperial) — index 1

## WHEN

## EXPECT
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:HP:4
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3

---

# NonImperialFriendly_NoBuff
#// Intended: the IMPERIAL half of the gate is load-bearing. Veers + a friendly Imperial Dark Trooper
#// (SEC_080, 3/3, {Imperial,Droid,Trooper}) + a friendly Battlefield Marine (SOR_095, 3/3,
#// {Rebel,Trooper} — no Imperial trait). The Dark Trooper reads 4/4 (the passing control that proves
#// the aura is live on this board); the Marine stays 3/3.

## GIVEN
CommonSetup: grk/grk
WithP1GroundArena: SOR_230:1:0    # General Veers — idx 0
WithP1GroundArena: SEC_080:1:0    # Imperial Dark Trooper (Imperial) — idx 1
WithP1GroundArena: SOR_095:1:0    # Battlefield Marine (Rebel, NOT Imperial) — idx 2

## EXPECT
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:HP:4
P1GROUNDARENAUNIT:2:POWER:3
P1GROUNDARENAUNIT:2:HP:3
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3

---

# EnemyImperial_NoBuff
#// Intended: the FRIENDLY half of the gate is load-bearing. Both seats field the same Imperial Dark
#// Trooper; only the one on Veers' side of the table reads 4/4. The enemy copy stays 3/3.

## GIVEN
CommonSetup: grk/grk
WithP1GroundArena: SOR_230:1:0    # General Veers — idx 0
WithP1GroundArena: SEC_080:1:0    # friendly Imperial — idx 1 → buffed
WithP2GroundArena: SEC_080:1:0    # enemy Imperial — idx 0 → unbuffed

## EXPECT
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:HP:4
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:3

---

# BuffsFriendlyImperialsInBothArenas
#// Intended: the aura says "other friendly Imperial UNITS", not "ground units" — a ground Veers also
#// buffs the space arena. The friendly TIE/ln Fighter (SOR_225, 2/1, Imperial) reads 3/2, while the
#// friendly Alliance X-Wing (SOR_237, 2/3, Rebel) in the same arena stays 2/3 (the trait gate again,
#// now measured across the arena boundary).

## GIVEN
CommonSetup: grk/grk
WithP1GroundArena: SOR_230:1:0    # General Veers (ground)
WithP1SpaceArena: SOR_225:1:0     # TIE/ln Fighter (Imperial) — idx 0
WithP1SpaceArena: SOR_237:1:0     # Alliance X-Wing (Rebel) — idx 1

## EXPECT
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:HP:2
P1SPACEARENAUNIT:1:POWER:2
P1SPACEARENAUNIT:1:HP:3

---

# VeersDefeated_BuffEnds_ExactlyLethalDamageDefeatsTheUnit
#// Intended: the +1/+1 is a live aura, so losing Veers re-checks every buffed unit against its
#// PRINTED HP. Boundary pair in one board: two Imperial Dark Troopers (3/3), one carrying 3 damage
#// and one carrying 2 — both are 4/4 and alive while Veers is out. Veers (3/3) attacks a Consular
#// Security Force (3/7) and dies to the 3 counter-damage; the aura ends, both Troopers snap back to
#// 3/3, and only the one holding 3 damage (exactly lethal at the printed HP) is defeated. The
#// 2-damage copy survives at 3/3 — the N vs N-1 discrimination.

## GIVEN
CommonSetup: grk/grk
WithP1GroundArena: SOR_230:1:0    # General Veers — idx 0, dies in the exchange
WithP1GroundArena: SEC_080:1:3    # Imperial Dark Trooper, 3 damage — idx 1, dies when the aura ends
WithP1GroundArena: SEC_080:1:2    # Imperial Dark Trooper, 2 damage — idx 2, survives
WithP2GroundArena: SOR_046:1:0    # Consular Security Force (3/7)

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
P1DISCARDCOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:3

