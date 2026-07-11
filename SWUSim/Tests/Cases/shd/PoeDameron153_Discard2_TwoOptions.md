# SHD_153 Poe Dameron (Unit, Ground, cost 5, Aggression/Heroism, 6/6)
#   "On Attack: Discard up to 3 cards from your hand. For each card discarded this way, choose a different
#    option: Deal 2 damage to a unit or base / Defeat an upgrade / An opponent discards a card."
# Poe attacks SOR_046 (survives the 6 combat damage). On Attack, P1 discards 2 hand cards and picks 2
# distinct options: Deal 2 to P2's base, then P2 discards a card. Base takes 2 (from the option, not
# combat), P1 hand empties into discard (2), and P2's 1-card hand is discarded.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_153:1:0
WithP1Hand: SOR_095
WithP1Hand: SEC_080
WithP2GroundArena: SOR_046:1:0
WithP2Hand: SOR_128

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myHand-0&myHand-1
- P1>AnswerDecision:Deal2
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:OppDiscard

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6
P2BASEDMG:2
P1HANDCOUNT:0
P1DISCARDCOUNT:2
P2HANDCOUNT:0
P2DISCARDCOUNT:1
