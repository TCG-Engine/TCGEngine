# 2ExpToRebel
#// SOR_241 Wing Leader (Space, 2/1) — When Played: give 2 Experience tokens to another
#// friendly REBEL unit. P1's Battlefield Marine (SOR_095, Rebel, 3/3) is the only other
#// Rebel → auto-receives +2/+2 (→ 5/5).
#// COVERAGE: offer=Offer_OtherFriendlyRebelsOnly (pending SELECTABLEEXACT: excludes self,
#//           non-Rebel friendlies, and enemy Rebels) · decline=N/A (mandatory give, no
#//           "you may") · control=N/A (one-shot token grant at play time; nothing to follow) ·
#//           boundary=StacksOntoExistingExperience_ThreeTotal (token stacking edge) ·
#//           reqboundary=N/A (resolves inside the play ceremony)

## GIVEN
CommonSetup: rrw/rrw/{myResources:3;handCardIds:SOR_241}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5

---

# StacksOntoExistingExperience_ThreeTotal
#// SOR_241 Wing Leader — Intended: the 2 Experience tokens stack onto a unit that ALREADY has
#// one. Battlefield Marine (3/3) starts with 1 Experience (4/4); the sole other friendly Rebel
#// auto-receives 2 more → 3 Experience total, 6/6.

## GIVEN
CommonSetup: rrw/rrw/{myResources:3;handCardIds:SOR_241}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:3
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:6

---

# Offer_OtherFriendlyRebelsOnly
#// SOR_241 Wing Leader — Intended: the give-2-Experience pool is "another friendly REBEL unit"
#// only. P1 controls two ground Rebels (SOR_095, SOR_046) and a non-Rebel Vehicle (SOR_249);
#// P2 controls a Rebel (SOR_095). The pick is left PENDING: the pool must hold exactly the two
#// friendly Rebels — not the non-Rebel, not the enemy Rebel, and not the just-played Wing
#// Leader itself ("another").

## GIVEN
CommonSetup: rrw/rrw/{myResources:3;handCardIds:SOR_241}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_249:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# NoOtherFriendlyRebel_NothingIsGiven
#// SOR_241 Wing Leader — the NO-VALID-TARGET cell. Offer_OtherFriendlyRebelsOnly proves the pool is
#// narrowed correctly when legal targets exist; this is the board where the narrowing removes every
#// candidate, which is the case a "fall back to any friendly unit" or "include yourself" implementation
#// gets wrong while passing all three existing sections.
#// COVERAGE (addendum to the ledger in 2ExpToRebel): the no-valid-target branch is covered here — the
#// three exclusions the offer section asserts as a POOL are re-asserted as an EMPTY pool, so the
#// ability must queue nothing and give nothing.
#//
#// P1's only other unit is a non-Rebel Vehicle (Frontier AT-RT) and the only Rebel besides Wing Leader
#// belongs to P2. "Another friendly REBEL unit" therefore names nobody: no decision is queued, no
#// Experience is created anywhere, and Wing Leader — who is himself excluded by "another" — stays at
#// his printed 2/1 rather than keeping the tokens for himself.

## GIVEN
CommonSetup: rrw/rrw/{myResources:3;handCardIds:SOR_241}
P1OnlyActions: true
WithP1GroundArena: SOR_249:1:0    # friendly, but a Vehicle — not a REBEL
WithP2GroundArena: SOR_095:1:0    # a REBEL, but not friendly

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_241
P1SPACEARENAUNIT:0:POWER:2
P1SPACEARENAUNIT:0:HP:1
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# SimulateRequestBoundary_ExperienceGiveSurvivesTheBoundary
#// SOR_241 Wing Leader — the REQUEST-BOUNDARY cell. The ledger in 2ExpToRebel records reqboundary as
#// N/A on the grounds that the ability "resolves inside the play ceremony", but that is only true on
#// the one-legal-target board: Offer_OtherFriendlyRebelsOnly shows the pick staying PENDING as soon as
#// two friendly Rebels exist, and in production a pending pick ENDS the request — the answer arrives in
#// a fresh process with every in-memory global gone. This section closes that gap for real rather than
#// by assertion.
#//
#// Same two-Rebel board as the offer section, with the boundary inserted between the play and the
#// answer. The chosen Consular Security Force (3/7) must still end up with BOTH Experience tokens
#// (5/9), and the Rebel that was not chosen with none.

## GIVEN
CommonSetup: rrw/rrw/{myResources:3;handCardIds:SOR_241}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0    # 2nd legal Rebel — keeps the pick interactive across the boundary

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:UPGRADECOUNT:2
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HP:9
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION
