# BuffAndDealFirst_ExpireAfterAttack
#// COVERAGE: offer=N/A - ⚠ SITUATIONAL GAP: "attack with a unit" offers the caster's ready units and
#//           no section asserts that pool. Open cell.
#//           decline=N/A - STRUCTURAL: printed mandatory.
#//           boundary=KillDefender_NoCounterDamage / SurvivedDefender_CounterDamage (the deal-first
#//           ordering only matters when the defender dies, so the pair straddles lethality)
#//           control=N/A - STRUCTURAL: an Event with a fixed caster and no owner-scoped zone.
#//           reqboundary=SimulateRequestBoundary_DealFirstAndBuffSurvive
#//           modes=2P only - no player reference, no friendly/enemy wording.
#// SOR_217 Shoot First — "It gets +1/+0 for this attack and deals its combat damage before the
#// defender." Both halves are ATTACK-duration registry effects (the SOR_217 +1/+0 STAT_BUFF and the
#// SHOOT_FIRST deal-first marker), dropped by SWUExpireTurnEffects('attack') when combat resolves.
#// Battlefield Marine SOR_095 (3 power) gets +1 → 4, deals first and defeats the shielded SOR_207
#// (so it takes NO counter, DAMAGE:0), and afterward is back to its base power 3 (buff expired).

## GIVEN
CommonSetup: gyw/gyw/{myResources:1;handCardIds:SOR_217}
WithP1GroundArena: SOR_095
WithP2GroundArena: SOR_207

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:POWER:3

---

# KillDefender_NoCounterDamage
## GIVEN
CommonSetup: gyw/gyw/{myResources:1;handCardIds:SOR_217}
WithP1GroundArena: SOR_095
WithP2GroundArena: SOR_207

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:0

---

# SurvivedDefender_CounterDamage
## GIVEN
CommonSetup: gyw/gyw/{myResources:1;handCardIds:SOR_217}
WithP1GroundArena: SOR_207:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENACOUNT:0

---

# SimulateRequestBoundary_DealFirstAndBuffSurvive
#// SOR_217 Shoot First — the attack-target pick ends the request in production, so combat resolves in a
#// fresh process. Both attack-duration halves written at play time (the +1/+0 STAT_BUFF and the
#// SHOOT_FIRST deal-first marker) must be serialized, not left in the transient $gShootFirstPending, or the
#// attacker takes the counter it should have dodged. Mirrors BuffAndDealFirst_ExpireAfterAttack with the
#// boundary inserted before the answer.

## GIVEN
CommonSetup: gyw/gyw/{myResources:1;handCardIds:SOR_217}
WithP1GroundArena: SOR_095
WithP2GroundArena: SOR_207

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:POWER:3
