# TWI_135 Darth Maul (5/6) — the double attack is OPTIONAL and only applies to units. With two enemy
# units + a legal base, Maul is asked Base-vs-Units; choosing "Base" makes an ordinary base attack: deals
# his 5 to it, no unit multi-select follows, and both enemy units are left untouched.
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: [SOR_095:1:0 SEC_080:1:0]
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Base
## EXPECT
P2BASEDMG:5
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:DAMAGE:0
P1NODECISION
