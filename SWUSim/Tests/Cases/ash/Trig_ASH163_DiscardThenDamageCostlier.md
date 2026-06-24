# ASH_163 Reckless Sacrifice (Event, cost 2) — Discard a unit from your hand, then deal 5 damage to a unit
# that costs MORE than the discarded card. SOR_095 (cost 2) is the only hand unit (auto-discarded); SEC_135
# (cost 3, 4/3) is the only unit costing more than 2 (auto-targeted) and is defeated by the 5 damage.
## GIVEN
CommonSetup: rrw/rrk/{myResources:2;handCardIds:ASH_163,SOR_095}
WithP2GroundArena: SEC_135:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:2
