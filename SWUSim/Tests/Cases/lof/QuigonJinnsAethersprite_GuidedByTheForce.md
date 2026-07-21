# RepeatWhenPlayed
#// LOF_197 Qui-Gon Jinn's Aethersprite — On Attack: the next When-Played ability you use this phase may be
#// used again. LOF_197 attacks the base (arming the repeat); then LOF_133 (When Played: deal 2 to a Force
#// unit) is played and used twice on Plo Koon → 4 damage.

## GIVEN
CommonSetup: rrk/ggw/{myResources:10;handCardIds:LOF_133}
P1OnlyActions: true
WithP1SpaceArena: LOF_197:1:0
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:4

---

# RepeatWhenPlayedAsUpgrade
#// LOF_197 — a "When Played as an upgrade" (Piloting) ability also counts as a "When Played" ability, so
#// the repeat applies to it. Aethersprite attacks base (arms the repeat); Astromech Pilot (JTL_057) is
#// played as an upgrade on SOR_225, and its "heal 2" is used TWICE on the 4-damage SOR_046 → 0 damage.
## GIVEN
CommonSetup: bbk/bbk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: JTL_057
WithP1SpaceArena: LOF_197:1:0
WithP1SpaceArena: SOR_225:1:0
WithP1GroundArena: SOR_046:1:4
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:mySpaceArena-1
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# RepeatWhenPlayedAsUpgrade_Decline
#// The repeat is optional — declining it heals only once (4 → 2 damage).
## GIVEN
CommonSetup: bbk/bbk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: JTL_057
WithP1SpaceArena: LOF_197:1:0
WithP1SpaceArena: SOR_225:1:0
WithP1GroundArena: SOR_046:1:4
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:mySpaceArena-1
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:NO
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# RepeatSurvivesAetherspriteDefeat
#// Official ruling (2025-07-14): once the "On Attack" repeat triggers, it still lets you reuse the next
#// When Played ability even if the Aethersprite is defeated. LOF_197 (3/6) attacks a Jedi Light Cruiser
#// (6/7) and dies — but the armed repeat persists (it's a player effect, not tied to the unit): LOF_133's
#// "deal 2 to a Force unit" is used twice on Plo Koon → 4 damage, with the Aethersprite already gone.
## GIVEN
CommonSetup: rrk/ggw/{myResources:10}
P1OnlyActions: true
WithP1SpaceArena: LOF_197:1:0
WithP2SpaceArena: JTL_251:1:0
WithP1GroundArena: LOF_050:1:0
WithP1Hand: LOF_133
## WHEN
- P1>AttackSpaceArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:4

---

# DeclineRepeat_NormalWhenPlayed
#// FT: "you may choose not to use the ability again" — the repeat is optional on a NORMAL (non-upgrade)
#// When-Played too. LOF_197 attacks base (arms the repeat); TWI_110 Huyang is played, buffing friendly
#// SOR_095 (3/3) +2/+2 → 5/5; declining the repeat leaves it at 5/5 (not 7/7).
## GIVEN
CommonSetup: ggw/ggw/{myResources:16}
P1OnlyActions: true
WithP1SpaceArena: LOF_197:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: TWI_110
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:NO
## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5

---

# OnlyNextWhenPlayed_LaterNotRepeated
#// FT: the repeat applies only to the NEXT When-Played ability used this phase; a later one is not
#// repeated. LOF_197 attacks base (arms once). LOF_133's "deal 2 to a Force unit" on Plo Koon (LOF_050)
#// is repeated → 4 damage. Then Imperial Interceptor (SOR_132) is played; its "deal 3 to a space unit"
#// is NOT repeated — the enemy JTL_251 (6/7) takes 3 damage only.
## GIVEN
CommonSetup: ggw/ggw/{myResources:20}
P1OnlyActions: true
WithP1SpaceArena: LOF_197:1:0
WithP1GroundArena: LOF_050:1:0
WithP2SpaceArena: JTL_251:1:0
WithP1Hand: LOF_133
WithP1Hand: SOR_132
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:4
P2SPACEARENAUNIT:0:DAMAGE:3

