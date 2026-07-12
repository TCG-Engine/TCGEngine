# TWI_009 Maul (Leader, front) — "Action [Exhaust]: Attack with a unit. It gains Overwhelm for this
# attack." SOR_046 (power 3) attacks SOR_128 (3/1): it dies and the 2 excess damage overflows to the base.
## GIVEN
CommonSetup: rrk/bbw/{myLeader:TWI_009}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:2
P1GROUNDARENAUNIT:0:DAMAGE:3
