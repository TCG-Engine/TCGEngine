# SOR_025 Tarkintown (Base) — "Epic Action: Deal 3 damage to a damaged non-leader
# unit." P1's base is Tarkintown. P2 has a damaged Consular Security Force (SOR_046,
# 3/7, 2 damage → targetable) and an undamaged Battlefield Marine (SOR_095, 0 damage
# → not targetable). The damaged unit is the sole target → auto-takes 3 (2+3 = 5);
# the undamaged Marine is untouched.

## GIVEN
CommonSetup: rrw/grw/{
  myBase:SOR_025
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:2    # damaged (2) → targetable, index 0
WithP2GroundArena: SOR_095:1:0    # undamaged → not targetable, index 1

## WHEN
- P1>UseBaseAbility

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5
P2GROUNDARENAUNIT:1:DAMAGE:0
P1BASE:EPICUSED