---

# NoRepeat_ShieldedKeyword
#// FT: "does not trigger for Shielded" — a keyword ability (Shielded) is not a "When Played" ability,
#// so it does NOT consume the armed repeat. LOF_197 attacks base (arms); Crafty Smuggler (SOR_207,
#// Shielded) is played and gains its shield without offering a repeat; the arm survives, so the NEXT
#// real When-Played (LOF_133 deal 2 to a Force unit) is still repeatable → Plo Koon takes 2+2 = 4.
## GIVEN
CommonSetup: ggw/ggw/{myResources:20}
P1OnlyActions: true
WithP1SpaceArena: LOF_197:1:0
WithP1GroundArena: LOF_050:1:0
WithP1Hand: SOR_207
WithP1Hand: LOF_133
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:1:CARDID:SOR_207
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1

---

# Coordinate_WhenPlayed_Repeated
#// FT: works with "Coordinate - When Played" abilities. LOF_197 attacks base (arms); Pelta Supply Frigate
#// (TWI_095) enters with Coordinate active (4 friendly units incl. itself) → create a Clone Trooper token;
#// the repeat makes a SECOND one. Ground arena: 2 seeded units + 2 Clone Troopers = 4.
## GIVEN
CommonSetup: ggw/ggw/{myResources:20}
P1OnlyActions: true
WithP1SpaceArena: LOF_197:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: TWI_095
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1SPACEARENACOUNT:2
P1GROUNDARENACOUNT:4

---

# ImperialInterceptor_SelfDefeatThenRepeat
#// FT: "Imperial Interceptor (defeating itself when played)". LOF_197 attacks base (arms); Imperial
#// Interceptor (SOR_132) enters and its "may deal 3 to a space unit" is aimed at ITSELF (2 HP) → defeated.
#// The armed repeat persists though the Interceptor left play: the repeated instance deals 3 to the enemy
#// Green Squadron A-Wing (SOR_141, 1/3) → defeated. Both leave play.
## GIVEN
CommonSetup: ggw/ggw/{myResources:20}
P1OnlyActions: true
WithP1SpaceArena: LOF_197:1:0
WithP2SpaceArena: SOR_141:1:0
WithP1Hand: SOR_132
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-1
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:LOF_197
P2SPACEARENACOUNT:0

---

# WhenPlayedAsUnit_Repeated
#// FT: works with "When played as a unit" abilities. LOF_197 attacks base (arms); Poe Dameron (JTL_100)
#// is played as a Unit → creates an X-Wing token (JTL_T02); the optional free-attach is declined. The
#// repeat runs the When-Played-as-unit ability again → a SECOND X-Wing token. Space arena: LOF_197 + 2
#// tokens = 3; Poe remains a ground unit.
## GIVEN
CommonSetup: ggw/ggw/{myResources:20}
P1OnlyActions: true
WithP1SpaceArena: LOF_197:1:0
WithP1Hand: JTL_100
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:Unit
- P1>AnswerDecision:-
- P1>AnswerDecision:YES
- P1>AnswerDecision:-
## EXPECT
P1SPACEARENACOUNT:3
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_100

---

# DoubleTrigger_AttackedTwice
#// FT: "can be double-triggered by a When-Played ability if the Aethersprite has attacked twice." LOF_197
#// attacks base (arm #1), then Dogfight (JTL_123) lets it attack the enemy A-Wing (arm #2). Pelta Supply
#// Frigate (TWI_095) enters with Coordinate active → create a Clone Trooper; BOTH arms are consumed, so
#// the ability resolves two more times → 3 Clone Troopers. Ground: 2 seeded + 3 tokens = 5.
## GIVEN
CommonSetup: ggw/ggw/{myResources:20}
P1OnlyActions: true
WithP1SpaceArena: LOF_197:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_141:1:0
WithP1Hand: JTL_123
WithP1Hand: TWI_095
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENACOUNT:2
P1GROUNDARENACOUNT:5

---

