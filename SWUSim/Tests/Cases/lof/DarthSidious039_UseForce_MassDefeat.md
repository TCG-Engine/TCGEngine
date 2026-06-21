# LOF_039 Darth Sidious (8/8) — When Played: you may use the Force → defeat each non-Sith unit with 3 or
# less remaining HP. P1 plays him with the Force; the enemy 3/1 and 3/3 (≤3 HP) are defeated, the 3/7
# survives, and Sidious himself (8 HP) is unaffected.

## GIVEN
CommonSetup: bbk/rrk/{myResources:12;handCardIds:LOF_039}
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1NOFORCE
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENACOUNT:1
