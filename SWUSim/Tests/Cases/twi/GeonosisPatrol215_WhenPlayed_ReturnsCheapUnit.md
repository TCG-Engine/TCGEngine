# TWI_215 Geonosis Patrol Fighter (Unit 3/2, Space, cost 5) — "Exploit 2. When Played: You may return
# a non-leader unit that costs 3 or less to its owner's hand." Played with no friendly units (Exploit
# auto-skips); the only ≤3-cost non-leader unit is P2's SEC_080 (cost 3) → returned to P2's hand.

## GIVEN
CommonSetup: yyk/grw/{myResources:5;handCardIds:TWI_215}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
