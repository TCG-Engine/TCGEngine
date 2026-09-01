# BlockEndsWhenDefeated
#// SOR_062 Regional Governor — "While THIS UNIT is in play …". The block ends when Governor leaves
#// play. P1 plays Governor and names "Battlefield Marine". P2 attacks Governor (1/4) with SOR_210
#// (4/3) and defeats it. P1 passes. Now P2 can play their Battlefield Marine (SOR_095) — the block is
#// gone because Governor is no longer in play.
#// COVERAGE: offer=N/A — the clause's decision is a free-form NAMECARD (any printed card title),
#//           not a candidate pool, so there is no SELECTABLEEXACT to read. The scope assertions
#//           that stand in for it are which PLAY PATHS the lock reaches — BlocksNamedCard (from
#//           hand), BlocksTopDeckPlay (played by an effect out of the deck), BlocksNamedEvent (a
#//           non-unit card type) — plus BlockedCardDoesNotGlow, which is the offer-side
#//           consequence: a blocked card must not be lit as playable · reqboundary=BlocksNamedCard
#//           (the NAMECARD answer closes P1's request; the named title has to be serialized before
#//           P2's separate later request can be refused by it) · control=ControlChange_
#//           NewControllerMayPlayNamedCard (green: the new controller stops being an "opponent")
#//           + ControlChange_LockTurnsOntoOriginalController (RED — the lock should turn around
#//           onto the ORIGINAL controller and does not) · boundary pair=BlocksNamedCard vs
#//           NonNamedCardAllowed (the named title vs a different title) and BlocksNamedCard vs
#//           BlockEndsWhenDefeated (the naming unit in play vs gone) · decline=N/A — nothing on
#//           this card is printed as "you may": naming is a mandatory part of the When Played and
#//           the lock is a constant ability. ControllerCanStillPlayNamedCard is the negative that
#//           carries the "opponents only" scope in its place.

## GIVEN
CommonSetup: bbw/ggw/{myResources:2;theirResources:2}
WithP1Hand: SOR_062
WithP2Hand: SOR_095
WithP2GroundArena: SOR_210:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine
- P2>AttackGroundArena:0:0
- P1>Pass
- P2>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:1:CARDID:SOR_095
P2HANDCOUNT:0

---

# BlocksNamedCard
#// SOR_062 Regional Governor (Unit 1/4, cost 2, Vigilance) — "When Played: Name a card. While this
#// unit is in play, opponents can't play the named card." P1 plays Governor and names "Battlefield
#// Marine". On P2's turn, P2 tries to play their Battlefield Marine (SOR_095) — it is BLOCKED: the
#// card stays in hand, no resources spent.

## GIVEN
CommonSetup: bbw/ggw/{myResources:2;theirResources:2}
WithP1Hand: SOR_062
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine
- P2>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P2RESAVAILABLE:2

---

# BlocksTopDeckPlay
#// SOR_062 Regional Governor — the "can't play the named card" block also covers cards-played-by-
#// effects (not just from hand). P1 plays Governor and names "Battlefield Marine". On P2's turn, P2
#// plays U-Wing Reinforcement (SOR_104), which searches the top 10 and plays up to 3 units for free.
#// P2's deck top has two Battlefield Marines (SOR_095) — both are BLOCKED, so neither enters play;
#// they go back to the deck. (The U-Wing event still resolves and goes to P2's discard.)

## GIVEN
CommonSetup: bbw/ggw/{myResources:2;theirResources:7}
WithP1Hand: SOR_062
WithP2Hand: SOR_104
WithP2Deck: SOR_095
WithP2Deck: SOR_095
WithP2Deck: SOR_171
WithP2Deck: SOR_171
WithP2Deck: SOR_171
WithP2Deck: SOR_171
WithP2Deck: SOR_171
WithP2Deck: SOR_171
WithP2Deck: SOR_171
WithP2Deck: SOR_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine
- P2>PlayHand:0
- P2>AnswerDecision:SOR_095,SOR_095

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:0
P2DECKCOUNT:10
P2DISCARDCOUNT:1

---

# NonNamedCardAllowed
#// SOR_062 Regional Governor — the block is name-specific. P1 names "Death Star Stormtrooper"
#// (SOR_128, which P2 doesn't have). On P2's turn, P2 plays a DIFFERENT card — Battlefield Marine
#// (SOR_095) — which is NOT the named card, so it plays normally.

