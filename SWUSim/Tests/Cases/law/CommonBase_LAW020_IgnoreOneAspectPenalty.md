# LAW_020 Daimyo's Palace (Vigilance common base) — Epic Action: Play a card from your hand, ignoring 1
#   of its Vigilance/Command/Aggression/Cunning aspect penalties. P1 (Vigilance base + Vigilance/Heroism
#   leader) plays an off-aspect Aggression unit (SEC_161, cost 2) — normally cost 2 + 2 penalty = 4, but
#   the base waives the Aggression pip → pays the printed 2. Epic is consumed.

## GIVEN
CommonSetup: bbw/brk/{
  myBase:LAW_020
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SEC_161

## WHEN
- P1>UseBaseAbility

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SEC_161
P1RESAVAILABLE:0
P1BASE:EPICUSED
