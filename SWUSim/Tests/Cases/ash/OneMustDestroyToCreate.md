# DefeatDeclineReplay
#// ASH_247 One Must Destroy to Create — declining the optional replay leaves the defeated unit in the
#// discard pile. SOR_095 is defeated and P1 declines, so the arena is empty and the discard holds both the
#// event and SOR_095.
## GIVEN
CommonSetup: yyk/yyk/{myResources:3;handCardIds:ASH_247}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO
## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2

---

# DefeatThenReplayFree
#// ASH_247 One Must Destroy to Create (Event, cost 3) — Defeat a friendly non-leader unit, then you may
#// play that unit from your discard pile for free. SOR_095 (the only friendly non-leader unit, auto-chosen)
#// is defeated and replayed for free, so a fresh SOR_095 is back in the arena and the discard holds only the
#// event itself.
## GIVEN
CommonSetup: yyk/yyk/{myResources:3;handCardIds:ASH_247}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENACOUNT:1
P1DISCARDCOUNT:1

---

# SelfTargetAdvantage
#// ASH_247 One Must Destroy to Create defeats P1's own ASH_191 Shin Hati's Fiend Fighter (When Defeated:
#// may give 3 Advantage to a unit when NOT combat-defeated), then replays it from discard for free. Per
#// CR the event resolves FULLY (defeat + replay) before the triggered When Defeated resolves — so the
#// REPLAYED ASH_191 is back in the space arena and is a legal target for its own Advantage. Expected: the
#// replayed space unit ends with 3 Advantage tokens.
## GIVEN
CommonSetup: yyk/yyk/{myResources:3;handCardIds:ASH_247}
WithP1SpaceArena: ASH_191:1:0          # only friendly non-leader unit → auto-chosen for the defeat
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
P1SPACEARENAUNIT:0:CARDID:ASH_191
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:3

---

# DefeatFriendlyThenReplayFree
#// ASH_247 One Must Destroy to Create — Defeat a friendly non-leader unit; then you may play that unit from
#// your discard for free. P1 defeats SOR_095 (the only friendly non-leader) and replays it for free.
## GIVEN
CommonSetup: rrk/rrk/{myResources:3;handCardIds:ASH_247}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095

---

# ForeignOwnedUnit_DefeatedToTHEIRDiscard_NoReplayOffered
#// ASH_247 One Must Destroy to Create — "Defeat a friendly non-leader unit. Then, you may play that unit
#// from YOUR discard pile for free."
#// ⚠ OWNER vs CONTROLLER regression guard. A unit you CONTROL but do not OWN goes to its OWNER's discard
#// when defeated, so it never reaches "your discard pile" and the replay must NOT be offered — even
#// though the defeat itself is perfectly legal and the unit was a legal friendly non-leader target.
#//
#// The board is built with LAW_066 Tear This Ship Apart rather than a seeding directive so the
#// ownership split is produced by a REAL play path: P2's only resource is SEC_051 Bo-Katan Kryze, P1
#// looks at P2's resources and plays her for free. She enters under P1's CONTROL while P2 still OWNS
#// her, and P2 then resources their deck-top (so P2's resource count nets back to 1).
#// P1 then plays ASH_247. Bo-Katan is P1's only friendly non-leader unit, so the defeat choice
#// auto-resolves; she dies into P2's discard and P1 is offered nothing.
#//
#// DISCRIMINATES on both piles at once: P1's discard holds exactly the TWO events P1 played and NOT
#// Bo-Katan, while P2's holds Bo-Katan alone. A "find the card in any discard" implementation would
#// replay a unit its controller never owned. P1NODECISION proves the replay was never OFFERED, rather
#// than offered and declined.
#// Costs: 7 (Tear This Ship Apart) + 3 (One Must Destroy to Create) = the 10 resources P1 starts with.

## GIVEN
CommonSetup: ygk/rbw/{myResources:10;theirResources:0}
P1OnlyActions: true
WithP1Hand: LAW_066
WithP1Hand: ASH_247
WithP2Resources: 1:SEC_051:1
WithP2Deck: SOR_095
WithP2Deck: SOR_046

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2
P2DISCARDCOUNT:1
P1NODECISION
P1RESAVAILABLE:0

---

# PREMISE_ForeignBoKatanEntersUnderP1sControl
#// The PREMISE for the section above, and its control: without this, a passing "no replay was offered"
#// could simply mean the Tear This Ship Apart play never happened and there was no unit to defeat.
#// Stops after the steal: Bo-Katan is in P1's GROUND ARENA (P1 controls her), P2's arena is empty, and
#// P2's resource count is back to 1 because they resourced their deck-top to replace her.
#// P1 has 3 resources left — exactly the cost of One Must Destroy to Create in the section above.

## GIVEN
CommonSetup: ygk/rbw/{myResources:10;theirResources:0}
P1OnlyActions: true
WithP1Hand: LAW_066
WithP1Hand: ASH_247
WithP2Resources: 1:SEC_051:1
WithP2Deck: SOR_095
WithP2Deck: SOR_046

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_051
P2GROUNDARENACOUNT:0
P2RESCOUNT:1
P1RESAVAILABLE:3
P1DISCARDCOUNT:1
P2DISCARDCOUNT:0