## GIVEN
CommonSetup: bbw/ggw/{myResources:2;theirResources:2}
WithP1Hand: SOR_062
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Death Star Stormtrooper
- P2>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2HANDCOUNT:0
P2RESAVAILABLE:0

---

# ControllerCanStillPlayNamedCard
#// SOR_062 Regional Governor — Intended: the lock reads "OPPONENTS can't play the named card", so
#// it is one-sided. P1 names Battlefield Marine and then plays P1's OWN Battlefield Marine in the
#// same phase — it enters play normally. This is the negative that proves the block is scoped by
#// controller and not a global "this title is unplayable". (Off-aspect for P1's Vigilance/Heroism
#// leader, so the Marine costs 2 + 2.)

## GIVEN
CommonSetup: bbw/ggw/{myResources:6;theirResources:2}
P1OnlyActions: true
WithP1Hand: [SOR_062 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_062
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1HANDCOUNT:0

---

# BlocksNamedEvent
#// SOR_062 Regional Governor — Intended: "name a CARD" is not restricted to units, and neither is
#// the lock. P1 names the event Mission Briefing; P2's copy is unplayable — it stays in hand, no
#// resource is spent, nothing reaches P2's discard and the draw-2 never happens (P2's deck is
#// untouched). This is the card-TYPE variant of the value class the unit sections already cover.

## GIVEN
CommonSetup: bbw/ggw/{myResources:2;theirResources:6}
WithP1Hand: SOR_062
WithP2Hand: SOR_171
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Mission Briefing
- P2>PlayHand:0

## EXPECT
P2HANDCOUNT:1
P2DISCARDCOUNT:0
P2DECKCOUNT:3
P2RESAVAILABLE:6
P1GROUNDARENACOUNT:1

---

# BlockedCardDoesNotGlow
#// SOR_062 Regional Governor — Intended: an unplayable card must not be lit up as playable. After
#// P1 names Battlefield Marine the turn passes to P2, who holds an affordable Battlefield Marine
#// (blocked) and an affordable Echo Base Defender (not blocked). Only the Echo Base Defender may
#// glow — the blocked Marine must read as un-selectable even though P2 can pay for it, which is
#// the discrimination that separates "blocked" from "unaffordable".

## GIVEN
CommonSetup: bbw/ggw/{myResources:2;theirResources:3}
WithP1Hand: SOR_062
WithP2Hand: [SOR_095 SOR_098]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine

## EXPECT
P1GROUNDARENACOUNT:1
P2HANDCOUNT:2
P2HANDGLOWNOT:0
P2HANDGLOW:1

---

# ControlChange_NewControllerMayPlayNamedCard
#// SOR_062 Regional Governor — control-axis control (the half that must be GREEN so the red half
#// below is evidence about the rule and not about the fixture). P1 plays the Governor and names
#// Battlefield Marine; P2 steals the Governor with Change of Heart (SOR_224). P2 now CONTROLS the
#// naming unit, so P2 is not one of "opponents" any more and plays their own Battlefield Marine
#// normally — the stolen Governor and the fresh Marine both sit in P2's arena.

## GIVEN
CommonSetup: bbw/gyw/{myResources:2;theirResources:8}
WithP1Hand: SOR_062
WithP2Hand: [SOR_224 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Pass
- P2>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SOR_062
P2GROUNDARENAUNIT:1:CARDID:SOR_095
P2HANDCOUNT:0

---

# ControlChange_LockTurnsOntoOriginalController
#// SOR_062 Regional Governor — Intended: "While this unit is in play, OPPONENTS can't play the
#// named card" is an ongoing ability of the UNIT, so per CR "opponents" is read against whoever
#// CONTROLS it right now, not against whoever played it. P1 plays the Governor and names
#// Battlefield Marine; P2 steals the Governor with Change of Heart (SOR_224). P1 is now an opponent
#// of the naming unit's controller, so P1's own Battlefield Marine must become unplayable — it
#// stays in hand and costs nothing. P1 can plainly afford it (4 resources left against an
#// off-aspect 2+2), so affordability cannot explain a refusal either way.
#// Paired control: ControlChange_NewControllerMayPlayNamedCard (same board, P2's side, GREEN).

## GIVEN
CommonSetup: bbw/gyw/{myResources:6;theirResources:8}
WithP1Hand: [SOR_062 SOR_095]
WithP2Hand: [SOR_224 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1HANDCARD:0:SOR_095
P1RESAVAILABLE:4
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_062
