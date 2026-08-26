# SymmetricAttritionAndWipe
#// TWI_177 Guerilla Insurgency (Event, cost 8, Aggression, Tactic) — "Each player defeats a resource they
#// control and discards 2 cards from their hand. Deal 4 damage to each ground unit." Both players lose a
#// resource and their whole (2-card) hands; every ground unit takes 4 (SOR_046 3/7 survives at 4, SOR_128
#// 3/1 dies). Hands are seeded to exactly 2 so the discards auto-resolve.
#//
#// ⚠ UPDATED 2026-08-26 (user-approved). "Each player defeats a resource THEY control" now PROMPTS that
#// player instead of silently taking their slot 0 — a resource is not fungible, and which one dies is
#// information the owner can act on. Every assertion below is unchanged; the added picks are what make
#// them happen. Each seat answers on its OWN queue, which is also what proves the decider is the
#// resource's owner and not the caster.

## GIVEN
CommonSetup: rrk/bbw/{myResources:9;theirResources:3}
P1OnlyActions: true
WithP1Hand: [TWI_177 SOR_095 SOR_128]
WithP2Hand: [SOR_095 SOR_128]
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0
- P2>AnswerDecision:myResources-0

## EXPECT
P1HANDCOUNT:0
P2HANDCOUNT:0
P1RESCOUNT:8
P2RESCOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENACOUNT:0

---

# TwinSuns_EVERYSeatLosesAResourceAndTwoCards
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-21. "EACH PLAYER defeats a resource they control and discards 2
#// cards from their hand." Both halves were two-seat literals: the resource loop was
#// `foreach ([caster, OtherPlayer(caster)])` and the discard was a single untargeted SWUDiscardCards.
#// At four seats, seats 3 and 4 kept their resource AND their hand — half the card simply did not happen.
#// ⚠ A 2-player version cannot fail: with two seats the old literal and GetLiveSeatsArray() are the same
#//   list. The seat count IS the test.
#// Every seat starts on 2 cards (so the discards auto-resolve with no prompt) and loses one resource.
#// The "deal 4 to each ground unit" half already used ZoneSearch and is untouched — SOR_128 (3/1) dies on
#// every seat that has one, which also proves the damage half still reaches the far seats.
#// ⚠ FIXTURE: seats 3/4 exist only via WithSeatOrder/WithLiveSeats, and their resources/hands must be
#//   seeded explicitly — CommonSetup builds seats 1 and 2 only.
#//
#// ⚠ UPDATED 2026-08-26 (user-approved). "Each player defeats a resource THEY control" now PROMPTS that
#// player instead of silently taking their slot 0 — a resource is not fungible, and which one dies is
#// information the owner can act on. Every assertion below is unchanged; the added picks are what make
#// them happen. Each seat answers on its OWN queue, which is also what proves the decider is the
#// resource's owner and not the caster.

## GIVEN
CommonSetup: rrk/bbw/{myResources:9;theirResources:3}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: [TWI_177 SOR_095 SOR_128]
WithP2Hand: [SOR_095 SOR_128]
WithP3Hand: [SOR_095 SOR_128]
WithP4Hand: [SOR_095 SOR_128]
WithP3Resources: 5
WithP4Resources: 4
WithP1GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_128:1:0
WithP4GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0
- P2>AnswerDecision:myResources-0
- P3>AnswerDecision:myResources-0
- P4>AnswerDecision:myResources-0

## EXPECT
SEATCOUNT:4
P1HANDCOUNT:0
P2HANDCOUNT:0
P3HANDCOUNT:0
P4HANDCOUNT:0
P3RESCOUNT:4
P4RESCOUNT:3
P3GROUNDARENACOUNT:0
P4GROUNDARENACOUNT:0

---

# EachSeatIsPromptedOverItsOWNResources
#// THE DECIDER CELL. "Each player defeats a resource THEY control" — so the prompt belongs to each
#// player for their own board, not to the caster for everyone's. The two sections above assert the
#// OUTCOME (counts drop), which an auto-pick would also produce; only the offer itself shows WHO was
#// asked and WHAT they were asked about.
#//
#// Seat 3 is a non-caster with 5 resources, so its offer must be exactly its own five and must contain no
#// foreign-frame (`p{n}Resources-…`) entry. A build that handed the caster one big cross-seat pool would
#// leave seat 3 with no decision at all and fail here while both sections above stayed green.

## GIVEN
CommonSetup: rrk/bbw/{myResources:9;theirResources:3}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: [TWI_177 SOR_095 SOR_128]
WithP2Hand: [SOR_095 SOR_128]
WithP3Hand: [SOR_095 SOR_128]
WithP4Hand: [SOR_095 SOR_128]
WithP3Resources: 5
WithP4Resources: 4

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P3SELECTABLEEXACT:myResources-0&myResources-1&myResources-2&myResources-3&myResources-4
P4SELECTABLEEXACT:myResources-0&myResources-1&myResources-2&myResources-3
