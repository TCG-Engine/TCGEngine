# PlayUnit_HeroismDiscount
#// SEC_257 Restore Freedom (event, cost 2) — Play a unit from your hand. It costs 1 resource less for
#//   each Heroism aspect icon among friendly units. SEC_041 (1 Heroism icon) gives -1, so SOR_046 (cost 4)
#//   plays for 3: with 5 resources, SEC_257 (2) + SOR_046 (3) = 5 → 0 left (unaffordable without the -1).

## GIVEN
CommonSetup: bbw/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP1Hand: SEC_257
WithP1Hand: SOR_046

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:0
