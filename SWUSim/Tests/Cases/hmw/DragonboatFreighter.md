# UniqueTarget_GetsWeaknessAndIsExhausted
#// HMW_231 Dragonboat Freighter (Unit, Space, 5/5, cost 6, [Cunning], Underworld/Vehicle/Transport,
#// non-unique) — "When Played: You may give a Weakness token to a unit. If it's a Unique unit,
#// exhaust it."
#//
#// COVERAGE: offer=Offer_SpansBothSides_UnqualifiedAUnit (SELECTABLEEXACT — "a unit" names no
#//           controller and no arena) ·
#//           decline=Decline_NothingHappensAtAll ("You may" earns a real refusal branch) ·
#//           boundary=N/A (structural: one token, one conditional exhaust; no threshold or count) ·
#//           control=N/A (structural: the pool is unqualified, so there is no seat-relative reading a
#//           control change could alter) ·
#//           reqboundary=RequestBoundary_TheTargetSurvivesIt ·
#//           modes=2P only — no player reference and no friendly/enemy wording, so all three formats
#//           share one code path (the documented ~2/3 case).
#//
#// ⚠ THE RIDER READS THE TARGET, NOT THE ACT. "If IT'S a Unique unit" is a property of the card that was
#// chosen — HMW_077 Boss Nass is unique, so he takes the Weakness (4/6 -> 3/5) AND is exhausted.
#// The non-unique control below is what makes that meaningful.
#// Two units are on the board so the MAY-choose really prompts.

## GIVEN
CommonSetup: yyk/yyk/{
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_231
WithP2GroundArena: HMW_077:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:HMW_077
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:HMW_T02
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:5
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:CARDID:SOR_095
P2GROUNDARENAUNIT:1:READY

---

# NonUniqueTarget_GetsWeaknessButStaysREADY
#// ⚠ HMW_231 — THE DISCRIMINATOR. A non-unique target takes the Weakness and is NOT exhausted; the
#// rider is conditional on the chosen card's uniqueness, not on the token being given. A handler that
#// exhausted unconditionally passes the positive above and fails only here.
#// SOR_095 Battlefield Marine is non-unique: 3/3 -> 2/2, and still READY.

## GIVEN
CommonSetup: yyk/yyk/{
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_231
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:HMW_T02
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:2
P2GROUNDARENAUNIT:0:READY

---

# Decline_NothingHappensAtAll
#// HMW_231 — the DECLINE branch, earned by the printed "You may". Answering '-' must leave every unit
#// untouched: no token, no exhaust. The Freighter itself still enters play, so this separates
#// "declined" from "the play failed".

## GIVEN
CommonSetup: yyk/yyk/{
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_231
WithP2GroundArena: HMW_077:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:HMW_231
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:1:UPGRADECOUNT:0
P2GROUNDARENAUNIT:1:READY

---

# Offer_SpansBothSides_UnqualifiedAUnit
#// HMW_231 — the OFFER cell. "a unit" carries NO controller qualifier, so your own units are legal
#// targets too — including the Freighter itself, which is in play by the time its When Played resolves.
#// A pool scoped to the enemy (the intuitive reading of a "give them a Weakness" card) is visibly
#// shorter. Decision left pending so the pool can be read.

## GIVEN
CommonSetup: yyk/yyk/{
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_231
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: HMW_077:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0

---

# WeaknessKillsAOneHpUnique_ExhaustIsMoot
#// HMW_231 — the ordering edge. HMW_T02 is -1/-1, so a 1-HP target drops to 0 remaining HP and is
#// defeated by the shrink sweep. SEC_111 Jar Jar Binks is a UNIQUE 2/1, so the rider WOULD fire — but
#// the unit is already gone, and re-resolving the target by UniqueID after the token is what stops the
#// exhaust from acting on a stale slot (or on whichever unit shifted into it).
#// The survivor beside it must be untouched and READY, which is what proves nothing was mis-targeted.

## GIVEN
CommonSetup: yyk/yyk/{
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_231
WithP2GroundArena: SEC_111:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:READY
P2DISCARDCOUNT:1

---

# RequestBoundary_TheTargetSurvivesIt
#// HMW_231 — the REQUEST-BOUNDARY cell. The MAY-choose ends the request, so the continuation that gives
#// the token, sweeps, and then conditionally exhausts resumes in a fresh process — and the UniqueID it
#// re-resolves by must ride the decision rather than a global. Same board and answer as the positive.

## GIVEN
CommonSetup: yyk/yyk/{
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_231
WithP2GroundArena: HMW_077:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:HMW_077
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:HMW_T02
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:READY
