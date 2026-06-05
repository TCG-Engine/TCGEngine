# SOR_168 Precision Fire (Aggression event, cost 1, Tactic) — "Attack with a unit. It gains Saboteur
# for this attack. If it's a TROOPER, it also gets +2/+0 for this attack." The chosen attacker is a
# Trooper (SOR_095, 3/3), so it gets +2/+0 → deals 5 to the enemy base. The buff is a one-shot for the
# attack (ObjectCurrentPower stays 3, only the dealt damage rises).

## GIVEN
CommonSetup: rrw/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SOR_168

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:EXHAUSTED
