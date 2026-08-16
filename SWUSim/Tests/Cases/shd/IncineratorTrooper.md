# ShootFirst
#// SHD_234 Incinerator Trooper (2-cost 2/2 ground) — "While attacking, this unit deals combat damage before
#// the defender." Attacking SOR_128 (3/1): Incinerator (2 power) deals 2 first → SOR_128 (1 HP) is defeated
#// and deals NO counter, so Incinerator survives undamaged. Without deal-first, SOR_128's 3 counter would
#// have killed it.
#// COVERAGE: offer=N/A (a constant combat-order modifier, no target pick) · decline=N/A (not optional) ·
#//           control=N/A (the modifier travels with the unit and is read from the attacker slot) ·
#//           boundary=ShootFirst (attacking → deals first) vs Defending_DamageIsSimultaneous (defending →
#//           the "while attacking" clause is off and the trade is simultaneous) ·
#//           reqboundary=N/A (resolved wholly inside one combat)

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_234:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SHD_234
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# Defending_DamageIsSimultaneous
#// Intended: the deal-first clause is gated on "WHILE ATTACKING", so it does nothing when the Incinerator
#// Trooper is the DEFENDER. P2's SOR_158 Jedha Agitator (2/1) attacks the Trooper (2/2): damage is
#// simultaneous, so BOTH die — the Agitator's 2 kills the Trooper and the Trooper's 2 kills the Agitator.
#// If the deal-first clause had applied on defense the Agitator would have died before dealing damage and
#// the Trooper would have survived, which is exactly the ShootFirst outcome one arena-slot over.
#// (The Agitator's own On Attack needs a friendly leader UNIT, and neither leader is deployed here, so it
#// contributes nothing.)

## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: SHD_234:1:0
WithP2GroundArena: SOR_158:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_234
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_158
