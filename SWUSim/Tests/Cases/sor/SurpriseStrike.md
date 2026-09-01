# AttackPlus3
#// COVERAGE: offer=Offer_AttackerPool_ReadyUnitsOnly_BothArenas (pending pick: both arenas in, the
#//           exhausted unit out) · decline=N/A (no "you may" — "Attack with a unit" is mandatory, and
#//           the no-unit edge is a silent fizzle, NoReadyUnit_EventFizzlesButIsStillPlayed, not a
#//           declinable offer) · control=N/A (the +3/+0 lives for one attack on a unit you already
#//           control; there is no persistent effect for a controller change to follow) ·
#//           boundary=Plus3_AgainstAUnit_ThenExpires (3 → 6 during the attack, back to 3 after) vs
#//           NoReadyUnit_EventFizzlesButIsStillPlayed (zero legal attackers) ·
#//           reqboundary=SimulateRequestBoundary_Plus3SurvivesTheChoose
#// SOR_220 Surprise Strike (Event, cost 2) — "Attack with a unit. It gets +3/+0 for
#// this attack." P1's only ready unit (Battlefield Marine, 3/3) is auto-chosen, gets
#// +3/+0, and — with P2 having no units — attacks P2's base for 3 + 3 = 6.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:SOR_220}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:6
P1GROUNDARENAUNIT:0:POWER:3

---

# SimulateRequestBoundary_Plus3SurvivesTheChoose
#// SOR_220 Surprise Strike — AttackPlus3 has a single ready unit, so the attacker auto-resolves and no
#// request ever ends. A second unit (SOR_046) keeps the "attack with a unit" choose interactive, and
#// the boundary goes before the answer: in production that answer arrives in a fresh process, so the
#// pending attack context AND the +3/+0-for-this-attack rider must both be serialized.
#// SEC_080 (3/3) is chosen → 3 + 3 = 6 to P2's base, and it is back to 3 power afterwards.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:SOR_220}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SOR_046:1:0    # 2nd ready unit, keeps the attacker choose interactive

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:6
P1GROUNDARENAUNIT:0:POWER:3

---

# Offer_AttackerPool_ReadyUnitsOnly_BothArenas
#// SOR_220 Surprise Strike — "Attack with a unit" can only pick a unit that is able to attack, i.e. a
#// READY one. Both existing sections answer the pick (or let it auto-resolve), which proves the branch
#// and never the pool. Here the pool is read directly, left PENDING: P1 has a ready ground unit, a ready
#// SPACE unit (the choice is not arena-scoped) and an EXHAUSTED ground unit. The exhausted unit must be
#// absent — it is the negative that proves the readiness filter is load-bearing.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:SOR_220}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0     # ready
WithP1GroundArena: SOR_046:0:0     # EXHAUSTED — must NOT be offered
WithP1SpaceArena: SOR_237:1:0      # ready, other arena
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_a_unit_to_attack_with_(+3/+0)
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0

---

# Plus3_AgainstAUnit_ThenExpires
#// SOR_220 Surprise Strike — both existing sections send the buffed attacker at the BASE, so the +3/+0
#// had never been seen resolving against a UNIT (a different damage path, and the only one where the
#// attacker takes damage back). Consular Security Force (3/7) is P1's only ready unit, so it
#// auto-resolves as the attacker; it attacks Imperial Dark Trooper (3/3) for 3 + 3 = 6, which defeats it,
#// and takes the Trooper's 3 back. "For this attack" then ends: the attacker reads its printed 3 power
#// again, with 3 damage on it, and P2's base is untouched.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:SOR_220}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0     # Consular Security Force 3/7 — sole ready unit
WithP2GroundArena: SEC_080:1:0     # Imperial Dark Trooper 3/3

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:0

---

# NoReadyUnit_EventFizzlesButIsStillPlayed
#// SOR_220 Surprise Strike — the no-valid-target edge: P1's only unit is EXHAUSTED, so there is no unit
#// to attack with. Per the standing ruling that SWUSim raises no "use it anyway?" confirmation, the
#// event is still played and its cost still paid; it simply does nothing. Guarded as a clean fizzle —
#// the card leaves hand for the discard, no decision is left dangling, nothing is damaged, and the
#// exhausted unit is neither readied nor sent into an attack.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:SOR_220}
P1OnlyActions: true
WithP1GroundArena: SOR_046:0:0     # exhausted — cannot attack
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_220
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:0
P1NODECISION
