# ExhaustedSmuggleBehindReady_Protected
#// SHD_114 Scanning Officer — the Ready-first reveal protects a Smuggle resource kept EXHAUSTED. P2 has
#// 3 ready non-Smuggle resources (SOR_095) + 1 exhausted Smuggle card (SHD_129). Scanning Officer reveals
#// the 3 ready ones; the exhausted Smuggle is NOT among the revealed 3, so it is never defeated. P2 keeps
#// all 4 resources, nothing goes to discard and nothing is replaced — this is the incentive to keep your
#// Smuggle cards exhausted vs Scanning Officer.
#// COVERAGE: offer=N/A (the reveal picks 3 by rule, ready-first — the victim set is derived, never chosen)
#//           · decline=N/A (mandatory on both sides) · control=N/A (the reveal is scoped to "enemy
#//           resources" from the player's own seat) · boundary=zero Smuggle (NoSmuggleRevealed_NoOp) /
#//           one (ReadySmuggleRevealed_...) / all three (ThreeReadySmuggle_...) / out of reach
#//           (ExhaustedSmuggleBehindReady_Protected). ⚠ NOT covered: Smuggle acquired from an AURA rather
#//           than printed — deferred, the keyword grant misresolves the resource's controller ·
#//           reqboundary=N/A (reveal, defeat and replacement all land in the one play)

## GIVEN
CommonSetup: rrk/grw/{myResources:4}
P1OnlyActions: true
WithP2Resources: 3:SOR_095:1,1:SHD_129:0
WithP2Deck: SEC_080
WithP1Hand: SHD_114

## WHEN
- P1>PlayHand:0

## EXPECT
P2RESCOUNT:4
P2RESAVAILABLE:3
P2DISCARDCOUNT:0
P2DECKCOUNT:1

---

# NoSmuggleRevealed_NoOp
#// SHD_114 Scanning Officer — fizzle: if none of the 3 revealed resources has the Smuggle keyword, nothing
#// is defeated and nothing is replaced (no crash, no dangling decision). P2 has 3 ready non-Smuggle
#// resources (SOR_095); all 3 are revealed but none is Smuggle, so P2 is untouched.

## GIVEN
CommonSetup: rrk/grw/{myResources:4}
P1OnlyActions: true
WithP2Resources: 3:SOR_095:1
WithP2Deck: SEC_080
WithP1Hand: SHD_114

## WHEN
- P1>PlayHand:0

## EXPECT
P2RESCOUNT:3
P2RESAVAILABLE:3
P2DISCARDCOUNT:0
P2DECKCOUNT:1

---

# ReadySmuggleRevealed_DefeatedReplacedExhausted
#// SHD_114 Scanning Officer — When Played: reveal 3 enemy resources and defeat each REVEALED Smuggle one,
#// its controller replacing it from deck AS A RESOURCE (exhausted, NOT readied — the key difference from
#// SEC_242 Elia Kane). P2 has 2 ready non-Smuggle resources (SOR_095) + 1 ready Smuggle card (SHD_129).
#// All 3 are revealed (only 3 exist); the Smuggle one is defeated and P2 replaces it from deck with an
#// EXHAUSTED resource — so P2 keeps 3 resources but only 2 are ready, the Smuggle card now in discard,
#// deck −1.

## GIVEN
CommonSetup: rrk/grw/{myResources:4}
P1OnlyActions: true
WithP2Resources: 2:SOR_095:1,1:SHD_129:1
WithP2Deck: SEC_080
WithP1Hand: SHD_114

## WHEN
- P1>PlayHand:0

## EXPECT
P2RESCOUNT:3
P2RESAVAILABLE:2
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SHD_129
P2DECKCOUNT:0

---

# ThreeReadySmuggle_AllDefeatedReplacedExhausted
#// SHD_114 Scanning Officer — "defeat EACH revealed Smuggle resource" defeats more than one. P2 has 3
#// ready Smuggle cards (SHD_129); all 3 are revealed and all 3 are defeated, and for each one P2 puts the
#// top of their deck into play as an EXHAUSTED resource. P2 ends with 3 resources again but all exhausted
#// (none ready), 3 Smuggle cards in discard, deck emptied.

## GIVEN
CommonSetup: rrk/grw/{myResources:4}
P1OnlyActions: true
WithP2Resources: 3:SHD_129:1
WithP2Deck: [SEC_080 SOR_095 SOR_100]
WithP1Hand: SHD_114

## WHEN
- P1>PlayHand:0

## EXPECT
P2RESCOUNT:3
P2RESAVAILABLE:0
P2DISCARDCOUNT:3
P2DECKCOUNT:0

---

