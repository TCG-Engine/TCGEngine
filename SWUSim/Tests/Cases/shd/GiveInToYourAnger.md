# DamageDefeatsTarget_NoAttack
#// COVERAGE: offer=MultipleTargets_ControllerChoosesUnit (the COMPELLED unit's attack pool is picked by
#//           its controller when more than one enemy unit is legal) · decline=N/A (a mandatory event, and
#//           the compulsion is "must ... if able") · control=N/A (the compulsion is stamped on the unit and
#//           read by its own controller; no take-control interaction) ·
#//           boundary=ForcesAttackAgainstUnit / NoUnitTarget_AttacksBase / Exhausted_CompulsionLapses /
#//           DamageDefeatsTarget_NoAttack (the four "if able" outcomes) plus
#//           CompulsionLastsOnlyOneAction (the DURATION boundary — the action AFTER the forced one is free
#//           again, and may even attack the base) · reqboundary=CompulsionLastsOnlyOneAction (the stamp
#//           has to survive the play→forced-attack→next-action request chain and then clear)
#// SHD_144 — if the 1 damage defeats the chosen unit, there is nothing left to force an attack with. P1
#// plays Give In to Your Anger on P2's SOR_128 (3/1); the 1 damage defeats it outright, so no forced
#// attack occurs — P1's unit and base are untouched.

## GIVEN
CommonSetup: rrk/rrk
WithP1Resources: 1
WithP1Hand: SHD_144
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:0

---

# Exhausted_CompulsionLapses
#// SHD_144 — "...must be an attack action with that unit, if able." The compulsion is conditional on the
#// unit being ABLE to attack. Here P2's SOR_046 is already exhausted, so it cannot attack: it still takes
#// the 1 damage, but no forced attack happens (the compulsion lapses) — P1's unit and base are untouched.

## GIVEN
CommonSetup: rrk/rrk
WithP1Resources: 1
WithP1Hand: SHD_144
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:0

---

# ForcesAttackAgainstUnit
#// SHD_144 Give In to Your Anger (Event, cost 1, Villainy/Aggression) — "Deal 1 damage to an enemy unit.
#// Its controller's next action this phase must be an attack action with that unit, if able. It must
#// attack a unit, if able." P1 plays it targeting P2's SOR_046 (3/7); that unit takes 1, then on P2's
#// forced next action it attacks P1's only unit (SOR_046 3/7) — NOT P1's base. Single friendly unit, so
#// the attack auto-resolves. P2's unit ends exhausted with 1+3=4 damage (SHD_144 + 3 counter); P1's unit
#// takes 3; P1's base is untouched (the compulsion forces a unit attack, not a base attack).

## GIVEN
CommonSetup: rrk/rrk
WithP1Resources: 1
WithP1Hand: SHD_144
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:DAMAGE:3
P1BASEDMG:0

---

# MultipleTargets_ControllerChoosesUnit
#// SHD_144 — "It must attack a unit, if able." When the compelled unit has more than one enemy unit it
#// could attack, its controller CHOOSES which (but must pick a unit, not the base). P1 has two SOR_046
#// (3/7); P1 plays Give In to Your Anger on P2's SOR_046, which is then forced to attack — P2 chooses the
#// second of P1's units (idx 1). That unit takes 3; P1's first unit is untouched; P2's unit ends exhausted.

## GIVEN
CommonSetup: rrk/rrk
WithP1Resources: 1
WithP1Hand: SHD_144
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:0

---

# NoUnitTarget_AttacksBase
#// SHD_144 — "...next action must be an attack action with that unit, if able. It must attack a unit, if
#// able." When the compelled unit CAN'T attack a unit (its controller's opponent has no units) but CAN
#// attack the base, it must still attack — hitting the base. P1 controls no units; P1 plays Give In to
#// Your Anger on P2's SOR_046 (3/7), which is forced to attack and, having no unit to hit, strikes P1's
#// base for 3. P2's unit ends exhausted with only the 1 SHD_144 damage (a base deals no counter).

## GIVEN
CommonSetup: rrk/rrk
WithP1Resources: 1
WithP1Hand: SHD_144
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:1
P1BASEDMG:3

---

# CompulsionLastsOnlyOneAction
#// Intended: "its controller's NEXT ACTION this phase must be an attack action with that unit" — the
#// compulsion is spent by exactly one action, and the "must attack a unit" rider goes with it. P1 plays
#// Give In to Your Anger on P2's SOR_046 (3/7); that unit takes 1 and is forced to attack P1's lone
#// SOR_046 (auto-targeted), ending exhausted on 1+3=4 damage while P1's unit takes 3. P1 then passes and
#// P2 takes a SECOND, entirely free action: attacking P1's BASE with the untouched SOR_128 (3/1). That
#// base hit is the proof the compulsion is over — if it still applied, the second action would have been
#// forced through SOR_046 and forbidden from hitting a base.

## GIVEN
CommonSetup: rrk/rrk
WithP1Resources: 1
WithP1Hand: SHD_144
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>Pass
- P2>AttackGroundArena:1:BASE

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:CARDID:SOR_128
P2GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:3
P1BASEDMG:3
