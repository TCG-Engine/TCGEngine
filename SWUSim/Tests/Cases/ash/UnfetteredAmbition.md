# WhenPlayed_AdvantagePerUpgrade
#// ASH_182 Unfettered Ambition (Upgrade +1/+1) — When Played: for each upgrade on attached unit NOT named
#// Advantage (including this one), give an Advantage token to attached unit. Host SOR_095 starts with a
#// real upgrade (SOR_120) AND a pre-existing Advantage token (ASH_T02). Playing ASH_182 onto it counts
#// the non-Advantage upgrades = SOR_120 + ASH_182 (this one) = 2 (the existing Advantage token is
#// excluded), giving 2 new Advantage tokens → 3 Advantage tokens total. (Value 3 distinguishes the rule:
#// counting the Advantage token too would give 4; counting only "this one" would give 2.)
## GIVEN
CommonSetup: rrw/rrk/{myResources:2;handCardIds:ASH_182}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1GroundArenaUpgrade: 0:ASH_T02
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:3
