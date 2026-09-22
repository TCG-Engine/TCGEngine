# Initiative_TurnStaysWhileTheClaimantsTriggerIsPending
#// Owner UX report (Twin Suns, 2026-09-22), the initiative twin of PlanCounter_TurnWaitsForPlan.md: claiming the
#// initiative passed the turn AT ONCE, so a claimant still answering a "when you take the initiative" prompt had
#// already handed the turn to the next seat, whose clicks bounced off "decisions are pending". The pass now waits
#// behind the triggers (SWU_INITIATIVE_PASS). ASH_155 Grogu's "you may attack with a unit" is left pending here.
## GIVEN
CommonSetup: rrk/rgw
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithP1GroundArena: ASH_155:1:0
WithP1GroundArena: SOR_046:1:0
## WHEN
- P1>Claim
## EXPECT
INITIATIVECOUNTER:P1_CLAIMED
TURNPLAYER:1
P1DECISIONTOOLTIP:Choose_a_unit_to_attack_with

---

# Initiative_BonusAttackResolves_TurnGoesToTheVERYNextSeat
#// After the bonus attack the turn moves exactly one seat. The close is stamped at the claim, so the attack's own
#// after-action is refused and the deferred pass is the ONLY swap — a second one would land on seat 3.
## GIVEN
CommonSetup: rrk/rgw
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithP1GroundArena: ASH_155:1:0
WithP1GroundArena: SOR_046:1:0
## WHEN
- P1>Claim
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:p2Base-0
## EXPECT
P2BASEDMG:3
TURNPLAYER:2
P1NODECISION

---

# Initiative_TriggerDeclined_TurnStillPasses
#// ASH_014 The Mandalorian (leader): "you may pay 1 resource; if you do, draw a card". Declining the YES/NO is a
#// sticky PASS — the deferred pass is exempt from it, so the turn still moves one seat.
## GIVEN
CommonSetup: grw/brk/{
  myLeader:ASH_014
}
WithSeatOrder: 123
WithLiveSeats: 123
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 1
WithP1Deck: SOR_095
## WHEN
- P1>Claim
- P1>AnswerDecision:PASS
## EXPECT
P1HANDCOUNT:0
TURNPLAYER:2
P1NODECISION

---

# Initiative_NoTrigger_TurnPassesImmediately
#// Control: nothing to resolve, so the claim passes at once, as it always has.
## GIVEN
CommonSetup: rrk/rgw
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
## WHEN
- P1>Claim
## EXPECT
TURNPLAYER:2
P1NODECISION
