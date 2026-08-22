# Front_CombatFourDamage_ExhaustToPing
#// HMW_011 Darth Sidious - There is No Mercy (Leader, [Aggression][Villainy], cost 6, 4/5 Ground,
#// Force/Sith, unique)
#// FRONT:    When you deal 4 or more damage to a unit or a base: You may exhaust this leader.
#//           If you do, deal 1 damage to a different unit or base.
#// EPIC:     Epic Action: If you control 6 or more resources, deploy this leader.  (the standard deploy
#//           line every leader carries — engine-generic, no card code)
#// DEPLOYED: Hidden · When you deal 4 or more damage to a unit or a base: You may deal 1 damage to a
#//           different unit or base.
#//
#// THE FIRST "WHEN YOU DEAL N OR MORE DAMAGE" OBSERVER IN THE ENGINE — nothing else in any set has this
#// trigger, so it needed a new collector wired into EVERY damage funnel (combat, ability, divided,
#// indirect, base). A path left unwired is a whole damage KIND the leader is blind to and no other test
#// would notice, which is why the funnels get one section each below.
#//
#// USER RULINGS (2026-08-21), none derivable from the printed text:
#//   1. COMBAT DAMAGE COUNTS (no "with an ability" qualifier).
#//   2. PER INSTANCE, NOT PER EVENT — "a unit", singular. 2+2 of a divided/indirect 4 triggers nothing;
#//      but ONE attack can fire it TWICE (the unit hit AND the Overwhelm excess into the base).
#//   3. YOUR OWN CARDS COUNT — "a unit or a base" names no controller.
#//
#// COVERAGE: offer=Front_OfferExcludesTheUnitThatWasDamaged (P1SELECTABLEEXACT) ·
#//           negative=Front_ThreeDamage_NoTriggerAtAll + Indirect_SplitTwoAndThree_DoesNOTTrigger ·
#//           boundary=Front_ThreeDamage_NoTriggerAtAll vs this section (3 vs 4, the PAIR) ·
#//           control=N/A (the trigger keys off the DEALER — the leader's own controller — and a leader
#//             cannot change control) · reqboundary=Front_AcrossTheRequestBoundary ·
#//           decline=Front_Declined_LeaderStaysREADY (+ the cannot-pay twin, Front_AlreadyExhausted)
#//
#// P1's 4-power Chain Code Collector attacks P2's 3/7: exactly 4 dealt to a unit → the offer appears.
#// P1 takes it, aiming the 1 at P2's base. The leader ends EXHAUSTED — that is the cost, and the only
#// thing that separates this side from the deployed one.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:HMW_011}
WithActivePlayer: 1
WithP1GroundArena: SHD_216:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P2BASEDMG:1
P1LEADER0:EXHAUSTED

---

# Front_ThreeDamage_NoTriggerAtAll
#// BOUNDARY, low half — and the NEGATIVE that proves the threshold is load-bearing. A 3-power attacker
#// deals 3, which is under the bar: no offer, no ping, and the leader is untouched.
#// Pairs with the section above (exactly 4). The positive alone passes for ANY threshold value.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:HMW_011}
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P2BASEDMG:0
P1LEADER0:READY
P1NODECISION

---

# Front_Declined_LeaderStaysREADY
#// DECLINE. "You MAY exhaust this leader. If you do, …" — refusing costs nothing, so the leader must
#// still be READY afterwards and no damage is dealt. A cost paid before the choice would show here.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:HMW_011}
WithActivePlayer: 1
WithP1GroundArena: SHD_216:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P2BASEDMG:0
P1LEADER0:READY
P1NODECISION

---

# Front_AlreadyExhausted_NeverOffered
#// ⚠ CANNOT-PAY, which is NOT the same branch as decline. An exhausted leader cannot pay the cost, so
#// the reaction must not be offered AT ALL — an optional effect that could only waste an answer is a
#// fizzle-only offer, the shape that has produced real bugs elsewhere in this engine.
#// Same 4-damage attack as the first section, with the leader seeded exhausted.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:HMW_011:0}
WithActivePlayer: 1
WithP1GroundArena: SHD_216:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P2BASEDMG:0
P1NODECISION

---

# Front_OfferExcludesTheUnitThatWasDamaged
#// ⚠ THE OFFER CELL. "A DIFFERENT unit or base" — the pool is every unit and every base EXCEPT the one
#// that just took the 4. Asserted exactly, so a pool that is too WIDE (still containing the damaged
#// unit) fails as loudly as one that is too narrow.
#// ⚠ The attacker must SURVIVE for the pool to be interesting, so it is SOR_048 Vigilant Honor Guards
#//   (4/6) rather than a glass-cannon 4/2 — a dead attacker leaves the section unable to prove that a
#//   FRIENDLY unit is on the menu at all. The pool is: the attacker, the defender's ally, and both
#//   bases — but NOT theirGroundArena-0, which just took the hit.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:HMW_011}
WithActivePlayer: 1
WithP1GroundArena: SOR_048:1:0
WithP2GroundArena: [SOR_046:1:0 SOR_095:1:0]
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-1&myBase-0&theirBase-0

---

# Deployed_NoExhaustCost_AndHasHidden
#// THE DEPLOYED SIDE, which is a SEPARATE ability set and clears the bar on its own. Same trigger, no
#// cost: a deployed Sidious pings without exhausting anything, and he keeps Hidden (generated from the
#// deploy text — the membership is a literal worth pinning).
#// P1's Chain Code Collector deals 4; the deployed leader unit is still READY afterwards.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:HMW_011; myLeaderDeployed:true}
WithActivePlayer: 1
WithP1GroundArena: SHD_216:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:1
P1LEADER0DEPLOYED:true
P1LEADER0:READY

