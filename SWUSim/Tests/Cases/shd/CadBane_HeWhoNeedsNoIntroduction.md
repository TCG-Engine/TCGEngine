# Front_Decline
#// SHD_014 Cad Bane (front) — declining the "may" leaves Cad Bane ready and deals no damage.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_014}
WithActivePlayer: 1
WithP1Resources: 1
WithP1Hand: SOR_204
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1LEADER:READY
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Front_NoEnemyUnit_NoOffer
#// SHD_014 Cad Bane (front) — with no enemy unit to damage, the reaction makes no offer (Cad Bane stays
#// ready); playing an Underworld card resolves with no prompt.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_014}
P1OnlyActions: true
WithP1Resources: 1
WithP1Hand: SOR_204

## WHEN
- P1>PlayHand:0

## EXPECT
P1LEADER:READY
P1GROUNDARENACOUNT:1

---

# Front_OpponentUnitTakesOne
#// SHD_014 Cad Bane (front, undeployed) — "When you play an Underworld card: You may exhaust this leader.
#// If you do, an opponent chooses a unit they control. Deal 1 damage to it." P1 plays SOR_204 (Underworld),
#// accepts (exhausting Cad Bane); P2 must choose one of their units (SOR_046) which then takes 1 damage.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_014}
WithActivePlayer: 1
WithP1Resources: 1
WithP1Hand: SOR_204
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:1
