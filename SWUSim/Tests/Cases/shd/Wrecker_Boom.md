# Decline_NoDamage
#// SHD_154 Wrecker — declining the optional resource-defeat (AnswerDecision:-) does nothing: no resource is
#// lost (still 7) and no damage is dealt.

## GIVEN
CommonSetup: rrw/rrw/{myResources:7}
P1OnlyActions: true
WithP1Hand: SHD_154
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1RESCOUNT:7
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# DefeatResource_Deal5
#// SHD_154 Wrecker (6-cost 7/6 ground) — Overwhelm + "When Played: You may defeat a friendly resource. If
#// you do, deal 5 damage to a ground unit." P1 plays Wrecker (7 resources), defeats one resource (→ 6 total),
#// then deals 5 to the enemy SOR_046 (7 HP → 5 damage).

## GIVEN
CommonSetup: rrw/rrw/{myResources:7}
P1OnlyActions: true
WithP1Hand: SHD_154
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1RESCOUNT:6
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# TeamSuns_DefeatsTheTEAMMATESResourceIntoTHEIRDiscard
#// The DEFEAT half of the same ruling: "you may defeat a FRIENDLY resource" now reaches a teammate's.
#// The destination is the mirror of the return case — a defeated resource goes to ITS OWNER's discard,
#// so seat 3's card lands in seat 3's pile where seat 3 can recur it, not in the caster's.
#// (Owner is routinely unset on a resource, and the primitive used to default it to the acting player.)

## GIVEN
CommonSetup: rrk/bbw/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: SHD_154
WithP3Resources: 1
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3Resources-0
- P1>AnswerDecision:p2GroundArena-0

## EXPECT
SEATCOUNT:4
P3RESCOUNT:0
P3DISCARDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# TwinSunsControl_TeammateResourceIsNotOffered
#// THE CONTROL — teams off, so seat 3's resource is not friendly and is not in the pool. Seat 1 holds
#// exactly two of its own, which is what the offer must contain.

## GIVEN
CommonSetup: rrk/bbw/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: SHD_154
WithP1Resources: 2
WithP3Resources: 1
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1SELECTABLENOT:p3Resources-0
