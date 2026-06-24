# ASH_094 Moff Jerjerrod — the doubling also fires on a SINGLE-token creation (the SWUCreateUnitToken
# wrapper, used by the ~84 single-token sites). P1 plays SEC_097 (When Played: create a Spy token) with
# Jerjerrod in play and accepts: Jerjerrod is defeated and a 2nd Spy is created. Final ground = SEC_097 +
# 2 Spy = 3, and index 0 is SEC_097 (Jerjerrod was defeated and reindexed away — proving the doubling).
## GIVEN
CommonSetup: ggw/ggw/{myResources:3;handCardIds:SEC_097}
WithActivePlayer: 1
WithP1GroundArena: ASH_094:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:SEC_097
