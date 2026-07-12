# TWI_135 Darth Maul (5/6) — with only ONE eligible enemy unit, there is no double-attack option: it is
# an ordinary combat prompt (choose the unit OR the base), NOT a Base-vs-Units mode picker. Here Maul
# attacks the base normally → deals 5 to it, the lone enemy unit is untouched, and no extra prompt is
# left pending. (Sibling of the single-enemy test that attacks the unit.)
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:5
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
