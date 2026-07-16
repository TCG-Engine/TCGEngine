# Bounty_ReplayFromDiscard_Exp
#// SHD_161 Stolen Landspeeder — full loop. P1 plays it from hand → P2 takes control. P1's Consular
#// Security Force (3/7) then defeats it (3 ≥ HP 2; counter 3 back). P1 owns the defeated unit (it
#// went to P1's discard), so collecting the bounty plays it from P1's discard FOR FREE and gives it
#// an Experience token (3/2 → 4/3). This replay is from DISCARD, not hand, so the When Played
#// control-flip does NOT fire — it stays under P1's control.

## GIVEN
CommonSetup: grw/grw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SHD_161

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SHD_161
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:3
P1DISCARDCOUNT:0

---

# PlayedFromHand_OpponentControls
#// SHD_161 Stolen Landspeeder — "When Played: If you played this unit from your hand, an opponent
#// takes control of it." P1 plays it from hand (1 resource, Aggression covered by the rw leader);
#// it moves to P2's ground arena, still exhausted from entering play.

## GIVEN
CommonSetup: grw/grw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_161

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SHD_161
P2GROUNDARENAUNIT:0:EXHAUSTED
P1RESAVAILABLE:0
