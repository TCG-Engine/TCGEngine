# ExhaustedPlotBehindReady_Protected
#// SEC_242 Elia Kane — the Ready-first reveal protects a Plot card kept EXHAUSTED. P2 has 3 ready
#// resources + 1 exhausted Plot card (SEC_053). Elia Kane reveals the 3 ready ones; the exhausted Plot is
#// NOT revealed. Since you can only defeat a REVEALED resource, P1's attempt to defeat the Plot
#// (theirResources-3) is rejected — the Plot survives, nothing is defeated and nothing is replaced. This is
#// the incentive to keep your Smuggle/Plot cards exhausted.

## GIVEN
CommonSetup: rrk/grw/{myResources:4}
P1OnlyActions: true
WithP2Resources: 3:SOR_095:1,1:SEC_053:0
WithP2Deck: SEC_080
WithP1Hand: SEC_242

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-3

## EXPECT
P2RESCOUNT:4
P2RESAVAILABLE:3
P2DISCARDCOUNT:0
P2DECKCOUNT:1

---

# FourResourcesPlusCredit_LooksAtThreeOnly
#// SEC_242 Elia Kane — "look at 3 enemy resources" caps at 3 even when more exist, and the Ready-first
#// reveal rule picks WHICH 3. With 4 resources (3 ready + 1 exhausted) and a Credit, the 3 READY resources
#// are the ones looked at (theirResources-0/1/2); the exhausted 4th and the last-kept Credit are not.
#// P1 defeats one of the 3 ready; P2 replaces it from deck (ready), so P2 keeps 3 ready + 1 exhausted = 4
#// real resources, the Credit untouched, deck −1, the defeated resource in discard.

## GIVEN
CommonSetup: rrk/grw/{myResources:4}
P1OnlyActions: true
WithP2Resources: 3:SOR_095:1,1:SOR_095:0
WithP2Credits: 1
WithP2Deck: SEC_080
WithP1Hand: SEC_242

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-0

## EXPECT
P2RESCOUNT:4
P2RESAVAILABLE:3
P2CREDITCOUNT:1
P2DECKCOUNT:0
P2DISCARDCOUNT:1

---

# ReadyPlotRevealed_CanBeDefeated
#// SEC_242 Elia Kane — a Plot card left READY gets no protection: it's revealed like any ready resource
#// and can be defeated. P2 has 2 exhausted resources + 1 ready Plot card (SEC_053). All 3 are revealed (only
#// 3 exist); P1 defeats the ready Plot (theirResources-2). It goes to P2's discard and P2 replaces it from
#// deck with a ready resource — so P2 ends with 2 exhausted + 1 ready, the Plot now in discard, deck −1.

## GIVEN
CommonSetup: rrk/grw/{myResources:4}
P1OnlyActions: true
WithP2Resources: 2:SOR_095:0,1:SEC_053:1
WithP2Deck: SEC_080
WithP1Hand: SEC_242

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-2

## EXPECT
P2RESCOUNT:3
P2RESAVAILABLE:1
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SEC_053
P2DECKCOUNT:0

---

# ThreeExhausted_DefeatGivesOneReady
#// SEC_242 Elia Kane — when ALL of the opponent's resources are exhausted, there's no ready one to reveal
#// preferentially, so the player can only defeat an exhausted resource. Its controller replaces it from
#// deck as a READY resource — so the opponent ends with 1 ready resource (a "free" ready resource; the
#// Ready-first reveal rule exists precisely to avoid handing this out when ready resources DO exist).
#// P2 has 3 exhausted resources; P1 defeats one → P2 has 2 exhausted + 1 ready.

## GIVEN
CommonSetup: rrk/grw/{myResources:4}
P1OnlyActions: true
WithP2Resources: 3:SOR_095:0
WithP2Deck: SEC_080
WithP1Hand: SEC_242

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-0

## EXPECT
P2RESCOUNT:3
P2RESAVAILABLE:1
P2DECKCOUNT:0
P2DISCARDCOUNT:1

---

# ThreeResourcesPlusCredit_LooksAtResourcesNotCredit
#// SEC_242 Elia Kane — "look at 3 enemy resources." When the opponent has 3 resources AND a Credit token,
#// the Credit is kept LAST in the resource zone, so the 3 looked-at (and offerable) cards are the real
#// resources — the Credit is never offered. P1 defeats one resource (theirResources-0); P2 replaces it from
#// deck (ready). The Credit is untouched (P2 still has 1), the defeated resource goes to discard, deck −1.
#// (Real resources stay at 3 because P2RESCOUNT excludes Credit tokens.)

## GIVEN
CommonSetup: rrk/grw/{myResources:4}
P1OnlyActions: true
WithP2Resources: 3:SOR_095:1
WithP2Credits: 1
WithP2Deck: SEC_080
WithP1Hand: SEC_242

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-0

## EXPECT
P2RESCOUNT:3
P2CREDITCOUNT:1
P2DECKCOUNT:0
P2DISCARDCOUNT:1

---

# TwoExhaustedOneReady_DefeatExhausted_GivesTwoReady
#// SEC_242 Elia Kane — opponent has 2 exhausted + 1 ready (all 3 presented). Defeating an EXHAUSTED one
#// (theirResources-0) replaces it from deck with a READY resource, so the opponent now has 2 ready (the
#// original ready + the new replacement) — a free ready resource for the opponent. This is the outcome the
#// Ready-first reveal rule avoids when ready resources exist (it would only offer ready ones to defeat).

## GIVEN
CommonSetup: rrk/grw/{myResources:4}
P1OnlyActions: true
WithP2Resources: 2:SOR_095:0,1:SOR_095:1
WithP2Deck: SEC_080
WithP1Hand: SEC_242

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-0

## EXPECT
P2RESCOUNT:3
P2RESAVAILABLE:2
P2DECKCOUNT:0
P2DISCARDCOUNT:1

---

# TwoExhaustedOneReady_DefeatReady_StaysOneReady
#// SEC_242 Elia Kane — opponent has 2 exhausted + 1 ready resource (3 total, so all 3 are presented).
#// Defeating the READY one (theirResources-2): it's replaced from deck with another ready resource, so the
#// opponent STILL has just 1 ready (no free upgrade). This is the "correct" target — it doesn't hand the
#// opponent a free ready resource.

## GIVEN
CommonSetup: rrk/grw/{myResources:4}
P1OnlyActions: true
WithP2Resources: 2:SOR_095:0,1:SOR_095:1
WithP2Deck: SEC_080
WithP1Hand: SEC_242

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-2

## EXPECT
P2RESCOUNT:3
P2RESAVAILABLE:1
P2DECKCOUNT:0
P2DISCARDCOUNT:1

---

# WhenPlayed_DefeatResourceReplace
#// SEC_242 Elia Kane (Ground, 3/6, Villainy) — Raid 1 + When Played: look at 3 enemy resources, may
#//   defeat 1; if you do, its controller puts the top of their deck into play as a ready resource.
#//   Defeat one of P2's 3 resources → P2 replaces from deck (net resource count unchanged, deck −1).

## GIVEN
CommonSetup: rrk/grw/{myResources:4}
P1OnlyActions: true
WithP2Resources: 3:SOR_095:1
WithP2Deck: [SOR_095]
WithP1Hand: SEC_242

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-0

## EXPECT
P2RESCOUNT:3
P2DECKCOUNT:0
P2DISCARDCOUNT:1
P1NODECISION
