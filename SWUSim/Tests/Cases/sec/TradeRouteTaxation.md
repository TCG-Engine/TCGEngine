# MoreUnits_OpponentCantPlayEvents
#// SEC_126 Trade Route Taxation (event, cost 2) — Choose an opponent. If you control more units than
#//   that opponent, they can't play events for this phase. P1 (2 units) > P2 (1 unit) → P2's event
#//   SEC_246 is blocked and stays in hand.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
WithActivePlayer: 1
WithP1GroundArena: SEC_041:1:0
WithP1GroundArena: SEC_042:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_126
WithP2Hand: SEC_246
WithP2Resources: 4

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0

## EXPECT
P2HANDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENACOUNT:2

---

# NotMoreUnits_OpponentCanStillPlayEvents
#// SEC_126 Trade Route Taxation — the lock only applies if you control MORE units than the chosen opponent.
#//   Here P1 (1 unit) does NOT control more than P2 (2 units), so the event has no effect and P2 can still
#//   play an event. P1 plays SEC_126, then P2 plays SHD_178 Daring Raid (deal 2 to a unit or base)
#//   targeting P1's base — it resolves, leaving P2's hand and dealing 2, proving the lock did not apply.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
WithActivePlayer: 1
WithP1GroundArena: SEC_041:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_126
WithP2Hand: SHD_178
WithP2Resources: 4

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P2>AnswerDecision:theirBase-0

## EXPECT
P2HANDCOUNT:0
P1BASEDMG:2
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:2

---

# BlocksEventPlayedViaSMUGGLE
#// SEC_126 Trade Route Taxation — "they can't play EVENTS for this phase" covers every route an event can
#// be played by, not just from hand. P1 (2 units) > P2 (1 unit), so P2 is locked out; P2 then tries to
#// SMUGGLE Covert Strength (SHD_075, an event with Smuggle [3 resources Vigilance]) from resources and the
#// play is refused — P2's damaged Plo Koon is not healed and the card stays a resource.
#// (Only testable since smuggled EVENTS were fixed to resolve at all — before that this passed vacuously.)
## GIVEN
CommonSetup: ggk/bbk/{myResources:2}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SEC_041:1:0
WithP1GroundArena: SEC_042:1:0
WithP2GroundArena: LOF_050:1:4
WithP1Hand: SEC_126
WithP2Resources: 1:SHD_075:1,10:SOR_095:1
WithP2Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P2>SmuggleResource:0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2DISCARDCOUNT:0

---

# BlocksEventPlayedFromDISCARD
#// SEC_126 Trade Route Taxation — the same lock applies to an event played from the DISCARD pile. P2 has
#// Covert Strength already in their discard; no free-play permission is involved, because P2 simply cannot
#// play events at all this phase — PlayFromDiscard is refused and the card stays put.
## GIVEN
CommonSetup: ggk/bbk/{myResources:2}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SEC_041:1:0
WithP1GroundArena: SEC_042:1:0
WithP2GroundArena: LOF_050:1:4
WithP1Hand: SEC_126
WithP2Discard: SHD_075
WithP2Resources: 8
## WHEN
- P1>PlayHand:0
- P2>PlayFromDiscard:0
## EXPECT
P2DISCARDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# BlocksEventPlayedViaPLOT
#// SEC_126 Trade Route Taxation — the lock is on PLAYING events, whatever route they take, so a Plot
#// event sitting in the opponent's resource row is refused too. P2's SEC_053 One in a Million is a Plot
#// card; P2 deploys their leader to open the Plot window and it cannot be played: the resource row is
#// unchanged, P2's deck is untouched and P1's units survive.
#// Completes the route matrix with BlocksEventPlayedViaSMUGGLE and BlocksEventPlayedFromDISCARD.

## GIVEN
CommonSetup: ggk/bbw/{myResources:2}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SEC_041:1:0
WithP1GroundArena: SEC_042:1:0
WithP1Hand: SEC_126
WithP2Resources: 1:SEC_053:1,7:SOR_046:1
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P2>DeployLeader

## EXPECT
P2LEADER:DEPLOYED
P2RESCOUNT:8
P2DECKCOUNT:2
P1GROUNDARENACOUNT:2
