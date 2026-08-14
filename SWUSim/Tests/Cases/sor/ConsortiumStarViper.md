# WithInitiative_AttackOnBaseHealsTwoFromOwnBase
#// SOR_112 Consortium StarViper (Space, 3/3, Command) — "While you have the initiative, this unit
#//   gains Restore 2." P1 holds the (unclaimed) initiative counter, so the StarViper has Restore 2:
#//   attacking the enemy base heals 2 from P1's base (5 → 3) while dealing 3 to P2's base.
#// COVERAGE: offer=AttackingAUnitAlsoHeals (the attack-target picker accepts the explicit unit answer —
#//           an out-of-pool answer throws, so the pass proves the pool; the ability itself raises no
#//           choice) · reqboundary=RegainedInitiativeNextPhase_HealsAgain (the gate is re-read from
#//           serialized state across a full regroup round-trip) · control=ControlledEnemyViper_HealsControllersBase
#//           (owner/controller split: the heal follows the CONTROLLER's base and the CONTROLLER's
#//           initiative) · boundary pair=this section vs NoInitiative_NoHeal +
#//           OpponentClaimsMidPhase_NoHeal (gate on/off) · decline=N/A (Restore is a static keyword
#//           grant — there is no "you may" anywhere on the card)

## GIVEN
CommonSetup: ggw/ggk/{myBaseDamage:5}
WithP1SpaceArena: SOR_112:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:3
P2BASEDMG:3

---

# AttackingAUnitAlsoHeals
#// Restore triggers when the unit ATTACKS — not only on base attacks. P1 (initiative) attacks the
#//   enemy Cartel Spacer (2/3): the Spacer dies to 3 power, the StarViper takes 2 back, and P1's base
#//   still heals 2 (5 → 3). The explicit unit answer also proves the target pool contained the unit.

## GIVEN
CommonSetup: ggw/ggk/{myBaseDamage:5}
WithP1SpaceArena: SOR_112:1:0
WithP2SpaceArena: SOR_178:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P1BASEDMG:3
P2SPACEARENACOUNT:0
P2DISCARDCOUNT:1
P1SPACEARENAUNIT:0:DAMAGE:2

---

# NoInitiative_NoHeal
#// The gate is OFF when the opponent holds the initiative: P2 has the (unclaimed) counter, P1 attacks
#//   with the StarViper — the enemy base takes 3 but P1's base stays at 5 (no Restore).

## GIVEN
CommonSetup: ggw/ggk/{myBaseDamage:5}
WithInitiativePlayer: 2
WithActivePlayer: 1
WithP1SpaceArena: SOR_112:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:5
P2BASEDMG:3

---

# OpponentClaimsMidPhase_NoHeal
#// The gate is live, not phase-locked: P1 starts the phase holding the (unclaimed) initiative
#//   counter, but P2 (acting first) claims it. The StarViper's attack after the claim does NOT heal
#//   (P1 no longer has the initiative at attack time). P2 acts first so the claim doesn't close the
#//   phase (a pass followed by a claim would end the action phase).

## GIVEN
CommonSetup: ggw/ggk/{myBaseDamage:5}
WithInitiativePlayer: 1
WithActivePlayer: 2
WithP1SpaceArena: SOR_112:1:0

## WHEN
- P2>Claim
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:5
P2BASEDMG:3

---

# RegainedInitiativeNextPhase_HealsAgain
#// The other direction: P2 holds the counter, P1 claims it mid-phase, the round crosses regroup, and
#//   in the next action phase P1 (first player, initiative claimed) attacks — Restore 2 is back on
#//   (5 → 3). Decks are seeded so the regroup draw doesn't hit an empty deck.

## GIVEN
CommonSetup: ggw/ggk/{myBaseDamage:5}
WithInitiativePlayer: 2
WithActivePlayer: 1
WithP1SpaceArena: SOR_112:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>Claim
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:3
P2BASEDMG:3

---

# NoOverheal_BaseStopsAtZero
#// Restore 2 on a base with only 1 damage heals to exactly 0, never negative.

## GIVEN
CommonSetup: ggw/ggk/{myBaseDamage:1}
WithP1SpaceArena: SOR_112:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:0
P2BASEDMG:3

---

# ControlledEnemyViper_HealsControllersBase
#// Owner/controller split (the end state after a control-take): P1 CONTROLS a StarViper OWNED by P2.
#//   Restore reads the CONTROLLER — P1's initiative satisfies the gate and P1's base (not the
#//   owner's) is healed (5 → 3) while the enemy base takes the 3 combat damage.

## GIVEN
CommonSetup: ggw/ggk/{myBaseDamage:5}
WithP1SpaceArenaControlled: SOR_112:2

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:3
P2BASEDMG:3