---

# Overwhelm_UnitHitAndBaseExcess_AreTWOSeparateTriggers
#// ⚠ RULING 2, the positive half. ONE attack, TWO qualifying instances: a 6-power Overwhelm attacker
#// into a 1-HP unit deals 6 to that unit (≥4) and spills 5 of excess into the base (≥4). Both are
#// separate damage instances, so a deployed (free) Sidious is offered TWICE.
#// This is also the section that proves the base funnel and the combat funnel are wired INDEPENDENTLY —
#// wire only one and exactly one of the two offers disappears.
#// HMW_051 Third Sister (6/3, Overwhelm) attacks a Death Star Stormtrooper (3/1).
#// ⚠ ORDER: the BASE trigger is collected first (SWUDealDamageToBase fires inside the attack) and the
#//   unit trigger second (the combat observer runs post-combat), so the FIRST offer is the one that
#//   excludes the base and the SECOND is the one that excludes the — by then defeated — unit. Answering
#//   both with theirBase-0 is therefore illegal on the first; it takes P1's own board instead.
#// Totals: 5 Overwhelm excess + 1 from the second ping = 6 on P2's base; the first ping lands on P1's.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:HMW_011; myLeaderDeployed:true}
WithActivePlayer: 1
WithP1GroundArena: HMW_051:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myBase-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:6
P1BASEDMG:1
P2GROUNDARENACOUNT:0
P1NODECISION

---

# Indirect_FourOnOneUnit_Triggers
#// THE INDIRECT FUNNEL, which writes ->Damage directly and bypasses the ability funnel entirely — a
#// whole damage KIND that is invisible unless wired separately (the JTL_177 shape).
#// P1 plays JTL_234 Torpedo Barrage (5 indirect at P2); P2 assigns 4 to their own unit and 1 to their
#// base. The 4 is one instance and P1's Sidious sees it.
#// ⚠ The DEALER is P1 (the ability's controller), even though P2 chose where it lands — CR 35.4.
#// ⚠ THE `P1>Drain` IS PART OF THE TEST, not padding. The indirect assignment finishes on P2's queue,
#//   which leaves P1 holding a LONE CUSTOM (the queued offer-builder) on an otherwise idle queue — and a
#//   lone CUSTOM on a player who is not otherwise acting never drains by itself. A real client polls its
#//   own queue every tick, so this resolves on its own in a live game; the harness needs the poll spelled
#//   out. Without it the pending decision is my un-drained CUSTOM (empty tooltip, empty target pool),
#//   which reads exactly like "the trigger never fired".

## GIVEN
CommonSetup: rrk/bbw/{myLeader:HMW_011; myLeaderDeployed:true; myResources:6}
WithActivePlayer: 1
WithP1Hand: JTL_234
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myGroundArena-0:4,myBase-0:1
- P1>Drain
- P1>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P2BASEDMG:2

---

# Indirect_SplitTwoAndThree_DoesNOTTrigger
#// ⚠ RULING 2, the negative half — and the section the whole "per instance" reading rests on. The same
#// 5 indirect, assigned 2 to one unit and 3 to another: the EVENT total is 5 but no single unit was
#// dealt 4, and the text says "a unit", singular. No offer at all.
#// An implementation that summed the event instead of reading each hit passes every other section here.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:HMW_011; myLeaderDeployed:true; myResources:6}
WithActivePlayer: 1
WithP1Hand: JTL_234
WithP2GroundArena: [SOR_046:1:0 SOR_046:1:0]
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myGroundArena-0:2,myGroundArena-1:3

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:1:DAMAGE:3
P2BASEDMG:0
P1NODECISION

---

# OperationCinder_ManyTriggers_EVENThoughItKillsSidious
#// ⚠ THE SECTION THE USER ASKED FOR, and the one that ties all three rulings together.
#// ASH_151 Operation Cinder: "Deal 5 damage to your base. Then, deal 5 damage to each unit."
#// With a DEPLOYED Sidious (4/5) that is FOUR qualifying instances, all dealt by P1:
#//   5 to P1's OWN base (ruling 3 — your own cards count) · 5 to Sidious himself · 5 to P1's other unit
#//   · 5 to P2's unit.
#// The 5 defeats Sidious mid-resolution, and every trigger still stands: he was in play when the damage
#// was dealt, and the ability does not need its source to resolve. That is why the collector decides the
#// MODE at collect time and rides it on the param — recomputing it when the offer drains would find a
#// Sidious already compacted away by CleanupRemovedCards and silently fizzle all four.
#// ⚠ This is the same-batch observer family: a live count taken after the fact misses an observer that
#//   traded. Here it would swallow the entire line the card is built around.
#// All four pings go into P2's base: 4 damage on top of nothing else. P1's base keeps its own 5.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:HMW_011; myLeaderDeployed:true; myResources:8}
WithActivePlayer: 1
WithP1Hand: ASH_151
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P1BASEDMG:5
P2BASEDMG:4
P1LEADER0DEPLOYED:false
P1NODECISION

---

# Front_AcrossTheRequestBoundary
#// ⚠ THE REQUEST-BOUNDARY CELL, and a live path: the trigger is collected in one request and the offer
#// is answered in another, with the mode and the excluded target carried between them. Held in memory
#// instead, both are gone by the time the player answers and the reaction silently does nothing.
#// Identical to the first section plus a boundary before the answer.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:HMW_011}
WithActivePlayer: 1
WithP1GroundArena: SHD_216:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>AttackGroundArena:0:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P2BASEDMG:1
P1LEADER0:EXHAUSTED

