# TWI_040 A Fine Addition — a PILOT can be played this way (user-confirmed ruling: A Fine Addition plays
# from a known zone, no "search for an upgrade" clause, so pilots qualify — unlike Reforge). JTL_046
# (Piloting [2], +2/+2) attaches as a Pilot to the only friendly Vehicle (SOR_237 Alliance X-Wing 2/3 →
# 4/5). Vigilance/Heroism is fully off-aspect here, so it is affordable at cost 2 (from 3) ONLY because
# the aspect penalty is ignored (unignored it would be 6).
## GIVEN
CommonSetup: brk/bbw/{myResources:3;handCardIds:TWI_040}
P1OnlyActions: true
WithP1Hand: JTL_046
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
## EXPECT
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HP:5
P1RESAVAILABLE:1
