# Deal2Then1SameArena
#// TWI_171 Grenade Strike (Event, cost 2, Aggression, Tactic) — "Deal 2 damage to a unit. You may deal 1
#// damage to another unit in the same arena." Two enemy ground units: 2 to the first, then (option taken) 1
#// to the second (same arena).

## GIVEN
CommonSetup: rrk/bbw/{myResources:2;handCardIds:TWI_171}
P1OnlyActions: true
WithP2GroundArena: [SOR_046:1:0 SOR_046:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:1:DAMAGE:1

---

# Deal2_DeclineSecond
#// TWI_171 Grenade Strike — the second hit is optional: declining (AnswerDecision:-) leaves the second
#// unit undamaged.

## GIVEN
CommonSetup: rrk/bbw/{myResources:2;handCardIds:TWI_171}
P1OnlyActions: true
WithP2GroundArena: [SOR_046:1:0 SOR_046:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:1:DAMAGE:0

---

# NoSecondTargetOtherArena
#// TWI_171 Grenade Strike — same-arena restriction: the first target is the only ground unit; the only
#// other unit is in the SPACE arena, so no second-hit offer is made (no pending decision) and the space
#// unit is untouched.

## GIVEN
CommonSetup: rrk/bbw/{myResources:2;handCardIds:TWI_171}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:DAMAGE:2
P2SPACEARENAUNIT:0:DAMAGE:0
