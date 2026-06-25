## GIVEN
# myLeaderDeployed:true places the code's leader (rrk -> Vader SOR_010) as a REAL ground-arena
# leader unit, linked via DeployedUniqueID — present, ready, and IsLeaderUnit on the board, not
# just a Deployed flag. (It can attack like any unit; Vader's "On Attack: may deal 2" would need an
# AnswerDecision step, so this demo asserts board presence instead.)
CommonSetup: rrk/grw/{myResources:5;myLeaderDeployed:true}
P1OnlyActions: true

## WHEN

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_010
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:0:READY
P1LEADER:DEPLOYED
