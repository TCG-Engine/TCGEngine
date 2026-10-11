# CheaperWithEnemyBounty
#// SHD_117 Reputable Hunter (3-cost, Command) — "If an enemy unit has a Bounty, this unit costs 1 resource
#// less to play." With the enemy Bounty unit SHD_095 in play, it costs 2 (3 resources → 1 left).
#// COVERAGE: offer=N/A (a passive cost reduction — no target pick anywhere) · decline=N/A (not optional;
#//           the discount is not a "you may") · control=N/A (the gate reads "an ENEMY unit", which is
#//           re-evaluated from the payer's seat at cost time) · boundary=CheaperWithEnemyBounty (innate
#//           Bounty) + CheaperWhenTheBountyComesFromAnUpgrade (GRANTED Bounty) vs FullCostNoBounty ·
#//           reqboundary=N/A (cost is computed fresh on every play attempt, nothing is stored)

## GIVEN
CommonSetup: ggk/ggk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_117
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_117
P1RESAVAILABLE:1

---

# FullCostNoBounty
#// SHD_117 Reputable Hunter — without an enemy Bounty unit it costs the full 3 (3 resources → 0 left).

## GIVEN
CommonSetup: ggk/ggk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_117
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_117
P1RESAVAILABLE:0

---

# CheaperWhenTheBountyComesFromAnUpgrade
#// Intended: "if an enemy unit HAS a Bounty" is a live ability check, not a printed-text check — a Bounty
#// GRANTED by an attached upgrade counts just as much as an innate one. SOR_046 has no Bounty of its own
#// (it is the exact unit FullCostNoBounty uses to prove the full 3), but wearing SHD_071 Top Target it
#// gains "Bounty - Heal 4 damage..." and the Hunter drops to 2 (3 resources → 1 left). The pair of
#// sections isolates the upgrade as the only variable.

## GIVEN
CommonSetup: ggk/ggk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_117
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SHD_071

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_117
P1RESAVAILABLE:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# ThreeSeat_BountyOnAFarSeat_StillCheaper
#// "If AN ENEMY UNIT has a Bounty" — any enemy. The Bounty unit sits on SEAT 3; seat 2's board is bare.
#// The discount must still apply, so 3 resources buy the 3-cost Hunter at 2 and leave 1.

## GIVEN
CommonSetup3P: ggk/ggk/ggk
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 3
WithP1Hand: SHD_117
WithP3GroundArena: SHD_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:3
P1GROUNDARENAUNIT:0:CARDID:SHD_117
P1RESAVAILABLE:1

---

# FourSeat_BountyOnTheFarthestSeat_StillCheaper
#// 4P sibling: the Bounty is on SEAT 4, two bystander boards bare.

## GIVEN
CommonSetup4P: ggk/ggk/ggk/ggk
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 3
WithP1Hand: SHD_117
WithP4GroundArena: SHD_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1GROUNDARENAUNIT:0:CARDID:SHD_117
P1RESAVAILABLE:1
