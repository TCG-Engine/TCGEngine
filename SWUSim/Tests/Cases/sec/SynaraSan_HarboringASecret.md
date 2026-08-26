# OnAttack_ReadyResourcePerUnit
#// SEC_225 Synara San (Ground, 7/7) — Hidden + On Attack: for each friendly unit, ready a friendly
#//   resource. With 2 friendly units (SEC_225 + SEC_041) and 2 exhausted resources, attacking readies 2.

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_225:1:0
WithP1GroundArena: SEC_041:1:0
WithP1Resources: 2:SOR_046:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:7
P1RESAVAILABLE:2

---

# TeamSuns_TwinSunsControl_NoTeammatePool_AutoReadiesYourOwn
#// COVERAGE: offer=TeamSuns_SplitIsOffered (the NUMBERCHOOSE range, asserted pending) ·
#//           decline=N/A (the split is mandatory once the ability resolves — there is no "may") ·
#//           boundary=TeamSuns_NoOwnExhausted_AllFromTeammate_NoPrompt is the lo===hi edge, paired
#//                    against TeamSuns_SplitIsOffered where lo<hi ·
#//           control=this section (teams OFF) ·
#//           reqboundary=N/A (the split is answered inside the same attack; no state is written before
#//                    the decision that is not on the CUSTOM's own param)
#//
#// SEC_225 Synara San — "On Attack: For each friendly unit, ready a friendly resource."
#// USER RULING 2026-08-26: "a friendly resource" spans the TEAM, and the player may SPLIT the readying
#// between their own resources and their teammate's.
#//
#// ⚠ The split is asked as a COUNT, never a card picker: resources are fungible within a player, so the
#// only real decision is how many from whom. That also avoids the Resources zone being Visibility=Self
#// (a teammate's cards would render as CARD BACKS) and Mode=All (your own render inline, not as a popup).
#//
#// THE CONTROL. Teams OFF, so seat 3 is an opponent and there is no teammate pool at all: the whole
#// thing collapses to the old self-only behaviour with NO prompt, which is what keeps Premier and Twin
#// Suns byte-identical.
#// ⚠ Seat 1 fields BOTH units and seat 3 fields none, deliberately — that fixes the friendly-unit COUNT
#// at 2 in either mode, so these sections isolate the RESOURCE split rather than also moving the count.

## GIVEN
CommonSetup: yyk/bbw/{theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: [SEC_225:1:0 SOR_046:1:0]
WithP1Resources: 2:SOR_046:0
WithP3Resources: 2:SOR_046:0

## WHEN
- P1>AttackGroundArena:0:P2B

## EXPECT
SEATCOUNT:4
P1NODECISION
P1RESAVAILABLE:2
P3RESAVAILABLE:0

---

# TeamSuns_SplitIsOffered
#// Teams ON. Two friendly units => ready 2 friendly resources. Seat 1 has 2 exhausted and seat 3 has 2,
#// so ANY split of the two is legal: the offer must be the full 0..2 range, left pending here.
#// lo = max(0, k - mateAvail) = 0, hi = min(k, myAvail) = 2.

## GIVEN
CommonSetup: yyk/bbw/{theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: [SEC_225:1:0 SOR_046:1:0]
WithP1Resources: 2:SOR_046:0
WithP3Resources: 2:SOR_046:0

## WHEN
- P1>AttackGroundArena:0:P2B

## EXPECT
SEATCOUNT:4
P1HASDECISION
P1DECISIONTOOLTIP:Ready_2_friendly_resources:_how_many_from_YOUR_OWN?

---

# TeamSuns_SplitAnswered_OneEach
#// Answering 1 readies one of your own and one of your teammate's. Asserting BOTH seats is what makes
#// this discriminate — a build that ignored the answer and readied all 2 from one side would still
#// satisfy a single-seat assertion.

## GIVEN
CommonSetup: yyk/bbw/{theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: [SEC_225:1:0 SOR_046:1:0]
WithP1Resources: 2:SOR_046:0
WithP3Resources: 2:SOR_046:0

## WHEN
- P1>AttackGroundArena:0:P2B
- P1>AnswerDecision:1

## EXPECT
SEATCOUNT:4
P1RESAVAILABLE:1
P3RESAVAILABLE:1

---

# TeamSuns_AllFromTheTeammate
#// The other end of the same range: answering 0 puts both onto the teammate's board and leaves seat 1's
#// own resources untouched. Paired with the section above, this proves the answer is actually honoured
#// rather than the split being computed some fixed way.

## GIVEN
CommonSetup: yyk/bbw/{theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: [SEC_225:1:0 SOR_046:1:0]
WithP1Resources: 2:SOR_046:0
WithP3Resources: 2:SOR_046:0

## WHEN
- P1>AttackGroundArena:0:P2B
- P1>AnswerDecision:0

## EXPECT
SEATCOUNT:4
P1RESAVAILABLE:0
P3RESAVAILABLE:2

---

# TeamSuns_NoOwnExhausted_AllFromTeammate_NoPrompt
#// THE DEGENERATE EDGE (lo === hi). Seat 1 has NO exhausted resources of its own, so every one of the
#// two must come from the teammate and there is exactly one legal split — the engine must resolve it
#// silently rather than raise a one-answer question, which is the repo's standing rule.

## GIVEN
CommonSetup: yyk/bbw/{theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: [SEC_225:1:0 SOR_046:1:0]
WithP1Resources: 2:SOR_046:1
WithP3Resources: 2:SOR_046:0

## WHEN
- P1>AttackGroundArena:0:P2B

## EXPECT
SEATCOUNT:4
P1NODECISION
P3RESAVAILABLE:2
