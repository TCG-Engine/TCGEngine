# EnemyUnit_TakesOneDamageAndIsExhausted
#// HMW_207 Maim (Event, cost 1, [Cunning][Villainy], Tactic) — "Deal 1 damage to a unit and exhaust it."
#// COVERAGE: offer=Offer_SpansBothSidesBothArenasTokensAndLeaderUnits (SELECTABLEEXACT: the pool is
#//           UNQUALIFIED — both controllers, both arenas, token units and leader units all included)
#//           decline=N/A (the printed text carries no "you may"/"up to"; the choose is a mandatory
#//           MZCHOOSE and the player is committed once the event resolves)
#//           boundary=ExactlyOneDamage_ThreeHpAtOneSurvives + LethalBoundary_ThreeHpAtTwoIsDefeated
#//           (the PAIR is what pins the amount at exactly 1 — the survivor alone passes for any
#//           amount >= 1, the death alone passes for any amount >= 1 as well)
#//           control=N/A (an event has a fixed caster; it names no owner-scoped zone — no "your hand /
#//           your deck / your discard" wording — and it can neither be stolen nor re-resolve later,
#//           so owner-vs-controller is unreachable for this card)
#//           reqboundary=RequestBoundary_TargetSurvivesTheDecision
#//           modes=2P only (no player reference — the text names no opponent or player — and no
#//           friendly/enemy wording, so all three formats share one code path)
#//
#// ⚠ PREVIEW SET: HMW is absent from card-specific-rulings.md, so the two readings below are reasoned
#// from the CR + the closest released analogue (JTL_230 Electromagnetic Pulse, "Deal 2 damage to a
#// Droid or Vehicle unit and exhaust it" — the same sentence shape) and are FLAGGED as assumptions:
#//   (1) The two halves are joined by "and", NOT "If you do", so the exhaust is UNCONDITIONAL — a
#//       Shield absorbing the damage does not stop it (see ShieldedTarget_… below). Same reading as
#//       HMW_202 Inferno Squad's "damage AND give a token".
#//   (2) "a unit" is unqualified, so it reaches friendly units, both arenas, token units and leader
#//       units (CR: an unqualified target word names no controller).
#//
#// Positive: two legal enemy targets (so the choose really prompts rather than auto-resolving); the
#// chosen 3/3 takes exactly 1 and is exhausted, the bystander is untouched and still ready.

