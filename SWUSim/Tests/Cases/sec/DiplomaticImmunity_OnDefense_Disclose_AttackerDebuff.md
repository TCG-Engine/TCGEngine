# SEC_052 Diplomatic Immunity (Upgrade +2/+2, Vigilance/Heroism) — grants the host:
#   "When this unit is attacked: you may disclose VigilanceVigilanceHeroismHeroism → the attacker gets
#   -2/-0 for this attack." (Granted On Defense reaction via the onDefenseFromUpgrade seam.)
# P2's host SOR_046 (3/7) + SEC_052 = 5/9. P1's SOR_046 (3/7, power 3) attacks it; before damage P2
# discloses 2x SOR_046 (Vigilance,Heroism → covers VVHH) → attacker becomes power 1 for this attack.
# Host takes only 1 (3-2); host counters 5 onto attacker. After the attack the debuff expires, so the
# attacker's POWER is back to 3 (proves SWU_DUR_ATTACK duration, not a lingering phase debuff).

## GIVEN
CommonSetup: ggw/ggw/{theirHandCardIds:SOR_046,SOR_046}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SEC_052

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P2>AnswerDecision:myHand-0&myHand-1

## EXPECT
P2GROUNDARENAUNIT:0:POWER:5
P2GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:0:POWER:3
P2HANDCOUNT:2
