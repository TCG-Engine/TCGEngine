# TWI_204 Impropriety Among Thieves (Event, cost 4, Cunning/Cunning) — "Choose a ready non-leader unit
# controlled by each player. Each player takes control of the chosen unit controlled by the player to
# their right." In 2P this is a control SWAP: P1 chooses its own SOR_095 and P2's SEC_080 → P1 takes
# control of SEC_080 and P2 takes control of SOR_095 (each moves into the new controller's arena).
## GIVEN
CommonSetup: rrk/bbw/{myResources:10;handCardIds:TWI_204}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
