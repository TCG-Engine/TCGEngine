# ReforgeUpgrade
#// ASH_090 Reforge (Event, cost 2) — Defeat an upgrade on a friendly unit, then search the top 8 for an
#// upgrade that can attach to that unit and play it on that unit for 4 less. SOR_095 wears SOR_136 (the only
#// upgrade, auto-defeated); the search finds SOR_120 (+2/+2) and plays it on SOR_095 for free → power 5.
## GIVEN
CommonSetup: bbw/bbk/{myResources:2;handCardIds:ASH_090}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_136
WithP1Deck: [SOR_120 SOR_095 SOR_095 SOR_095]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_120
## EXPECT
P1GROUNDARENAUNIT:0:POWER:5

---

# SearchPool_ExcludesUnaffordable
#// ASH_090 Reforge — "Defeat an upgrade on a friendly unit. If you do, search the top 8 for an upgrade
#// that can attach to that unit… It costs 4 resources less." Same class of bug as Kelleran Beq (LOF_100):
#// the offered pool wasn't filtered by affordability (its host-target filter passed a null upgrade object,
#// which skips the affordability gate). Worse, an unaffordable pick was staged into hand and then failed to
#// pay — leaving the searched upgrade stuck in the player's hand. The playable set must exclude unaffordable
#// upgrades.
#//
#// P1 has SOR_095 carrying SOR_166 (the upgrade Reforge defeats — sole host + sole upgrade → auto). P1 has 2
#// resources; Reforge costs 2 (Vigilance, covered by the blue base) → 0 ready remain. Top of deck:
#//   - SOR_069 Resilient — cost 1 (Vigilance, covered) → max(0, 1−4) = 0 net → affordable, MUST be offered.
#//   - LOF_091 Craving Power — cost 5 (Command/Villainy, covered by Tarkin) → max(0, 5−4) = 1 net →
#//     UNaffordable, must NOT be offered.
#// Both can attach to the friendly SOR_095, so only affordability separates them. Decision left pending.

## GIVEN
CommonSetup: bgk/ggw/{myResources:2;handCardIds:ASH_090}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_166
WithP1Deck: SOR_069
WithP1Deck: LOF_091

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SEARCHPLAYABLEHAS:SOR_069
P1SEARCHPLAYABLENOT:LOF_091

---

# NoFriendlyUpgrade_NoEffect
#// ASH_090 Reforge — the whole effect is gated on defeating a friendly upgrade. With no upgrade in play, the
#// event fizzles: no search, and SOR_095 stays a plain 3-power unit.
## GIVEN
CommonSetup: bbw/bbk/{myResources:2;handCardIds:ASH_090}
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_120 SOR_095 SOR_095]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:3

---

# TakeNothing
#// ASH_090 Reforge — after defeating the upgrade the search is a "may": the player can take nothing.
#// SOR_136 (sole upgrade) is auto-defeated; P1 declines the search → SOR_095 ends with 0 upgrades.
## GIVEN
CommonSetup: bbw/bbk/{myResources:8;handCardIds:ASH_090}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_136
WithP1Deck: [SOR_120 SOR_095 SOR_095]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# ChooseWhichUpgradeToDefeat
#// ASH_090 Reforge — when the friendly unit has multiple upgrades the player picks which one to defeat.
#// SOR_095 wears SOR_136 and SOR_166; P1 defeats SOR_136 (myTempZone-0) → SOR_166 remains. Search declined.
## GIVEN
CommonSetup: bbw/bbk/{myResources:8;handCardIds:ASH_090}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_136
WithP1GroundArenaUpgrade: 0:SOR_166
WithP1Deck: [SOR_120 SOR_095 SOR_095]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_166

---

# OnlyFriendlyUpgradeChoosable
#// ASH_090 Reforge — "Defeat an upgrade on a FRIENDLY unit." An upgrade on an enemy unit is not a legal
#// target: only the friendly SOR_136 (auto-defeated) can be chosen; the enemy's SOR_120 is untouched.
## GIVEN
CommonSetup: bbw/bbk/{myResources:8;handCardIds:ASH_090}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_136
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
WithP1Deck: [SOR_095 SOR_095 SOR_095]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_120

---

# SearchExcludesAttachRestricted
#// ASH_090 Reforge — the search offers only upgrades that can legally attach to the reforged unit. SEC_256
#// Moral Authority requires a UNIQUE unit; SOR_095 (Battlefield Marine) is non-unique, so it is not playable.
## GIVEN
CommonSetup: bbw/bbk/{myResources:8;handCardIds:ASH_090}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_136
WithP1Deck: SEC_256
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1HASDECISION
P1SEARCHPLAYABLENOT:SEC_256

---

# SearchExcludesUnitCards
#// ASH_090 Reforge — the search looks for an UPGRADE; a unit card in the top 8 is not playable this way.
#// Deck top: SOR_120 (an upgrade, offered) and SOR_051 Luke Skywalker (a unit, not offered).
## GIVEN
CommonSetup: bbw/bbk/{myResources:8;handCardIds:ASH_090}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_136
WithP1Deck: SOR_120
WithP1Deck: SOR_051
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1HASDECISION
P1SEARCHPLAYABLEHAS:SOR_120
P1SEARCHPLAYABLENOT:SOR_051

---

# DefeatExperienceToken
#// ASH_090 Reforge — an Experience token (SOR_T01) counts as an upgrade and can be the one defeated.
#// SOR_095 wears a lone Experience token; it is auto-defeated, leaving the unit with no upgrades.
## GIVEN
CommonSetup: bbw/bbk/{myResources:8;handCardIds:ASH_090}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1Deck: [SOR_095 SOR_095 SOR_095]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:3

---

# OpponentControlledUpgradeOnFriendlyUnit_Defeatable
#// ASH_090 Reforge — "Defeat an upgrade on a FRIENDLY unit" looks at the UNIT's controller, not the upgrade's.
#// P2 attaches Condemn (SEC_038) onto P1's SOR_095 (a friendly unit). P1 then plays Reforge: the
#// opponent-controlled Condemn on the friendly unit is a legal target (auto-defeated to P2's discard). The
#// deck top holds only unit cards, so no replacement upgrade is found.
## GIVEN
CommonSetup: bbw/bbk/{myResources:8;handCardIds:ASH_090;theirResources:5;theirhandCardIds:SEC_038}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2DISCARDCOUNT:1