# TechGrantsSmuggle_EveryRevealedResourceIsDefeated
#// Intended: "defeat each resource WITH THE SMUGGLE KEYWORD revealed this way" reads the keyword LIVE, so
#// Smuggle handed out by an aura counts. P2 fields SHD_248 Tech ("Each friendly resource gains Smuggle")
#// and 3 ready SOR_095 resources — cards with no printed Smuggle at all, the exact ones that survive
#// untouched in NoSmuggleRevealed_NoOp. Under Tech all 3 revealed resources are Smuggle, so all 3 are
#// defeated and each is replaced from the top of P2's deck as an EXHAUSTED resource. Tech itself is
#// untouched — it is a unit, not a resource.
#// This is the SEAT half of the check: the grant must be read from the RESOURCE'S OWN seat. It used to be
#// read from P1's board for every resource in the game, so P2's Tech granted nothing.

## GIVEN
CommonSetup: rrk/grw/{myResources:4}
P1OnlyActions: true
WithP2GroundArena: SHD_248:1:0
WithP2Resources: 3:SOR_095:1
WithP2Deck: [SEC_080 SOR_095 SOR_100]
WithP1Hand: SHD_114

## WHEN
- P1>PlayHand:0

## EXPECT
P2RESCOUNT:3
P2RESAVAILABLE:0
P2DISCARDCOUNT:3
P2DECKCOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SHD_248
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# YourOwnTechDoesNotGrantSmuggleToTheOPPONENTsResources
#// The other half of the seat bug, and the more dangerous one. SHD_248 Tech reads "Each FRIENDLY resource
#// gains Smuggle" — friendly to TECH'S controller. Here P1 controls Tech and plays Scanning Officer at
#// P2's three plain SOR_095 resources: those are not friendly to P1's Tech, so they never gain Smuggle
#// and NONE of them may be defeated. P1's own resources are the ones Tech is actually granting to.
#// Before the fix every resource in the game resolved the grant against P1's board, so P1's Tech made the
#// OPPONENT's resources smugglable and this play wiped P2's entire resource row.

## GIVEN
CommonSetup: rrk/grw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SHD_248:1:0
WithP2Resources: 3:SOR_095:1
WithP2Deck: [SEC_080 SOR_095 SOR_100]
WithP1Hand: SHD_114

## WHEN
- P1>PlayHand:0

## EXPECT
P2RESCOUNT:3
P2RESAVAILABLE:3
P2DISCARDCOUNT:0
P2DECKCOUNT:3

---

# TwinSuns_CasterPicksWHOSEResourcesToScan
#// ⚠ TWIN SUNS SWEEP PASS 2 (2026-08-27) — §1b "defending player / that opponent / its controller" family.
#// "Reveal 3 ENEMY resources" names no seat. This resolved OtherPlayer($player) — literally seat 2 — so
#// above two seats the card always scanned seat 2 no matter who the caster meant. Now the caster chooses,
#// following SHD_184 Bazine Netal ("look at AN OPPONENT's hand"), the canonical analogue for this shape.
#//
#// The fixture is built so the LEGACY answer differs from the CORRECT one (sweep rule 6): P1's opponents
#// are 2 and 4 (3 is a teammate), both hold an identical ready Smuggle resource, and P1 picks P4. Under
#// the old code P2 would have been scanned instead — so this section fails if the fix is reverted.
#// P4 keeps 3 resources but only 2 ready (the Smuggle one defeated, replaced EXHAUSTED); P2 is untouched.

## GIVEN
CommonSetup: rrk/grw
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Resources: 4
WithP1Hand: SHD_114
WithP2Resources: 2:SOR_095:1,1:SHD_129:1
WithP2Deck: SEC_080
WithP4Resources: 2:SOR_095:1,1:SHD_129:1
WithP4Deck: SEC_080

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P4

## EXPECT
SEATCOUNT:4
P4RESCOUNT:3
P4RESAVAILABLE:2
P2RESCOUNT:3
P2RESAVAILABLE:3

---

# TwinSuns_OfferIsBothOpponents_AndExcludesAResourcelessOne
#// Sweep rule 4: assert the PROMPT, never just answer it — a spare answer is silently absorbed, so a
#// section that only answers proves nothing about who was offered. Rule 5: a menu assertion needs TWO
#// eligible opponents, since at one the picker correctly auto-resolves invisibly.
#//
#// Here P2 and P4 both hold resources and P3 is a TEAMMATE, so the offer must be exactly {P2, P4} —
#// asserting P3 is absent is what pins "teammates are never enemies" for this card.
#// (The resource-less filter is Bazine's precedent: an opponent with nothing to reveal is a choice among
#// nothing. It is covered by the auto-resolve in the sections above, where only one opponent has any.)

## GIVEN
CommonSetup: rrk/grw
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Resources: 4
WithP1Hand: SHD_114
WithP2Resources: 2:SOR_095:1,1:SHD_129:1
WithP2Deck: SEC_080
WithP3Resources: 2:SOR_095:1,1:SHD_129:1
WithP4Resources: 2:SOR_095:1,1:SHD_129:1
WithP4Deck: SEC_080

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1OPTIONHAS:P2
P1OPTIONHAS:P4
P1OPTIONNOT:P3
