# TWI_204 Impropriety Among Thieves — "At the start of the regroup phase, each player takes control of
# each unit they own that was chosen for this ability." The control swap is temporary (TEMPORARY_STEAL):
# after the swap, advancing to the regroup phase returns each unit to its OWNER — SOR_095 back to P1,
# SEC_080 back to P2.
## GIVEN
CommonSetup: rrk/bbw/{myResources:10;handCardIds:TWI_204}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Deck: [SEC_080 SEC_080 SEC_080 SEC_080 SEC_080 SEC_080]
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080 SEC_080 SEC_080]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>Pass
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
