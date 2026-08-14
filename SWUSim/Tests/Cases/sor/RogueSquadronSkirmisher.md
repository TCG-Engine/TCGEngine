# ReturnFromDiscard
#// SOR_101 Rogue Squadron Skirmisher (cost 6, Command/Heroism) — When Played:
#// return a unit that costs 2 or less from your discard to hand.
#// P1 discard seeded with Battlefield Marine (SOR_095, cost 2 — valid) and
#// Consular Security Force (SOR_046, cost 4 — too expensive). Playing SOR_101
#// auto-returns the only ≤2-cost unit (SOR_095) to hand; SOR_046 stays in discard.
#// P2 has no units, so SOR_101's Ambush has no target and does not prompt.
#// COVERAGE: offer=AmbushFirst_ThenReturn_OfferEligibleOnly (pending SELECTABLEEXACT: only
#//           ≤2-cost discard units; cost-3/cost-4 units and an event excluded) ·
#//           decline=NoEligibleInDiscard_NoReturn_AmbushDeclined (Ambush declined; the return
#//           itself is mandatory when candidates exist) · boundary=cost 2 in / cost 3 out
#//           (AmbushFirst_ThenReturn_OfferEligibleOnly) + empty-eligible-pool no-op
#//           (NoEligibleInDiscard_NoReturn_AmbushDeclined) · reqboundary=AmbushFirst_ThenReturn_PicksMarine
#//           (trigger-order → ambush YES → discard pick span separate requests) · control=N/A
#//           (the When Played reads the playing player's own discard; no persistent state that
#//           could change controller)

## GIVEN
CommonSetup: ggw/ggw/{myResources:6;handCardIds:SOR_101;discardCardIds:SOR_095,SOR_046}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_046

---

# AmbushFirst_ThenReturn_OfferEligibleOnly
#// SOR_101 — the player may order Ambush before the When Played. Ambush attack into Consular
#// Security Force (3/7): it takes 4, the Skirmisher takes 3 back. Then the return pick offers
#// ONLY discard units costing 2 or less: Battlefield Marine (2) and Greedo (1) — the 3-cost
#// Death Trooper (one over the boundary), the event (Open Fire) and the 4-cost Consular are
#// all excluded. Two candidates → pick left pending.

## GIVEN
CommonSetup: ggw/ggw/{myResources:6;handCardIds:SOR_101;discardCardIds:SOR_095,SOR_204,SOR_033,SOR_172,SOR_046}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:Ambush
- P1>AnswerDecision:YES
# (sole enemy unit → the ambush attack target auto-resolves)

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:EXHAUSTED
P1HASDECISION
P1SELECTABLEEXACT:myDiscard-0&myDiscard-1

---

# AmbushFirst_ThenReturn_PicksMarine
#// SOR_101 — same flow resolved: after the Ambush trade the player picks Battlefield Marine
#// from the two eligible discard units; the Marine goes to hand, Greedo / Death Trooper /
#// Open Fire / the 4-cost Consular stay in the discard.

## GIVEN
CommonSetup: ggw/ggw/{myResources:6;handCardIds:SOR_101;discardCardIds:SOR_095,SOR_204,SOR_033,SOR_172,SOR_046}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:Ambush
- P1>AnswerDecision:YES
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_095
P1DISCARDCOUNT:4
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# NoEligibleInDiscard_NoReturn_AmbushDeclined
#// SOR_101 — with no discard unit costing 2 or less (only an event and a 4-cost unit) the
#// When Played is ordered first, has no candidates and no-ops; the player then declines
#// Ambush, so no attack happens and the Skirmisher just sits down. Hand stays empty;
#// discard untouched.

## GIVEN
CommonSetup: ggw/ggw/{myResources:6;handCardIds:SOR_101;discardCardIds:SOR_172,SOR_046}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:WhenPlayed
- P1>AnswerDecision:NO

## EXPECT
P1HANDCOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_101
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P1DISCARDCOUNT:2
P1NODECISION
