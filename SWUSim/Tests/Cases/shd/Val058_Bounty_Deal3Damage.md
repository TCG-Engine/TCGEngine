# SHD_058 Val — "Bounty — Deal 3 damage to a unit." P1's Consular Security Force (SOR_046 3/7)
# defeats P2's 2-damaged Val (2/4); P1 collects the bounty. The only unit left in play is SOR_046
# itself (single target → auto-resolve) which takes the 3: 2 counter + 3 bounty = 5 damage.
# Val's own When Defeated (P2's) fizzles — P2 has no other friendly unit.

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SHD_058:1:2    # Val, 2 damage — dies to the 3-power hit

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:5
