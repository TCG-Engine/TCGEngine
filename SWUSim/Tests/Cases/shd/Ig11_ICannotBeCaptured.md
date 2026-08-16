# OnAttack_NothingDamaged_NoOffer
#// SHD_170 IG-11, I Cannot Be Captured — Unit, cost 5, 6/5, Ground, [Aggression], traits Droid/Bounty
#// Hunter, unique.
#// "If this unit would be captured, defeat him and deal 3 damage to each enemy ground unit instead.
#//  On Attack: You may deal 3 damage to a damaged ground unit."
#// COVERAGE: offer=OnAttack_OfferIsDamagedGroundUnitsOnly (the "damaged" filter and the ground-arena scope
#//           asserted together, decision left PENDING) · decline=OnAttack_Decline_NoDamageDealt ·
#//           boundary=OnAttack_NothingDamaged_NoOffer vs OnAttack_Deal3ToADamagedEnemyGroundUnit (the
#//           0-damage / 1-damage pair either side of the "damaged" filter) ·
#//           control=Capture_ControlledByTheCaptor_DamageFollowsTheControllersEnemy (IG-11 owned by P1 but
#//           controlled by P2: "each enemy ground unit" resolves against the CONTROLLER's opponent, and the
#//           defeat still returns him to his OWNER's discard) ·
#//           reqboundary=OnAttack_OfferSurvivesTheRequestBoundary
#// With nothing damaged anywhere, the "a damaged ground unit" filter empties the pool and there is no
#// offer at all — not an offer that must be declined. IG-11 is 6 power, so the base takes 6 and the
#// action completes with no decision pending.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_170:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:6
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# OnAttack_OfferIsDamagedGroundUnitsOnly
#// SHD_170 IG-11 — the rider's pool is "a DAMAGED GROUND unit", which is two filters at once:
#//   * damaged — the undamaged SOR_046 (friendly, ground index 2) and the undamaged SOR_095 (enemy, ground
#//     index 1) are absent even though they are ground units on the correct boards;
#//   * ground — the enemy SOR_237 sits in the space arena carrying 1 damage and is still absent.
#// Controller does not matter: IG-11 himself and the friendly SOR_095 are offered alongside the enemy
#// SOR_046. Three legal targets keep the pick interactive, and the decision is deliberately left PENDING
#// (nothing answers it) because EXPECT is only ever evaluated against the END state.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_170:1:1
WithP1GroundArena: SOR_095:1:1
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:2
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:1

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0
P1HASDECISION

---

# OnAttack_OfferSurvivesTheRequestBoundary
#// SHD_170 IG-11 — the rider's pool is collected while the attack resolves but is answered on a LATER HTTP
#// request. Driving an explicit request boundary between the attack and the answer proves the pending
#// decision (and its collected pool) survives the gamestate serialize/deserialize round-trip: the answer
#// still lands on the enemy SOR_046, which goes from 2 damage to 5.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_170:1:1
WithP2GroundArena: SOR_046:1:2

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:5
P2BASEDMG:6
P1NODECISION

---

# OnAttack_Deal3ToADamagedEnemyGroundUnit
#// SHD_170 IG-11 — with a damaged friendly and a damaged enemy both on offer, P1 sends the 3 at the enemy
#// SOR_046 (3/7): 2 damage becomes 5 and it survives. IG-11's own 1 damage is untouched, the friendly
#// SOR_095 keeps its 1, and the base still takes IG-11's full 6.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_170:1:1
WithP1GroundArena: SOR_095:1:1
WithP2GroundArena: SOR_046:1:2

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:1:DAMAGE:1
P2BASEDMG:6
P1NODECISION

---

