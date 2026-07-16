# ReturnUpgradeAndShield
#// ASH_232 Full of Surprises (Event, cost 2) — Return an upgrade that costs 2 or less to its owner's hand,
#// then give a Shield token to a unit. SOR_120 (cost 2) on SOR_095 is the only ≤2 upgrade (auto-returned);
#// the Shield then goes to SOR_095 (the only unit, auto-targeted). SOR_095 reverts to 3 power and gains a Shield.
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_232}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
