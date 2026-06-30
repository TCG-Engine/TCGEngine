# SOR_050 The Ghost (5/5, Space) — When Played/On Attack: You may give a Shield token to
# another SPECTRE unit. Tested via On Attack: The Ghost attacks the enemy base; the trigger
# offers a shield to another Spectre unit. The only other Spectre is Chopper (SOR_188) →
# auto-resolves and he gains a Shield. Battlefield Marine (non-Spectre) is NOT a valid
# target and stays unshielded — guards the Spectre trait filter.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1SpaceArena: SOR_050:1:0     # The Ghost (ready) — attacker, idx 0
WithP1GroundArena: SOR_188:1:0    # Chopper (Spectre) — idx 0, the only other Spectre
WithP1GroundArena: SOR_095:1:0    # Battlefield Marine (non-Spectre) — idx 1, must be ignored

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0
P2BASEDMG:5
