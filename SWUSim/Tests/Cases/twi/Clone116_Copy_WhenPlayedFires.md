# TWI_116 Clone — the copied card's abilities are part of its printed attributes (CR 9.2), so when Clone
# enters play AS a copy, the copied card's "When Played" fires on that entry. Clone copies SHD_160 (2/1,
# "When Played: Deal 1 damage to each base") → as Clone enters as SHD_160, its When Played deals 1 to
# EACH base. Clone is now a 2/1 SHD_160.
## GIVEN
CommonSetup: rrk/bbw/{myResources:11;handCardIds:TWI_116}
P1OnlyActions: true
WithP2GroundArena: SHD_160:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1BASEDMG:1
P2BASEDMG:1
P1GROUNDARENAUNIT:0:CARDID:SHD_160
P1GROUNDARENAUNIT:0:HASTRAIT:Clone
