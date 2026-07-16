# NoBountyHunter_NoOffer
#// SHD_254 Bounty Guild Initiate — without another friendly Bounty Hunter unit, the gate fails and there is
#// no offer. The enemy SOR_046 is untouched and no decision is pending.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_254
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# WithBountyHunter_Deal2
#// SHD_254 Bounty Guild Initiate (1-cost 1/2 ground) — "When Played: If you control another Bounty Hunter
#// unit, you may deal 2 damage to a ground unit." With the friendly LAW_124 (Bounty Hunter) already in play,
#// the gate is met and P1 deals 2 to the enemy SOR_046.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_254
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2
