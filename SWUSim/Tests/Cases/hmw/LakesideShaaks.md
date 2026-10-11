# ReadiesOneFriendlyResource
#// COVERAGE: offer=TeamSuns_SplitIsOffered (the NUMBERCHOOSE range is the only offer; in 2P there is no
#//           choice — resources are fungible within a player) · decline=N/A (STRUCTURAL: mandatory)
#//           boundary=ReadiesExactlyOne (4 exhausted, only 1 comes back)
#//           control=N/A (no owner-scoped zone; "friendly" is resolved against the controller's team)
#//           reqboundary=TeamSuns_AcrossTheRequestBoundary · no-target=N/A (STRUCTURAL: paying the Shaaks'
#//           own cost exhausts resources, so there is always one to ready unless Credits paid it all)
#//           modes=2P,TeamSuns (text says "a friendly resource") · TwinSuns=TwinSuns_OpponentIsNotFriendly
#//
#// HMW_228 Lakeside Shaaks — Unit (Ground) 4/4, cost 4, [Cunning], Creature.
#// "When Played: Ready a friendly resource."
#// 5 resources, pay 4 → 1 ready, 4 exhausted → the When Played readies one → 2 ready.

## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_228
WithP1Resources: 5:SOR_046:1

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1RESAVAILABLE:2
P1NODECISION

---

# ReadiesExactlyOne
#// 6 ready + 2 already exhausted: pay 4 → 2 ready, 6 exhausted → the When Played readies ONE → 3 ready.
#// "a" resource, never all of them.

## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_228
WithP1Resources: 6:SOR_046:1,2:SOR_046:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:3
P1RESCOUNT:8

---

# TwinSuns_OpponentIsNotFriendly
#// Four seats, no teams: seat 3 is an opponent. Its exhausted resource must stay exhausted.

## GIVEN
CommonSetup: yyk/bbw
SkipPreGame: true
P1OnlyActions: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: HMW_228
WithP1Resources: 4:SOR_046:1
WithP3Resources: 2:SOR_046:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1RESAVAILABLE:1
P3RESAVAILABLE:0
P1NODECISION

---

# TeamSuns_SplitIsOffered
#// Team game: seat 3 is P1's teammate and holds exhausted resources, and P1 has exhausted ones from
#// paying — the helper asks how many of the one come from P1's own.

## GIVEN
CommonSetup: yyk/bbw
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: HMW_228
WithP1Resources: 4:SOR_046:1
WithP3Resources: 2:SOR_046:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1RESAVAILABLE:0
P3RESAVAILABLE:0

---

# TeamSuns_TeammatesResourceReadied

## GIVEN
CommonSetup: yyk/bbw
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: HMW_228
WithP1Resources: 4:SOR_046:1
WithP3Resources: 2:SOR_046:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:0

## EXPECT
P1RESAVAILABLE:0
P3RESAVAILABLE:1
P1NODECISION

---

# TeamSuns_AcrossTheRequestBoundary

## GIVEN
CommonSetup: yyk/bbw
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: HMW_228
WithP1Resources: 4:SOR_046:1
WithP3Resources: 2:SOR_046:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:1

## EXPECT
P1RESAVAILABLE:1
P3RESAVAILABLE:0
