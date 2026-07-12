# TWI_039 Malevolence (Unit 7/7, Space, cost 9) — "Exploit 4. Restore 2. When Played: Give an enemy
# unit -4/-0 for this phase. It can't attack for this phase." P1 has no units, so Exploit auto-skips.
# P2's SOR_095 (3/3) is buffed to 5 power by an attached SOR_120 (+2/+2). Malevolence's When Played gives
# it -4/-0 → power 1, and marks it can't-attack. When P2 then attacks P1's base with it, the attack is a
# no-op (0 base damage) — proving both the debuff (power 1) AND the can't-attack.

## GIVEN
CommonSetup: bbk/grw/{myResources:9;handCardIds:TWI_039}
WithActivePlayer: 1
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P2>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:POWER:1
P1BASEDMG:0
