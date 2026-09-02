# AttackDefeats_DealExcess
#// COVERAGE: offer=ExcessOffer_EnemyGroundUnitsOnly · decline=AttackDefeats_DeclineExcess
#//           boundary=N/A - STRUCTURAL: "the excess damage" is a computed amount, not a threshold to
#//           straddle; the amount itself is asserted in AttackDefeats_DealExcess.
#//           control=N/A - STRUCTURAL: no owner-scoped zone in the text (no hand/deck/discard/base
#//           reference) and the trigger is self-referential ("this unit attacks and defeats").
#//           reqboundary=SimulateRequestBoundary_ExcessAmountSurvives
#//           modes=2P only - no player reference; "an enemy ground unit" is a pool, not a seat choice.
#// COVERAGE (this file holds SOR_088 Blizzard Assault AT-AT and the SOR_012 IG-88 leader sections):
#//           offer=ExcessOffer_EnemyGroundUnitsOnly (the excess prompt left PENDING and its whole legal
#//           pool read with SELECTABLEEXACT — two surviving enemy ground bodies so it cannot
#//           auto-resolve, with a FRIENDLY ground unit and an ENEMY SPACE unit seated as the excluded
#//           bodies) ·
#//           decline=AttackDefeats_DeclineExcess (the "you may" answered with '-') ·
#//           boundary=LeaderAction_MoreUnits_Buff + LeaderAction_NotMoreUnits_NoBuff (the "more units
#//           than the defending player" count boundary, 1-vs-0 buffs / 1-vs-1 does not) ·
#//           reqboundary=SimulateRequestBoundary_ExcessAmountSurvives (the excess is a trigger payload
#//           computed in combat and consumed after the prompt — 6, not 0 and not the raw power 9) ·
#//           control=N/A: the excess pool is "an enemy ground unit", recomputed at resolution time from
#//           the ACTING seat's frame (ZoneSearch('theirGroundArena')) and asserted in that frame, and
#//           the effect touches no owner-relative zone (nothing returns to a hand/deck/discard), so an
#//           owner≠controller split has no field to change; the mirror direction — a unit fighting for
#//           the other side — is the same code path with the seat swapped.
#// SOR_088 Blizzard Assault AT-AT (9/9) — "When this unit attacks and defeats a unit: You may deal
#// the excess damage from this attack to an enemy ground unit." It attacks a 3/3 (excess = 9-3 = 6),
#// defeats it, then deals 6 to the opponent's other ground unit (a 3/7, which survives at 6 damage).

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_088:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:6
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# AttackDefeats_DeclineExcess
#// SOR_088 Blizzard Assault AT-AT — the excess deal is "you may": declining leaves the other enemy
#// unit untouched. Same setup as the deal-excess test, but the player declines → SOR_046 stays at 0
#// damage (only the 3/3 it defeated is gone).

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_088:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Deployed_GrantsRaid1
#// SOR_012 IG-88 — deployed leader unit's passive: Each OTHER friendly unit gains Raid 1
#// (+1/+0 while attacking). IG-88 is deployed (ground); a friendly space unit (Distant
#// Patroller, 2 power) attacks the enemy base and deals 2 + 1 (Raid) = 3. (The Raid grant is
#// already implemented in GetConditionalKeyword_Raid_Value — this test verifies it.)

## GIVEN
CommonSetup: grk/bbk/{
  myLeader:SOR_012
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1SpaceArena: SOR_060:1:0     # gains Raid 1 from deployed IG-88

## WHEN
- P1>DeployLeader
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:3

---

# LeaderAction_MoreUnits_Buff
#// SOR_012 IG-88 — Leader Action [Exhaust]: Attack with a unit. If you control more units than
#// the defending player, the attacker gets +1/+0 for this attack. P1 controls 1 unit, P2 controls 0
#// → bonus applies. The 3-power unit attacks the base for 3+1=4. The +1 is one-shot (POWER stays 3).

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_012;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:POWER:3
P1LEADER:EXHAUSTED

---

# LeaderAction_NotMoreUnits_NoBuff
#// SOR_012 IG-88 — when you do NOT control more units than the defending player, no +1/+0.
#// P1 controls 1 unit, P2 controls 1 unit (equal) → no bonus. The 3-power unit attacks the base
#// (chosen over the enemy unit) for 3 damage.

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_012;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:EXHAUSTED

---

# ExcessOffer_EnemyGroundUnitsOnly
#// SOR_088 Blizzard Assault AT-AT — OFFER axis. "You may deal the excess damage to AN ENEMY GROUND
#// UNIT" names two restrictions and neither is observable by answering: a branch answer only proves the
#// target you picked was legal. The AT-AT (9/9) defeats a 3/3, and the excess prompt is left PENDING so
#// the whole legal pool can be read. The board carries an N+1 fixture on purpose — TWO surviving enemy
#// ground bodies (so the pick cannot auto-resolve), plus a FRIENDLY ground unit and an ENEMY SPACE unit
#// as the two excluded bodies. The pool must be exactly the two enemy ground units: "enemy" drops the
#// friendly, "ground" drops the space one, and "unit" drops both bases.
#// (Indexes are POST-cleanup — the defeated 3/3 has already left P2's arena when the prompt is built.)

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_088:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1
P2GROUNDARENACOUNT:2
P2SPACEARENACOUNT:1
P1GROUNDARENACOUNT:2

---

# SimulateRequestBoundary_ExcessAmountSurvives
#// SOR_088 Blizzard Assault AT-AT — REQUEST-BOUNDARY axis. The excess is computed during combat and
#// carried on the trigger as a payload, then the "you may deal it" prompt ends the request: in
#// production the amount is re-read by a FRESH process from the serialized state alone. Mirrors
#// AttackDefeats_DealExcess (9 power − 3 remaining HP = 6) with the boundary inserted before the
#// answer, so a payload that did not survive serialization would land 0 (or the raw power, 9) instead
#// of 6. Two surviving enemy bodies keep the pick interactive across the boundary.

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_088:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:DAMAGE:6
P1GROUNDARENAUNIT:0:DAMAGE:3
