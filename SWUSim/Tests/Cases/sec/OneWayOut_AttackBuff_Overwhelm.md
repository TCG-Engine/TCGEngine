# SEC_157 One Way Out (event) — Attack with a unit; it gets +1/+0 and gains Overwhelm for this attack.
#   P1's SOR_046 (3/7) attacks SOR_128 (3/1) via One Way Out: +1 → power 4, defeats the 1-HP defender,
#   and Overwhelm spills the 3 excess (4-1) to P2's base. (No +1 → only 2 would spill, so P2BASEDMG:3
#   proves the +1 AND the Overwhelm grant.) The attacker survives the 3 counter (7 HP).

## GIVEN
CommonSetup: rrw/grk/{myResources:1;handCardIds:SEC_157}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:3
P1GROUNDARENAUNIT:0:DAMAGE:3
