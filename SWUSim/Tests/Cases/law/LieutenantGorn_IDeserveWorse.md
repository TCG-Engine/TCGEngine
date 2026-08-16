# OnAttackTakeCredit
#// LAW_221 Lieutenant Gorn (4/4) — On Attack: take control of an enemy Credit token. Attacks the base;
#// P2 loses its Credit, P1 gains one.

## GIVEN
CommonSetup: yyw/bgw/{theirResources:0}
P1OnlyActions: true
WithP2Credits: 1
WithP1GroundArena: LAW_221:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1CREDITCOUNT:1
P2CREDITCOUNT:0

---

# EnemyGornTakesBack
#// LAW_221 Lieutenant Gorn — the On-Attack steal works for whichever player attacks, so an opponent's
#// own Gorn can take a Credit right back. Here P2's Gorn attacks P1's base while P1 holds a Credit; P2
#// ends with the Credit, P1 with none.

## GIVEN
CommonSetup: bgw/yyw/{}
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1Credits: 1
WithP2GroundArena: LAW_221:1:0

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P2CREDITCOUNT:1
P1CREDITCOUNT:0

---

# OnlyTakesOneOfMultiple
#// LAW_221 Lieutenant Gorn — On Attack takes control of a SINGLE enemy Credit token even when the
#// opponent has several. P2 has 3 Credits; Gorn attacks the base and P1 ends with exactly 1 Credit,
#// P2 keeps the other 2.

## GIVEN
CommonSetup: yyw/bgw/{theirResources:0}
P1OnlyActions: true
WithP2Credits: 3
WithP1GroundArena: LAW_221:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1CREDITCOUNT:1
P2CREDITCOUNT:2

---

# NoEnemyCredits_NothingHappens
#// LAW_221 Lieutenant Gorn — On Attack does nothing when the opponent controls no Credit tokens. P1
#// starts with 1 Credit (to prove no change) and P2 has none; after Gorn attacks the base, credit
#// counts are unchanged.

## GIVEN
CommonSetup: yyw/bgw/{theirResources:0}
P1OnlyActions: true
WithP1Credits: 1
WithP1GroundArena: LAW_221:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1CREDITCOUNT:1
P2CREDITCOUNT:0

---

# StolenCreditSpendableByNewController
#// LAW_221 Lieutenant Gorn — a stolen Credit belongs to its NEW controller for payment purposes.
#// Intended: after the On-Attack steal, the thief can defeat the taken Credit to pay for a card.
#// P1 has 0 resources; Gorn attacks the base and takes P2's only Credit. P1 then plays SOR_069
#// Resilient (cost 1, Vigilance): Gorn is the only unit in play so the host auto-resolves, and the
#// payment offers the stolen Credit (at myResources-0, after P1's zero real resources). Defeating it
#// pays the full cost — no resources exhausted, Credit gone, Resilient attached.

## GIVEN
CommonSetup: bbw/ggk/{myResources:0}
P1OnlyActions: true
WithP2Credits: 1
WithP1GroundArena: LAW_221:1:0
WithP1Hand: SOR_069

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1CREDITCOUNT:0
P2CREDITCOUNT:0
P1RESCOUNT:0
P1NODECISION

---

# OldControllerCannotSpendStolenCredit
#// LAW_221 Lieutenant Gorn — the ORIGINAL owner loses all access to the stolen Credit. After P1's
#// Gorn takes P2's only Credit, P2 plays SOR_069 Resilient (cost 1) with exactly 1 resource: no
#// Credit-payment offer appears for P2 (they control none), the real resource is exhausted, and P1
#// keeps the stolen Credit untouched.

## GIVEN
CommonSetup: yyw/bbw/{theirResources:1}
WithP1GroundArena: LAW_221:1:0
WithP2GroundArena: SOR_046:1:0
WithP2Hand: SOR_069
WithP2Credits: 1

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>PlayHand:0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1CREDITCOUNT:1
P2CREDITCOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2RESCOUNT:1
P2RESAVAILABLE:0
P2NODECISION

---

# StolenCreditCanBeStolenBack
#// LAW_221 Lieutenant Gorn — full round-trip: a Credit that changed controller once is a legal
#// steal target AGAIN. Each player has their own Gorn; P2 starts with the only Credit. P1's Gorn
#// attacks the base and takes it; then on P2's action their Gorn attacks P1's base and takes the
#// (controlled-by-P1, owned-by-P2) Credit right back. Exercises re-stealing a token whose
#// controller already differs from its owner — the end state is back where it started.
#//
#// COVERAGE: offer=N/A (the steal is mandatory and single-object — the lone enemy Credit
#//           auto-resolves; OnlyTakesOneOfMultiple pins the one-not-all contract) ·
#//           decline=N/A (no "you may") · control=StolenCreditSpendableByNewController +
#//           OldControllerCannotSpendStolenCredit + this section (token control round-trip) ·
#//           boundary pair=NoEnemyCredits_NothingHappens vs OnAttackTakeCredit (0 vs 1 Credit) ·
#//           reqboundary=covered by the two-action flows here and in
#//           OldControllerCannotSpendStolenCredit (steal state survives into a later action).

## GIVEN
CommonSetup: yyw/yyw/{}
WithP1GroundArena: LAW_221:1:0
WithP2GroundArena: LAW_221:1:0
WithP2Credits: 1

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AttackGroundArena:0:BASE

## EXPECT
P1CREDITCOUNT:0
P2CREDITCOUNT:1

---

# Offer_NA_OnlyEnemyCreditsAreEligible
#// LAW_221 Lieutenant Gorn — the offer axis, discriminated. "Take control of an ENEMY Credit token" has a
#// real controller restriction, so the board seeds a violator: P1 already controls 2 Credits of its own
#// while P2 controls 3. Verified: even with 5 Credits in play across both seats, NO decision is ever
#// raised — Credit tokens are fungible, so the steal resolves against the enemy pool automatically
#// (P1NODECISION). Auto-resolution IS the assertion here, and the end state carries the discrimination:
#// exactly one Credit crosses (P1 2->3, P2 3->2), so P1's own Credits were never candidates and only one
#// of P2's three was taken. Confirms the existing "offer=N/A" ledger claim under a multi-Credit board.

## GIVEN
CommonSetup: yyw/bgw/{theirResources:0}
P1OnlyActions: true
WithP1Credits: 2
WithP2Credits: 3
WithP1GroundArena: LAW_221:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1CREDITCOUNT:3
P2CREDITCOUNT:2
P1NODECISION
