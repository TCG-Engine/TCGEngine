# ASH_068 Domesticated Loth-Cat (Ground, 1/3) — "Enemy units lose Ambush and Support." With Loth-Cat in
# play, the enemy ASH_046 (Support) and ASH_207 (Ambush) lose those keywords; a friendly ASH_207 keeps
# Ambush (only ENEMY units are affected).
## GIVEN
CommonSetup: bbw/bbk
WithP1GroundArena: ASH_068:1:0
WithP1GroundArena: ASH_207:1:0
WithP2GroundArena: ASH_046:1:0
WithP2GroundArena: ASH_207:1:0
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:ASH_207
P1GROUNDARENAUNIT:1:HASKEYWORD:Ambush
P2GROUNDARENAUNIT:0:CARDID:ASH_046
P2GROUNDARENAUNIT:0:NOTKEYWORD:Support
P2GROUNDARENAUNIT:1:CARDID:ASH_207
P2GROUNDARENAUNIT:1:NOTKEYWORD:Ambush
