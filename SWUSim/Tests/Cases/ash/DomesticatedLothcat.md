# EnemyLoseAmbushSupport
#// ASH_068 Domesticated Loth-Cat (Ground, 1/3) — "Enemy units lose Ambush and Support." With Loth-Cat in
#// play, the enemy ASH_046 (Support) and ASH_207 (Ambush) lose those keywords; a friendly ASH_207 keeps
#// Ambush (only ENEMY units are affected).
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

---

# PreventEnemyAmbushOnPlay
#// ASH_068 Domesticated Loth-Cat — an enemy unit played while Loth-Cat is in play has no Ambush, so it
#// cannot attack on entry. P2 plays SOR_149 Mace Windu (Ambush, 5 power); with Ambush stripped it does not
#// get to attack P1's Loth-Cat, which stays undamaged.
## GIVEN
CommonSetup: bbw/rrw/{theirResources:10}
WithP1GroundArena: ASH_068:1:0
WithP2Hand: SOR_149
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
## WHEN
- P2>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_068
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:1

---

# AllowFriendlyAmbushOnPlay
#// ASH_068 Domesticated Loth-Cat only strips ENEMY units — a friendly unit keeps Ambush. P1 (who controls
#// Loth-Cat) plays SOR_149 Mace Windu (Ambush, 5 power), which Ambush-attacks the enemy SOR_164 Wampa (4/5)
#// and defeats it.
## GIVEN
CommonSetup: rrw/rrk/{myResources:10;handCardIds:SOR_149}
WithP1GroundArena: ASH_068:1:0
WithP2GroundArena: SOR_164:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SOR_149

---

# PreventEnemySupportOnPlay
#// ASH_068 Domesticated Loth-Cat — an enemy unit played while Loth-Cat is in play has no Support, so it
#// cannot trigger a bonus attack on entry. P2 plays ASH_072 Doctor Pershing (Support); with Support
#// stripped, P2's SOR_095 Battlefield Marine does not attack, staying ready, and P1's base takes no damage.
## GIVEN
CommonSetup: bbw/bbk/{theirResources:10}
WithP1GroundArena: ASH_068:1:0
WithP2GroundArena: SOR_095:1:0
WithP2Hand: ASH_072
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
## WHEN
- P2>PlayHand:0
## EXPECT
P1BASEDMG:0
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:READY

---

# AllowFriendlySupportOnPlay
#// ASH_068 Domesticated Loth-Cat only strips ENEMY units — a friendly unit keeps Support. P1 (who controls
#// Loth-Cat) plays ASH_072 Doctor Pershing (Support) and uses it to attack the enemy base with the friendly
#// SOR_095 Battlefield Marine (3 power): the base takes 3 and the Marine exhausts.
## GIVEN
CommonSetup: bbw/bbk/{myResources:10;handCardIds:ASH_072}
WithP1GroundArena: [ASH_068:1:0 SOR_095:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:EXHAUSTED