## GIVEN
CommonSetup: yyk/rrk/{
  myResources:2
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_207
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:CARDID:SOR_046
P2GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENAUNIT:1:READY
P1DISCARDCOUNT:1

---

# FriendlyUnit_IsALegalTarget_UnqualifiedAUnit
#// HMW_207 — "a unit" carries NO controller qualifier, so your OWN units are legal targets. This is the
#// section that reds if the target pool is ever narrowed to the enemy board.

## GIVEN
CommonSetup: yyk/rrk/{
  myResources:2
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_207
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:READY

---

# Offer_SpansBothSidesBothArenasTokensAndLeaderUnits
#// HMW_207 — the OFFER cell. Answering a target only proves the branch; this leaves the choose pending
#// and asserts the pool itself. Six units across four arenas: a friendly ground unit, a friendly
#// DEPLOYED LEADER unit (SOR_016 Thrawn — the `Leader Unit` ZoneSearch token, historically the easiest
#// class to drop silently), a friendly space unit, an enemy ground unit, an enemy TOKEN unit
#// (TWI_T02 — the `Token Unit` token, the other easily-dropped class) and an enemy space unit.
#// Every one of them is legal; nothing is excluded.

## GIVEN
CommonSetup: yyk/rrk/{
  myResources:2;
  myLeaderDeployed:true
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_207
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: TWI_T02:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&mySpaceArena-0&theirGroundArena-0&theirGroundArena-1&theirSpaceArena-0

---

# ExactlyOneDamage_ThreeHpAtOneSurvives
#// HMW_207 — quantity discrimination, lower half of the boundary pair. SOR_095 is 3/3 seeded with 1
#// damage: exactly 1 more leaves it at 2 and ALIVE. A "deal 2" implementation would put it at 3 and
#// defeat it, so ARENACOUNT + DAMAGE together pin the amount from above.

## GIVEN
CommonSetup: yyk/rrk/{
  myResources:2
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_207
WithP2GroundArena: SOR_095:1:1
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# LethalBoundary_ThreeHpAtTwoIsDefeated
#// HMW_207 — upper half of the boundary pair. The SAME 3/3 seeded with 2 damage dies to the 1, which
#// pins the amount from below (a "deal 0" / no-op implementation leaves it standing). The exhaust is
#// moot on a defeated unit and must not fatal on the now-stale slot.

## GIVEN
CommonSetup: yyk/rrk/{
  myResources:2
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_207
WithP2GroundArena: SOR_095:1:2
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:READY
P2DISCARDCOUNT:1

---

# ShieldedTarget_DamagePrevented_StillExhausted
#// HMW_207 — ⚠ THE JUDGEMENT CALL. The clauses are joined by "and", not "If you do", so the exhaust is
#// NOT gated on the damage landing: a Shield token absorbs all 1 damage (DAMAGE stays 0, the token is
#// defeated) and the unit is exhausted anyway. Same reading as HMW_202 Inferno Squad. If the card is
#// ever ruled to gate the exhaust on damage being dealt, THIS is the section that changes.

## GIVEN
CommonSetup: yyk/rrk/{
  myResources:2
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_207
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# AlreadyExhaustedTarget_IsLegal_TakesDamage
#// HMW_207 — the text says "a unit", not "a ready unit", so an already-exhausted unit is a legal
#// target: the damage lands and the exhaust is simply a no-op. Proves the pool is not filtered on
#// readiness (a "zero-effect targets are unselectable" instinct would wrongly exclude it, because the
#// DAMAGE half still does something).

## GIVEN
CommonSetup: yyk/rrk/{
  myResources:2
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_207
WithP2GroundArena: SEC_080:0:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# TokenUnit_IsALegalTarget
#// HMW_207 — value-CLASS variant: a TOKEN unit (TWI_T02 Clone Trooper, 2/2) is a unit. The pool must
#// use the full ['Unit','Token Unit','Leader Unit'] filter; a bare ['Unit'] search silently drops every
#// token. 2 HP means it survives the 1 and the exhaust is observable.

## GIVEN
CommonSetup: yyk/rrk/{
  myResources:2
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_207
WithP2GroundArena: TWI_T02:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:TWI_T02
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# DeployedLeaderUnit_IsALegalTarget
#// HMW_207 — value-CLASS variant: a DEPLOYED LEADER unit (SOR_016 Thrawn, 3/9) is a unit. Its
#// CardType is 'Leader', so only the 'Leader Unit' ZoneSearch mapping finds it; a pool built from
#// ['Unit'] alone leaves deployed leaders untargetable. The leader sits at ground index 1 (a deployed
#// leader appends after the seeded units), and the CARDID assertion pins WHICH unit was hit.

## GIVEN
CommonSetup: yyk/rrk/{
  myResources:2;
  myLeaderDeployed:true
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_207
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_016
P1GROUNDARENAUNIT:1:DAMAGE:1
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:READY

---

# NoUnitsInPlay_EventFizzlesCleanly_NoDanglingDecision
#// HMW_207 — no valid target. With no unit anywhere the event still PLAYS (cost paid, card to the
#// discard) and resolves to nothing, leaving no dangling decision behind. A handler that queued an
#// empty choose would leave P1 with a pending decision here.

## GIVEN
CommonSetup: yyk/rrk/{
  myResources:2
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_207

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:HMW_207
P1RESAVAILABLE:1
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0

---

# RequestBoundary_TargetSurvivesTheDecision
#// HMW_207 — the REQUEST-BOUNDARY cell. The target choose ends the request in production, so the
#// continuation resumes in a fresh process: everything it needs (the chosen mzID, and the UniqueID it
#// re-resolves the target by after the damage may have re-indexed the arena) must ride the decision
#// itself, never an in-memory global. Identical board and answers to the positive above, with one
#// SimulateRequestBoundary inserted before the answer.

## GIVEN
CommonSetup: yyk/rrk/{
  myResources:2
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_207
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:CARDID:SOR_046
P2GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENAUNIT:1:READY
