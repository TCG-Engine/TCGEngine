# CheapFriendlyDamaged_OffersOneDamageToAnEnemy
#// HMW_045 Logray, Bright Tree Shaman (Command/Aggression, Ewok, 2-cost 1/5 Ground) —
#// "When another friendly unit that costs 3 or less is dealt damage: You may deal 1 damage to an
#// enemy unit."
#// COVERAGE: offer=CheapFriendlyDamaged_OffersOneDamageToAnEnemy (the enemy-only pool asserted while
#//           pending) · negative=ExpensiveFriendlyDamaged_NoTrigger (cost 4, the boundary partner) +
#//           LograyHimselfDamaged_NoTrigger ("another") + EnemyUnitDamaged_NoTrigger ("friendly") ·
#//           boundary pair=cost 3 fires vs cost 4 does not · decline=DeclinedOffer_NoDamage ·
#//           control=StolenLogray_ObservesTheNEWControllersUnits ·
#//           reqboundary=OfferSurvivesTheRequestBoundary
#// ⚠ COST is the PRINTED cost, and the clause has NO "and survives" — see
#// DamagedUnitIsDEFEATED_StillTriggers, which is the cell most likely to be got wrong.
#// P2's 2-power SOR_207 attacks P1's SOR_095 (cost 2, 3/3): the Marine takes 2 and SURVIVES, which
#// triggers Logray. The attacker DIES in the trade, so by the time the offer is built the enemy board
#// has compacted to just SOR_046 at index 0 — which is the point of queuing the offer instead of
#// building it inline (an inline pool would have offered the dead attacker).
#// ⚠ The offer lands on P1's queue while P2 is the acting player, so P1 must POLL (`Drain`) before it
#// surfaces — the harness equivalent of P1's client fetching state.

## GIVEN
CommonSetup: grw/bgw/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: HMW_045:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_207:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>AttackGroundArena:0:1
- P1>Drain
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:2
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# OfferPool_EnemyUnitsOnly
#// HMW_045 — the offer itself. Left UNANSWERED so the pending pool is the assertion: "an enemy unit"
#// names no arena, so BOTH enemy arenas are in and every friendly unit — including the just-damaged
#// Marine and Logray himself — is out. The attacker here (LAW_124, 4/7) SURVIVES, so all three enemy
#// units are still on the board when the pool is built.

## GIVEN
CommonSetup: grw/bgw/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: HMW_045:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P2>AttackGroundArena:0:1
- P1>Drain

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1&theirSpaceArena-0

---

# ExpensiveFriendlyDamaged_NoTrigger
#// HMW_045 — the boundary partner. The damaged friendly unit is SOR_046 at PRINTED cost 4, one over the
#// gate, so no offer is raised at all. P1NODECISION is the load-bearing assertion.

## GIVEN
CommonSetup: grw/bgw/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: HMW_045:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_207:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>AttackGroundArena:0:1
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:2
P1NODECISION

---

# LograyHimselfDamaged_NoTrigger
#// HMW_045 — "ANOTHER friendly unit". Logray costs 2, so he would qualify on cost alone; damage dealt
#// to HIM must not trigger his own ability. He is 1/5 and takes 2, surviving, so the only reason for
#// silence is the "another" exclusion.

## GIVEN
CommonSetup: grw/bgw/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: HMW_045:1:0
WithP2GroundArena: SOR_207:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>AttackGroundArena:0:0
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_045
P1GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION

---

# EnemyUnitDamaged_NoTrigger
#// HMW_045 — "FRIENDLY". A cheap ENEMY unit taking damage must not trigger Logray. P1's SOR_095 attacks
#// P2's SOR_207 (cost 2): the enemy is damaged and dies, and P1 is offered nothing.

## GIVEN
CommonSetup: grw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: HMW_045:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_207:1:0

## WHEN
- P1>AttackGroundArena:1:0
- P1>Drain

## EXPECT
P2GROUNDARENACOUNT:0
P1NODECISION

---

# DamagedUnitIsDEFEATED_StillTriggers
#// HMW_045 — ⚠ THE KEY CELL. The clause is "is dealt damage", with NO "and survives", so it fires even
#// when that damage DEFEATS the unit. P2's LAW_124 (4 power) kills P1's SOR_095 (3 HP) outright; Logray
#// must still offer, and the 1 damage still lands on an enemy.
#// Most damage observers in this engine sit behind a survived-gate, so an implementation that follows
#// the neighbours rather than the printed text fails exactly here.

## GIVEN
CommonSetup: grw/bgw/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: HMW_045:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>AttackGroundArena:0:1
- P1>Drain
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_045
P2GROUNDARENAUNIT:1:CARDID:SOR_046
P2GROUNDARENAUNIT:1:DAMAGE:1

---

# DeclinedOffer_NoDamage
#// HMW_045 — "YOU MAY". Declining deals nothing and leaves no dangling decision.

## GIVEN
CommonSetup: grw/bgw/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: HMW_045:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_207:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>AttackGroundArena:0:1
- P1>Drain
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# StolenLogray_ObservesTheNEWControllersUnits
#// HMW_045 — "friendly" and "enemy" are both relative to whoever CONTROLS Logray. P1 owns him, P2
#// controls him (`WithP2GroundArenaControlled: HMW_045:1`). A cheap unit of P2's is damaged, so from
#// the new controller's seat that IS a friendly unit, and the 1 damage must go to one of P1's units.
#// P1's SOR_095 attacks P2's SOR_207 (cost 2, damaged and defeated). The controlled Logray seeds
#// AFTER the plain unit, so SOR_207 is P2's ground index 0.
#// The Marine takes 2 counter-damage and then Logray's 1 — exactly its 3 HP — so it DIES. That is the
#// discriminator: without the grant it survives at 2 damage, with it the arena empties.

## GIVEN
CommonSetup: grw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArenaControlled: HMW_045:1
WithP2GroundArena: SOR_207:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P2>Drain
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1

---

# OfferSurvivesTheRequestBoundary
#// HMW_045 — the request-boundary cell: the pending offer must survive serialization between the damage
#// that raised it and the answer that resolves it.

## GIVEN
CommonSetup: grw/bgw/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: HMW_045:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_207:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>AttackGroundArena:0:1
- P1>Drain
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:1
