# TWI_116 Clone — arena type is a printed attribute, so copying a SPACE unit makes Clone enter the SPACE
# arena. Clone copies an enemy LOF_069 (2/7 space Creature with Sentinel): it enters P1's space arena as
# LOF_069 — gaining Sentinel (a copied keyword) and the Clone trait — and P1's ground arena stays empty.
## GIVEN
CommonSetup: rrk/bbw/{myResources:11;handCardIds:TWI_116}
P1OnlyActions: true
WithP2SpaceArena: LOF_069:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:LOF_069
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel
P1SPACEARENAUNIT:0:HASTRAIT:Clone
P1GROUNDARENACOUNT:0
