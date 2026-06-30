# ASH_135 — control: without the Darksaber, SOR_046 provides no aspect icons, so P1 (Cunning/Villainy)
# faces the full +2 Heroism penalty on SOR_237 (cost 2 → 4) and cannot afford it on 2 resources — the
# unit stays in hand. Proves the aspect provision comes from the Darksaber.
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:SOR_237}
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:1