# TwoDifferentAbilities_AttackBetween
#// FT: "can be used on two different When-Played abilities if the Aethersprite attacks again in between."
#// LOF_197 attacks base (arm #1). LOF_133's "deal 2 to a Force unit" on Plo Koon (LOF_050) is repeated →
#// 4 damage (arm #1 consumed). Dogfight sends LOF_197 at the enemy Green A-Wing (arm #2), defeating it.
#// Then Imperial Interceptor (SOR_132) is played and its "deal 3 to a space unit" is repeated by arm #2 →
#// the enemy Jedi Light Cruiser (JTL_251, 6/7) takes 6.
## GIVEN
CommonSetup: ggw/ggw/{myResources:24}
P1OnlyActions: true
WithP1SpaceArena: LOF_197:1:0
WithP1GroundArena: LOF_050:1:0
WithP2SpaceArena: SOR_141:1:0
WithP2SpaceArena: JTL_251:1:0
WithP1Hand: LOF_133
WithP1Hand: JTL_123
WithP1Hand: SOR_132
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:theirSpaceArena-0
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:4
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:JTL_251
P2SPACEARENAUNIT:0:DAMAGE:6

---

# WhenPlayedOnAttack_Repeated_WhenPlayedPortion
#// FT: works with "When Played/On Attack" abilities (the When-Played portion repeats). LOF_197 attacks
#// base (arms); Reinforcement Walker (SOR_119) enters → its When-Played "look at the top card: draw it,
#// or discard it and heal 3" first DRAWS the top card (SOR_095); the repeat runs it again and DISCARDS
#// the new top (SOR_128), healing 3 from P1's base (3 → 0).
## GIVEN
CommonSetup: ggw/ggw/{myResources:12;myBaseDamage:3}
P1OnlyActions: true
WithP1SpaceArena: LOF_197:1:0
WithP1Hand: SOR_119
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_128
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:Draw
- P1>AnswerDecision:YES
- P1>AnswerDecision:Discard
## EXPECT
P1HANDCOUNT:1
P1BASEDMG:0
P1DECKCOUNT:1
P1DISCARDCOUNT:1

---

# Smuggle_WhenPlayed_Repeated
#// FT: the repeat also applies to a card played via SMUGGLE. LOF_197 attacks base (arms the repeat). P1 then
#// smuggles SHD_113 Privateer Crew (Smuggle [6 Command]; "When played using Smuggle: give 3 Experience to
#// this unit"). The repeat re-uses that smuggle When-Played ability → 3 + 3 = 6 Experience tokens on it.
## GIVEN
CommonSetup: ggw/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LOF_197:1:0
WithP1Resources: 1:SHD_113:1,6:SOR_251:1
WithP1Deck: [SOR_095 SOR_095]
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>SmuggleResource:0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_113
P1GROUNDARENAUNIT:0:UPGRADECOUNT:6

---

# MultiWhenPlayed_RepeatsOnlyFirstAbility
#// FT "should work with multiple When Played abilities": the repeat re-uses only the FIRST When-Played
#// ability, not every window. LOF_197 attacks (arms). LOF_070 Anakin has TWO When-Played windows (heroism
#// -3/-3 :0, villainy -3/-3 :1; both active with a Heroism + Villainy card in discard). Resolving gives
#// window0 + window1 + REPEAT of window0 = 3 defeats, NOT 4. Three 3/3 SOR_095 all drop to 0/0 (reindex to 0).
## GIVEN
CommonSetup: bbk/ggw/{myResources:6;discardCardIds:SOR_095,SEC_080}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LOF_197:1:0
WithP1Hand: LOF_070
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0

---

# HuyangAura_StacksOnRepeat
#// FT: repeating a "+X/+X while in play" When-Played (Huyang TWI_110) STACKS as a second continuous effect.
#// LOF_197 attacks (arms). Huyang enters; choose SOR_046 (ground) → +2/+2. The repeat re-applies +2/+2 to the
#// same unit → +4/+4 total → 7/11. (LOF_197 in space is also "another friendly unit", so the target prompts.)
## GIVEN
CommonSetup: ggw/ggw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LOF_197:1:0
WithP1GroundArena: SOR_046:1:0
WithP1Hand: TWI_110
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:11
