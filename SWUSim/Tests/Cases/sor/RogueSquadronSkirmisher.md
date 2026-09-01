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

---

# WhenPlayedOrderedFirst_ThenAmbush
#// SOR_101 — the OTHER trigger order. Both the When Played return and Ambush enter the same timing
#// window, so the player may resolve either first; AmbushFirst_ThenReturn_PicksMarine takes one
#// branch, this one takes the other. The return is resolved first (Battlefield Marine picked out of
#// the two eligible ≤2-cost discard units), and only then is Ambush triggered into the sole enemy
#// Consular Security Force. Same end state as the Ambush-first branch: Marine in hand, four cards left
#// in the discard, Consular on 4 damage and the Skirmisher on 3 and exhausted. Order must not change
#// the outcome here — if it does, one of the two orders is resolving against a stale board.

## GIVEN
CommonSetup: ggw/ggw/{myResources:6;handCardIds:SOR_101;discardCardIds:SOR_095,SOR_204,SOR_033,SOR_172,SOR_046}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:WhenPlayed
- P1>AnswerDecision:myDiscard-0
- P1>AnswerDecision:YES

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_095
P1DISCARDCOUNT:4
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# Offer_ExcludesNonUnitsAndTheOpponentDiscard
#// SOR_101 — "Return A UNIT that costs 2 or less FROM YOUR DISCARD PILE" carries three separate gates,
#// and the pending offer has to enforce all three at once. P1's discard holds Battlefield Marine (unit,
#// cost 2 — the boundary value, IN), Greedo (unit, cost 1 — IN), Disarm (an EVENT costing 1: cheap
#// enough but the wrong CARD TYPE, OUT) and Death Trooper (unit, cost 3 — one over the boundary, OUT).
#// P2's discard holds a cost-1 Death Star Stormtrooper, which satisfies both the type and the cost gate
#// and is excluded purely by the ZONE word "your". The existing offer section pairs a cost-3 event with
#// a cost-4 unit, so neither of its exclusions isolates the type gate; a CHEAP event does.
#// P2 controls no unit, so Ambush never prompts and the return pick is the only pending decision.

## GIVEN
CommonSetup: ggw/ggw/{myResources:6;handCardIds:SOR_101;discardCardIds:SOR_095,SOR_204,SOR_216,SOR_033;theirDiscardCardIds:SOR_128}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myDiscard-0&myDiscard-1

---

# Ambush_TargetPoolIsEnemyGroundUnitsOnly
#// SOR_101 — the Ambush clause's own scope, which no existing section pins because they all seed a
#// single enemy unit and let the target auto-resolve. "Ambush (After you play this unit, it may ready
#// and attack an ENEMY UNIT.)" gives three gates: enemy only (P1's own Battlefield Marine is not
#// offered), UNIT only (P2's base is not a legal Ambush target even though it is a legal attack target
#// generally), and the attacking unit's own arena (P2's TIE/ln Fighter is in the space arena and the
#// Skirmisher is Ground). Two eligible enemy ground units keep the choice interactive, so it is left
#// PENDING and the exact pool read. The When Played return is empty here (nothing in the discard), so
#// the Ambush target pick is the only decision on the table. (Both triggers still enter the
#// window even with an empty discard, so Ambush is ordered explicitly first.)

## GIVEN
CommonSetup: ggw/ggw/{myResources:6;handCardIds:SOR_101}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SOR_046:1:0 SEC_080:1:0]
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:Ambush
- P1>AnswerDecision:YES

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1
