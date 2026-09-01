# AttackBase_Deal3
#// SOR_133 Seventh Sister "Implacable Inquisitor" (Unit, cost 5, [Aggression][Villainy],
#// Force/Imperial/Inquisitor, UNIQUE, 3/6) — "Saboteur (When this unit attacks, ignore Sentinel and
#// defeat the defender's Shields.) / When this unit deals combat damage to an opponent's base: You may
#// deal 3 damage to a ground unit that opponent controls."
#// COVERAGE: offer=Offer_OnlyThatOpponentsGROUNDUnits (menu asserted on a PENDING MZMAYCHOOSE — an
#//           enemy SPACE unit and two friendly ground units are the excluded controls, two enemy
#//           ground units are legal) · decline=Decline_NoDamageIsDealt ('-' with two legal targets;
#//           the base damage still lands, the rider damage does not) · boundary
#//           pair=RiderIsAlwaysExactlyThree_NotHerCurrentPower (the rider stays 3 while her power is
#//           lifted to 4 — the only section where the two numbers differ) + AttackBase_Deal3 (rider
#//           fires) vs AttacksAUnitNotABase_NoTriggerAtAll (no base damage → no trigger) +
#//           AttackBase_NoEnemyUnit_Fizzle (zero legal targets) · control
#//           change=ControlChange_TheCONTROLLERResolvesTheRider (owner P2 / controller P1: both "an
#//           opponent's base" and "that opponent controls" resolve from the controller's seat) ·
#//           request boundary=structural in AttackBase_Deal3, Decline_NoDamageIsDealt,
#//           RiderIsAlwaysExactlyThree_NotHerCurrentPower and
#//           ControlChange_TheCONTROLLERResolvesTheRider — the attack is one request and the rider's
#//           answer is a SEPARATE one, so the target pool is rebuilt from serialized state;
#//           Offer_OnlyThatOpponentsGROUNDUnits reads that rebuilt pool with the decision still open.
#// Saboteur is covered from both sides: Saboteur_AttacksBasePastSentinel (ignore Sentinel) and
#// Saboteur_DefeatsTheDefendersShieldBeforeDamage (defeat the defender's Shields).
#// She attacks the base (3 damage),
#// then deals 3 to the opponent's 3/7 ground unit.

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_133:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# AttackBase_NoEnemyUnit_Fizzle
#// SOR_133 Seventh Sister — base-damage rider with NO enemy ground unit to target. The "may deal 3
#// to a ground unit" has zero legal targets → SWUQueueMayChooseTarget no-ops (no dangling decision,
#// no crash). Base still takes her 3 combat damage; P1 keeps a clean turn (no pending decision).

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_133:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1NODECISION
P1GROUNDARENACOUNT:1

---

# Saboteur_AttacksBasePastSentinel
#// SOR_133 Seventh Sister — Saboteur lets her ignore Sentinel and attack the BASE even though P2
#// controls a Sentinel (SOR_063, 2/4). The base takes her 3 combat damage, which then fires the
#// rider: deal 3 to a ground unit P2 controls → the Sentinel takes 3 (survives at 4 HP). She takes
#// no counter (bases don't fight back). Proves Saboteur + the base-damage trigger compose.

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_133:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# Decline_NoDamageIsDealt
#// THE DECLINE BRANCH — "You MAY deal 3 damage to a ground unit that opponent controls." Two enemy
#// ground units are on the board so the offer is a genuine MZMAYCHOOSE (with one target it would
#// auto-resolve and there would be nothing to decline), and the player answers '-'.
#// The combat damage to the base is NOT part of the optional clause, so the base still shows her 3;
#// what must not happen is any of the 3 rider damage landing on either enemy unit, and no decision may
#// be left dangling.
#// Paired positive: AttackBase_Deal3 above.

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_133:1:0
WithP2GroundArena: [SEC_080:1:0 SOR_046:1:0]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:3
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# Offer_OnlyThatOpponentsGROUNDUnits
#// THE OFFER CELL, asserted on a PENDING decision. "Deal 3 damage to A GROUND UNIT THAT OPPONENT
#// CONTROLS" carries two restrictions and both are exercised at once:
#//   theirGroundArena-0 SEC_080  ground, enemy  → legal
#//   theirGroundArena-1 SOR_046  ground, enemy  → legal (two legal targets, or the pick auto-resolves
#//                                                       and there is no menu at all)
#//   theirSpaceArena-0  TWI_253  enemy but SPACE → EXCLUDED by "ground unit"
#//   myGroundArena-1    SOR_095  ground but MINE → EXCLUDED by "that opponent controls"
#//   myGroundArena-0    SOR_133  the Seventh Sister herself → excluded for the same reason
#// The decision is left unanswered; the resolutions live in AttackBase_Deal3 and Decline_NoDamageIsDealt.

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_133:1:0 SOR_095:1:0]
WithP2GroundArena: [SEC_080:1:0 SOR_046:1:0]
WithP2SpaceArena: TWI_253:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# AttacksAUnitNotABase_NoTriggerAtAll
#// The gate is "When this unit deals combat damage to AN OPPONENT'S BASE" — a unit attack deals no
#// base damage, so the rider must not fire. She attacks SEC_080 (3/3): it dies to her 3, she takes 3
#// back, P2's base stays on 0 and NO decision is raised.
#// This is the negative that makes the trigger's condition load-bearing; without it a rider wired to
#// "on attack" instead of "dealt damage to a base" would pass every other section in this file.

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_133:1:0
WithP2GroundArena: [SEC_080:1:0 SOR_046:1:0]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2BASEDMG:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION

---

# Saboteur_DefeatsTheDefendersShieldBeforeDamage
#// CLAUSE 1, the half the existing Saboteur section does not reach: "…and defeat the defender's
#// Shields." P2's SOR_046 (3/7) carries a Shield token (SOR_T02). Saboteur defeats the Shield as the
#// attack is declared, BEFORE combat damage, so the token cannot absorb anything and the defender
#// takes her full 3 — a shielded defender ends on 3 damage and 0 Shields, not 0 damage and 0 Shields
#// (which is what "the Shield popped instead of being defeated" would produce).
#// No base damage is dealt, so the When-deals-damage-to-a-base rider stays silent.

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_133:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:3
P2BASEDMG:0
P1NODECISION

---

# RiderIsAlwaysExactlyThree_NotHerCurrentPower
#// THE QUANTITY DISCRIMINATION. She is printed 3/6 and her rider deals 3, so in every other section
#// the two numbers are indistinguishable. Here an Experience token (SOR_T01, +1/+1) makes her 4/7:
#// the base takes 4 (her CURRENT power) while the rider still deals exactly 3 to the enemy ground
#// unit. A rider wired to "deal damage equal to this unit's power" would put 4 on the unit.

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_133:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P2BASEDMG:4
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# ControlChange_TheCONTROLLERResolvesTheRider
#// OWNER ≠ CONTROLLER. The Seventh Sister sits in P1's arena but is OWNED by P2 (the end state of a
#// take-control effect). Both halves of the trigger have to resolve for the CONTROLLER: the base she
#// damages is "an opponent's base" from P1's seat (P2's), and the ground unit the rider offers is one
#// "that opponent" controls — P2's — even though P2 is the card's owner.
#// If either half were resolved from the OWNER's seat the attack would look for P1's base and offer
#// P1's own units, which is the failure this section discriminates.

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArenaControlled: SOR_133:2
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_133
P2BASEDMG:3
P1BASEDMG:0
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION
