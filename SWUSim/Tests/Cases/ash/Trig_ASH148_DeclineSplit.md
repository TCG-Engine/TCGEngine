# ASH_148 Ninth Sister — the damage is OPTIONAL ("you MAY deal damage..."). The opponent still discards
# (SOR_046, cost 4, their only card → auto), but P1 declines to assign any of the 4 damage (AnswerDecision:-).
# Nothing takes damage; the enemy SEC_080 and the played ASH_148 are both undamaged.
## GIVEN
CommonSetup: rrk/rrk/{
  myResources:7;
  myhandCardIds:ASH_148;
  theirHandCardIds:SOR_046
}
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P2DISCARDCOUNT:1
P1NODECISION
