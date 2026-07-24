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
