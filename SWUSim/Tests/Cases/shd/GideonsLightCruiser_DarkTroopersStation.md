# Decline
#// SHD_242 Gideon's Light Cruiser — control Moff Gideon but DECLINE the optional free-play.
#// The offer is a "may" (MZMAYCHOOSE); answering '-' declines, so SEC_080 stays in hand and nothing extra plays.

## GIVEN
CommonSetup: ggk/rrk/{myResources:10;myLeader:SHD_007:1:1}
P1OnlyActions: true
WithP1Hand: SHD_242
WithP1Hand: SEC_080

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1NODECISION

---

# FreePlayedUnitFiresWhenPlayedOnce
#// SHD_242 Gideon's Light Cruiser — the free-played unit's OWN When Played fires exactly once.
#// SEC_240 (Space, 3/5, Villainy, cost 3) has "When Played: Deal 2 damage to this unit." Free-played via
#// SHD_242, it must end with DAMAGE:2 (a single fire), not 4 (a double fire from both the auto entry-trigger
#// and a manual re-fire) — guards the nested-play trigger drain.

## GIVEN
CommonSetup: ggk/rrk/{myResources:10;myLeader:SHD_007:1:1}
P1OnlyActions: true
WithP1Hand: SHD_242
WithP1Hand: SEC_240

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:1:CARDID:SEC_240
P1SPACEARENAUNIT:1:DAMAGE:2

---

# NoGideon_NoOp
#// SHD_242 Gideon's Light Cruiser — no Moff Gideon controlled → When Played does nothing.
#// P1 has a Vader (rrk) setup with no Moff Gideon. Playing SHD_242 (12 resources cover the off-aspect +2)
#// resolves with no free-play offer: SEC_080 stays in hand, no decision pending.

## GIVEN
CommonSetup: rrk/rrk/{myResources:12}
P1OnlyActions: true
WithP1Hand: SHD_242
WithP1Hand: SEC_080

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1NODECISION

---

# NoValidTarget_NoOp
#// SHD_242 Gideon's Light Cruiser — control Moff Gideon but hand has no eligible unit → clean fizzle.
#// SOR_095 (Heroism, not Villainy) doesn't qualify, so the offer is skipped entirely (no decision).

## GIVEN
CommonSetup: ggk/rrk/{myResources:10;myLeader:SHD_007:1:1}
P1OnlyActions: true
WithP1Hand: SHD_242
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1NODECISION

---

# PlaysFromDiscard
#// SHD_242 Gideon's Light Cruiser — free-play the Villainy <=3 unit from the DISCARD pile.
#// P1 controls Moff Gideon deployed. SEC_080 (cost 2, Villainy) sits in P1's discard. Playing SHD_242
#// offers it; P1 picks myDiscard-0 and it enters play for free (discard empties, ground count = Gideon + SEC_080).

## GIVEN
CommonSetup: ggk/rrk/{myResources:10;myLeader:SHD_007:1:1;discardCardIds:SEC_080}
P1OnlyActions: true
WithP1Hand: SHD_242

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:2
P1DISCARDCOUNT:0
P1RESAVAILABLE:2

---

# PlaysVillainyFromHand
#// SHD_242 Gideon's Light Cruiser (Unit, Space, cost 8, Villainy, 7/8, Overwhelm)
#//   "When Played: If you control Moff Gideon (as a leader or unit), play a [Villainy] unit that costs
#//    3 or less from your hand or discard pile for free."
#// P1 controls Moff Gideon (SHD_007) deployed, so its Villainy aspect covers SHD_242 (cost stays 8).
#// P1 plays SHD_242 (pays 8 of 10 → 2 left); its When Played offers a free Villainy <=3 unit. P1 picks
#// SEC_080 (Command/Villainy, cost 2) from hand — it enters play for FREE (resources stay at 2), proving
#// the nested free-play from a unit's When Played drains to the arena.

## GIVEN
CommonSetup: ggk/rrk/{myResources:10;myLeader:SHD_007:1:1}
P1OnlyActions: true
WithP1Hand: SHD_242
WithP1Hand: SEC_080

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SHD_242
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:2
P1HANDCOUNT:0
