# LAW_156 Hunter For Hire — the cost is "defeat a friendly Credit token." With NO Credit token, the
# opponent can't pay, so the action is a full no-op: control does not change. P2 has no Credit, so its
# attempt to use the action on the enemy Hunter For Hire does nothing (P1 keeps control).

## GIVEN
P1LeaderBase: JTL_002/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: LAW_156:1:0

## WHEN
- P2>UseUnitAbility:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_156
P2GROUNDARENACOUNT:0
