# RestoreGrantAndBounce
#// TS26_37 Abandoned the Order (Upgrade +1/+1, Cunning/Vigilance) — Attached unit loses the Jedi trait
#// and gains Restore 1. When Played: you may return a non-leader unit to its owner's hand. Attaching to
#// LAW_124 makes it 5/8 with Restore; the When-Played bounce returns the enemy SEC_080 to hand.
## GIVEN
CommonSetup: byk/rrk/{myResources:4;handCardIds:TS26_37}
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HASKEYWORD:Restore
