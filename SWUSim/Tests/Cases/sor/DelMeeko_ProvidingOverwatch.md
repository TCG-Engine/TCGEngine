# OpponentEventCostsMore
#// SOR_034 Del Meeko (3/3) — "Each event an opponent plays costs 1 more."
#// P1 controls Del Meeko. P2 holds Surprise Strike (SOR_172, Event, Aggression, cost 3)
#// and has exactly 3 ready resources. The surcharge makes it cost 4, so P2 cannot pay:
#// PlayHand is a silent no-op — the event stays in hand and P2's resources are untouched.
#// (Without Del Meeko, 3 resources would play the cost-3 event — the surcharge is what blocks it.)
#// COVERAGE: offer=N/A (neither clause targets — Restore heals your own base with no choice, and the
#//           surcharge is a passive cost modifier that queues no decision) ·
#//           decline=N/A (no "you may" on either clause; a cost increase cannot be refused) ·
#//           control=StolenDelMeeko_TaxesHisNEWControllersOpponent ("an opponent" is read from the
#//           CONTROLLER; its passing control is SurchargeAppliesToOPPONENTSOnly_...) ·
#//           boundary pair=OpponentEventCostsMore (3 ready resources is one short) +
#//           SurchargeIsExactlyOne_FourResourcesPaysTheTaxedEvent (4 pays, and all 4 are spent) ·
#//           reqboundary=N/A (nothing is stored: the surcharge is recomputed from the live board every
#//           time a cost is determined, so no value crosses a serialization round trip)

## GIVEN
CommonSetup: rrk/rrk/{theirResources:3;theirHandCardIds:SOR_172}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SOR_034:1:0    # Del Meeko

## WHEN
- P2>PlayHand:0

## EXPECT
P2HANDCOUNT:1
P2RESAVAILABLE:3

---

# Restore1_HealsYourBaseOnAttack
#// Intended: "Restore 1 (When this unit attacks, heal 1 damage from your base.)" — the first of Del
#// Meeko's two clauses, which the surcharge sections never touch. P1's base starts on 3 damage; Del
#// Meeko attacks the enemy base and the Restore heals exactly 1 (3 → 2) while the enemy base takes his
#// full 3 power. Healing exactly 1, not 3 and not the whole bar, is the quantity discrimination.

## GIVEN
CommonSetup: bbk/bbk/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: SOR_034:1:0    # Del Meeko

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:2
P2BASEDMG:3

---

# SurchargeIsExactlyOne_FourResourcesPaysTheTaxedEvent
#// Intended: the surcharge is "[1 resource] more", not "unplayable" and not 2 more. The N+1 half of the
#// pair with OpponentEventCostsMore (which proves 3 is one short): P2 holds the same cost-3 Open Fire
#// and now has 4 ready resources. The play SUCCEEDS and consumes all 4 — P2RESAVAILABLE ends at 0, so
#// the taxed cost was exactly 4. The event's own effect resolves too: 4 damage auto-targets Del Meeko
#// (P1's only unit) and defeats the 3/3, which is why the ground arena empties.

## GIVEN
CommonSetup: rrk/rrk/{theirResources:4;theirHandCardIds:SOR_172}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SOR_034:1:0    # Del Meeko

## WHEN
- P2>PlayHand:0

## EXPECT
P2HANDCOUNT:0
P2RESAVAILABLE:0
P2RESCOUNT:4
P1GROUNDARENACOUNT:0

---

# SurchargeAppliesToEVENTSOnly_NotToUnits
#// Intended: "Each EVENT an opponent plays costs 1 more." The card-type gate is load-bearing. Same
#// board, same 3 ready resources, but P2's hand holds a cost-3 UNIT (SOR_161 Ardent Sympathizer,
#// Aggression, on-aspect) instead of a cost-3 event. It plays for its printed 3 and lands in the arena,
#// where OpponentEventCostsMore's identically-priced EVENT could not be paid for at all.

## GIVEN
CommonSetup: rrk/rrk/{theirResources:3;theirHandCardIds:SOR_161}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SOR_034:1:0    # Del Meeko

## WHEN
- P2>PlayHand:0

## EXPECT
P2HANDCOUNT:0
P2RESAVAILABLE:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_161

---

# SurchargeAppliesToOPPONENTSOnly_NotToDelMeekosOwnController
#// Intended: "each event AN OPPONENT plays" — Del Meeko never taxes his own side. P1 controls Del Meeko
#// and plays the very same cost-3 Open Fire with exactly 3 ready resources: it resolves for 3, all 3
#// resources are spent, and the 4 damage lands on the enemy Industrious Team (4/7, survives at 4).
#// The enemy unit also gives the target choice two candidates, so the pick is a real answer rather than
#// an auto-resolve onto Del Meeko himself.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3;myhandCardIds:SOR_172}
P1OnlyActions: true
WithP1GroundArena: SOR_034:1:0    # Del Meeko — P1's own, so no surcharge
WithP2GroundArena: LAW_124:1:0    # second candidate for Open Fire

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1HANDCOUNT:0
P1RESAVAILABLE:0
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENACOUNT:1

---

# StolenDelMeeko_TaxesHisNEWControllersOpponent
#// Intended: "an opponent" is read from Del Meeko's CONTROLLER, not his owner. Here P2 controls a Del
#// Meeko that P1 still OWNS, so P1 is now the opponent being taxed: P1's cost-3 Open Fire costs 4 and
#// cannot be paid with 3 ready resources — the play is a silent no-op, the event stays in hand and no
#// resource is spent. The passing control is SurchargeAppliesToOPPONENTSOnly_NotToDelMeekosOwnController
#// above, which plays the identical event off the identical 3 resources while P1 controls Del Meeko.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3;myhandCardIds:SOR_172}
P1OnlyActions: true
WithP2GroundArenaControlled: SOR_034:1    # P2 CONTROLS Del Meeko, P1 still OWNS him
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1RESAVAILABLE:3
P2GROUNDARENAUNIT:0:DAMAGE:0
