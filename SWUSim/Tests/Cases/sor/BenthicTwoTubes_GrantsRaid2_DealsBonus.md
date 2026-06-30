# SOR_156 Benthic "Two Tubes" (Aggression unit, cost 1, 2/2, Rebel/Trooper) — "On Attack: Another
# friendly [Aggression] unit gains Raid 2 for this phase." Benthic (idx1) attacks the base; its single
# eligible recipient SOR_164 (Aggression, 4/5, idx0) auto-receives Raid 2. SOR_164 then attacks the
# base and deals 4+2 = 6. Base total = 2 (Benthic) + 6 (SOR_164) = 8, and SOR_164 has the Raid keyword.

## GIVEN
CommonSetup: rrw/rrk/{}
WithP1GroundArena: SOR_164:1:0
WithP1GroundArena: SOR_156:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P2>Pass
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:8
P1GROUNDARENAUNIT:0:HASKEYWORD:Raid