# OnAttack_Decline_NoDamageDealt
#// SHD_170 IG-11 — the rider is "you MAY deal 3 damage", so a non-empty pool is still declinable. P1
#// declines; the damaged enemy SOR_046 stays at 2 and the damaged friendly SOR_095 stays at 1. This is the
#// branch that separates "declined" from "never offered" (OnAttack_NothingDamaged_NoOffer) — both end with
#// no pending decision, but only this one had a live offer.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_170:1:1
WithP1GroundArena: SOR_095:1:1
WithP2GroundArena: SOR_046:1:2

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:1:DAMAGE:1
P2BASEDMG:6
P1NODECISION

---

# Capture_ReplacedByDefeatAndThreeToEachEnemyGroundUnit
#// SHD_170 IG-11 — "If this unit would be captured, defeat him and deal 3 damage to each enemy ground unit
#// instead." This is a REPLACEMENT: the capture never happens. P2 plays SHD_120 Discerning Veteran (When
#// Played: capture an enemy non-leader ground unit) and aims it at IG-11.
#// Both halves are asserted:
#//   * IG-11 is DEFEATED, not captured — he leaves P1's ground arena into P1's discard, and the would-be
#//     captor SHD_120 ends with ZERO subcards (a real capture would have left him facedown underneath it);
#//   * "each ENEMY GROUND unit" sweeps only the captor's side of the ground arena — P2's seated SOR_046 and
#//     the just-played SHD_120 (3/4, survives at 3) each take 3, while P2's SOR_225 in the SPACE arena takes
#//     nothing (a 2/1 would have died, so its survival is the arena-scope proof) and P1's own SOR_095 and
#//     SOR_237 are untouched.

## GIVEN
CommonSetup: rrk/ggk/{theirResources:5}
WithActivePlayer: 2
WithP1GroundArena: SHD_170:1:0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2Hand: SHD_120

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:DAMAGE:0
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:DAMAGE:0
P1DISCARDUNIT:0:CARDID:SHD_170
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:1:CARDID:SHD_120
P2GROUNDARENAUNIT:1:DAMAGE:3
P2GROUNDARENAUNIT:1:UPGRADECOUNT:0
P2SPACEARENAUNIT:0:CARDID:SOR_225
P2SPACEARENAUNIT:0:DAMAGE:0

---

# Capture_ReplacementDamageIsPreventedByAShieldToken
#// SHD_170 IG-11 — the 3 damage the replacement deals is ordinary damage, so a Shield token replaces it.
#// P1's SOR_046 carries a Shield (SOR_T02) and plays SHD_120 Discerning Veteran at P2's IG-11 (the only
#// enemy ground unit, so the capture target auto-resolves). IG-11 is defeated into his owner's discard
#// instead of being captured; the shielded SOR_046 sheds the Shield and takes 0, while the unshielded
#// SHD_120 (3/4) eats the full 3 and survives.

## GIVEN
CommonSetup: ggk/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1Hand: SHD_120
WithP2GroundArena: SHD_170:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDUNIT:0:CARDID:SHD_170
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SHD_120
P1GROUNDARENAUNIT:1:DAMAGE:3
P1NODECISION

---

# Capture_ControlledByTheCaptor_DamageFollowsTheControllersEnemy
#// SHD_170 IG-11 — "each ENEMY ground unit" is resolved from IG-11's CONTROLLER, not from whoever is doing
#// the capturing. Here IG-11 is OWNED by P1 but CONTROLLED by P2, and it is P1 who plays SHD_120 Discerning
#// Veteran to capture him. The replacement therefore fires against the controller's opponent — P1's OWN
#// board — so P1's SOR_046 and the just-played SHD_120 each take 3 while P2's SOR_095 stands untouched.
#// The defeat still routes the card to its OWNER: SHD_170 ends in P1's discard, not P2's.
#// P2's controlled SHD_170 seats AFTER the regular WithP2GroundArena line, so it is ground index 1 and the
#// captor's two-candidate offer stays interactive.

## GIVEN
CommonSetup: ggk/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SHD_120
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaControlled: SHD_170:1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P1DISCARDUNIT:0:CARDID:SHD_170
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:1:CARDID:SHD_120
P1GROUNDARENAUNIT:1:DAMAGE:3
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1NODECISION
