# ASH_135 The Darksaber — "While you are paying costs, the attached unit provides its aspect icons." P1
# (Cunning/Villainy, no Heroism) plays SOR_237 (cost 2, mono-Heroism) on exactly 2 resources: the +2
# off-aspect Heroism penalty is waived because the Darksaber-wearing SOR_046 (Vigilance/Heroism) provides
# Heroism. The unit enters play with 0 resources left.
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:SOR_237}
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_135
## WHEN
- P1>PlayHand:0
## EXPECT
P1SPACEARENACOUNT:1
P1RESAVAILABLE:0
