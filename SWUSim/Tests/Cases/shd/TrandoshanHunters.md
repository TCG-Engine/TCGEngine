# EnemyBounty_ExpSelf
#// SHD_140 Trandoshan Hunters (5-cost 6/4 ground) — Overwhelm + "When Played: If an enemy unit has a Bounty,
#// give an Experience token to this unit." With the enemy Bounty unit SHD_095 in play, Trandoshan Hunters
#// gets an Experience token (→ 7/5).

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_140
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_140
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:5

---

# NoBounty_NoExp
#// SHD_140 Trandoshan Hunters — without an enemy Bounty unit, no Experience token is given (stays 6/4).

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_140
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_140
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:6
