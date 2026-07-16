# Reprint_Cunning
#// LAW_028 Canto Bight (Cunning common base) — same Epic Action (reprint). Cunning base + Vigilance/
#//   Heroism leader plays an off-aspect Aggression unit (SEC_161, cost 2) at the printed 2 (Aggression
#//   penalty waived). Confirms a different base in the set shares the wiring.

## GIVEN
CommonSetup: ybw/brk/{
  myBase:LAW_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SEC_161

## WHEN
- P1>UseBaseAbility

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SEC_161
P1RESAVAILABLE:0
P1BASE:EPICUSED
