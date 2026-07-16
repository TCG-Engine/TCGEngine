# IgnoreOneAspectPenalty
#// LAW_020 Daimyo's Palace (Vigilance common base) — Epic Action: Play a card from your hand, ignoring 1
#//   of its Vigilance/Command/Aggression/Cunning aspect penalties. P1 (Vigilance base + Vigilance/Heroism
#//   leader) plays an off-aspect Aggression unit (SEC_161, cost 2) — normally cost 2 + 2 penalty = 4, but
#//   the base waives the Aggression pip → pays the printed 2. Epic is consumed.

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

---

# OnlyOnePipWaived
#// LAW_020 — only ONE battlefield-aspect pip is waived, and NOT an alignment pip. SOR_128 (Aggression,
#//   Villainy, cost 1) has penalty 4 (Aggression +2, Villainy +2). The base waives the Aggression pip
#//   only → effective cost 1 + 2 (Villainy) = 3. With exactly 3 resources it plays, leaving 0 ready
#//   (a "waive all" bug would cost 1, leaving 2 ready).

## GIVEN
CommonSetup: bbw/brk/{
  myBase:LAW_020
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: SOR_128

## WHEN
- P1>UseBaseAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_128
P1RESAVAILABLE:0
