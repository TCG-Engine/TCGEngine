# OperationCinder
#// ASH_151 Operation Cinder (Event, cost 6) — Deal 5 damage to your base. Then, deal 5 damage to each
#// unit. P1's base takes 5; SOR_046 (3/7) survives with 5 damage; SEC_080 (3/3) is defeated.
## GIVEN
CommonSetup: rrk/rrk/{myResources:6;handCardIds:ASH_151}
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1BASEDMG:5
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:5
P2GROUNDARENACOUNT:0
