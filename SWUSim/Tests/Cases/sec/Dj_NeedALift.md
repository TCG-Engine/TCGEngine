# LeaderAction_PlayUnitCaptured
#// SEC_018 DJ (leader) — Action [Exhaust]: Choose a friendly unit. Play a unit from your hand (costs 1
#// less). The chosen unit captures it. P1's SOR_095 (the captor) captures the just-played SOR_128, so
#// SOR_128 is NOT a separate arena unit (ground count stays 1) — it rides SOR_095 as a captive subcard.
#// Generous resources avoid aspect-penalty math on the played unit.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:SEC_018;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: SOR_128
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1HANDCOUNT:0
P1LEADER:EXHAUSTED

---

# NoFriendlyUnit_UsableNoEffect
#// SEC_018 DJ — CR 6.4.587.c: the [Exhaust] cost changes game state, so the Action is usable even with no
#// friendly unit to capture with. It exhausts the leader and does nothing (no unit played, hand unchanged).
## GIVEN
CommonSetup: yyk/brk/{myLeader:SEC_018}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: SOR_095
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
P1HANDCOUNT:1

---

# NoAffordableHandUnit_UsableNoEffect
#// SEC_018 DJ — CR 6.4.587.c: usable even when no hand unit is affordable at the -1 discount (only SOR_038,
#// cost 7, in hand with 3 resources). The [Exhaust] cost changes game state; the leader exhausts and no unit
#// is played (hand unchanged).
## GIVEN
CommonSetup: yyk/brk/{myLeader:SEC_018}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SOR_038
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
P1HANDCOUNT:1

---

# CaptureResolvesBeforeWhenPlayed
#// SEC_018 DJ — per CR the leader ability resolves COMPLETELY (play + capture) before the played unit's When
#// Played triggers. DJ plays SHD_161 Stolen Landspeeder ("When Played: an opponent takes control of it") and
#// captures it: because the capture resolves first, the unit is out of play when its When Played drains, so
#// the "opponent takes control" fizzles — the opponent never gains it. (Same ordering as SHD_013 Han's
#// deal-2-before-When-Played; a WRONG order would hand SHD_161 to P2.)
## GIVEN
CommonSetup: yyk/rrk/{myLeader:SEC_018}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SHD_161
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1HANDCOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENACOUNT:0

---

# WhenPlayedAbilityResolvesAfterCapture
#// SEC_018 DJ (leader) — the played unit's When Played resolves AFTER it is captured, but a When Played
#// that acts on ANOTHER unit still resolves. DJ plays SOR_202 Cantina Bouncer (Cunning, cost 5 → 4 with
#// the −1) and has SOR_210 Swoop Racer capture it. Cantina Bouncer's "return a non-leader unit to hand"
#// then still fires (it targets AT-ST, not itself), bouncing SOR_232 to P2's hand. Swoop Racer carries the
#// captured Bouncer (UPGRADECOUNT 1) and stays the lone friendly ground unit.
## GIVEN
CommonSetup: yyw/bbk/{myLeader:SEC_018;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1GroundArena: SOR_210:1:0
WithP1Hand: SOR_202
WithP2GroundArena: SOR_232:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_210
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P1HANDCOUNT:0
P1LEADER:EXHAUSTED

---

# PlaysOneCostForFree_NoReadyResources
#// SEC_018 DJ (leader) — the played unit costs 1 resource less, so a 1-cost unit is free. With all 5
#// resources exhausted (0 ready), DJ plays JTL_211 Independent Smuggler (cost 1 → 0) and SOR_210 Swoop
#// Racer captures it. No resources are spent (RESAVAILABLE stays 0) and the Smuggler rides Swoop Racer as
#// a captive.
## GIVEN
CommonSetup: yyw/bbk/{myLeader:SEC_018;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5:SOR_284:0
WithP1GroundArena: SOR_210:1:0
WithP1Hand: JTL_211
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_210
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1HANDCOUNT:0
P1RESAVAILABLE:0
P1LEADER:EXHAUSTED

---

# Deployed_EnemyRescuedFromFriendlyCaptor_NotReady
#// SEC_018 DJ (deployed) — "Friendly units that are rescued enter play ready." The bonus is keyed to the
#// RESCUED unit's owner controlling a deployed DJ. An ENEMY unit rescued from a friendly captor does NOT
#// get it. P1 (deployed DJ) plays SHD_120 Discerning Veteran, capturing SOR_232 AT-ST. P2 plays SOR_222
#// Waylay to return Discerning Veteran to P1's hand; AT-ST is rescued to P2 — but its owner (P2) has no
#// deployed DJ, so it enters exhausted.
## GIVEN
CommonSetup: yyw/ggk/{myLeader:SEC_018:1:1:1;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 8
WithP2Resources: 6
WithP1Hand: SHD_120
WithP2Hand: SOR_222
WithP2GroundArena: SOR_232:1:0
## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_232
P2GROUNDARENAUNIT:0:EXHAUSTED
P1LEADER:DEPLOYED

---

# Deployed_FriendlyRescuedFromEnemyCaptor_EntersReady
#// SEC_018 DJ (deployed) — a FRIENDLY unit rescued from an enemy captor enters play READY. P2 plays SHD_120
#// Discerning Veteran, capturing P1's SOR_095 Battlefield Marine. P1 plays SOR_222 Waylay to return the
#// Discerning Veteran to P2's hand; SOR_095 is rescued to P1, whose deployed DJ makes it enter ready.
## GIVEN
CommonSetup: yyw/ggk/{myLeader:SEC_018:1:1:1;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithP1Resources: 6
WithP2Resources: 8
WithP1GroundArena: SOR_095:1:0
WithP2Hand: SHD_120
WithP1Hand: SOR_222
## WHEN
- P2>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:READY
P1LEADER:DEPLOYED

---

# IG11PlayedViaDj_FriendlyCapture_DefeatsAndDeals3ToEnemyGround
#// SHD_170 IG-11 — "If this unit would be captured, defeat him and deal 3 damage to each enemy ground unit
#// instead." is ABSOLUTE (any captor). DJ SEC_018 plays IG-11 from hand and has friendly SOR_095 capture it —
#// a FRIENDLY capture — so IG-11's replacement still fires: IG-11 is defeated (SOR_095 gains no captive) and
#// each ENEMY (P2) ground unit takes 3, while the friendly captor is untouched.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:SEC_018;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: SHD_170
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# NoUnitsInHand_UsableNoEffect
#// SEC_018 DJ — CR 6.4.587.c mirror of NoAffordableHandUnit_UsableNoEffect: the Action is usable with an
#// EMPTY hand too (the [Exhaust] cost changes game state), it just does nothing. DJ exhausts, the friendly
#// unit is untouched and captures nothing.
## GIVEN
CommonSetup: yyk/brk/{myLeader:SEC_018}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: SOR_046:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# Deployed_FriendlyRescuedFromFRIENDLYCaptor_EntersReady
#// SEC_018 DJ (deployed) — "Friendly units that are rescued enter play ready" is about the RESCUED unit's
#// controller, not about who held it. Completes the captor matrix alongside
#// Deployed_FriendlyRescuedFromEnemyCaptor_EntersReady: here the captor is FRIENDLY. P1's Escape Pod
#// (SEC_056, "When Played: you may have this unit capture a friendly non-Vehicle, non-leader unit")
#// captures P1's own Battlefield Marine; P2 then Waylays (SOR_222) the Escape Pod, releasing the Marine
#// back to P1 — who has DJ deployed, so it enters READY.
## GIVEN
CommonSetup: yyw/ggk/{myLeader:SEC_018:1:1:1;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 6
WithP2Resources: 8
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_056
WithP2Hand: SOR_222
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-1
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:READY
P1LEADER:DEPLOYED

---

# Deployed_FriendlyRescuedFromABASECaptor_EntersReady
#// SEC_018 DJ (deployed) — "friendly units that are RESCUED enter play ready" is about the rescue, not
#// about who was holding the captive, so it applies to a unit freed from a BASE just as much as from a
#// unit. P2 plays SEC_195 Arrest so THEIR base captures P1's SOR_095; at the start of the regroup phase
#// its owner P1 rescues it, and because P1 has a deployed DJ it comes back READY instead of the usual
#// exhausted (CR 8.34.3).
#// Base captives live in a GlobalEffects flag rather than a Subcards slot, so this is a genuinely
#// different rescue path from the unit-captor sections above.

## GIVEN
CommonSetup: yyw/yyk/{myLeader:SEC_018:1:1:1;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 5
WithP1GroundArena: SOR_095:1:0
WithP2Hand: SEC_195
WithP1Deck: [SOR_046 SOR_046 SOR_046]
WithP2Deck: [SOR_046 SOR_046 SOR_046]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:READY

---

# DoubleCunningLeader_CoversADoubleCunningCard_NoAspectPenalty
#// SEC_018 DJ is himself a DOUBLE-Cunning leader ([Cunning, Cunning]), so he supplies TWO Cunning icons —
#// enough to match BOTH icons on a double-Cunning card. P1 plays TWI_202 Jar Jar Binks (cost 2,
#// [Cunning, Cunning]) from hand holding exactly 2 resources and ends at 0: no surcharge. Counting
#// DISTINCT aspects instead of icons would leave the second Cunning unmatched, add +2, and make the play
#// unaffordable — so the exact-2 spend is the discriminator. (Plain hand play; DJ's Action is not used.)

## GIVEN
CommonSetup: gyk/grw/{myLeader:SEC_018;myResources:2;handCardIds:TWI_202}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_202
P1RESAVAILABLE:0

---

# PlayedUnitsWhenPlayedFIZZLES_BecauseItIsCapturedOutOfPlay
#// SEC_018 DJ — "(When Played abilities resolve after the unit is captured.)" A When Played ability that
#// refers to the played unit itself therefore has no unit to work with: the card is a captive, not in
#// play, so the ability fizzles. P1's SOR_210 Swoop Racer captures the just-played SHD_120 Discerning
#// Veteran, whose own "When Played: This unit captures an enemy non-leader ground unit" does nothing —
#// P2's AT-ST is untouched and still in its arena. P1 pays 4 (cost 5, reduced by 1) and DJ exhausts.

## GIVEN
CommonSetup: gyk/grw/{myLeader:SEC_018;myResources:5;handCardIds:SHD_120}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_210:1:0
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_210
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_232
P1RESAVAILABLE:1
P1HANDCOUNT:0
P1LEADER:EXHAUSTED

---

# UniquenessPrompt_DefeatThePreExistingCopy_NewCopyStaysCaptured
#// SEC_018 DJ + CR 1050.3 — playing a second copy of a unique unit forces its controller to defeat one of
#// them immediately. P1 already controls TWI_202 Jar Jar Binks and plays a second copy through DJ, with
#// SOR_210 Swoop Racer as the captor: the uniqueness prompt offers BOTH copies. Choosing the pre-existing
#// one sends it to the discard and leaves the new copy riding the Swoop Racer as a captive. P1 pays 1
#// (cost 2, reduced by 1).
#// The other branch (defeating the newly played copy) is covered by
#// UniquenessPrompt_DefeatTheNewlyPlayedCopy_NothingIsCaptured below. It used to be broken — the capture
#// ran ahead of the pending uniqueness choice — and was fixed 2026-08-09.

## GIVEN
CommonSetup: gyk/grw/{myLeader:SEC_018;myResources:5;handCardIds:TWI_202}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_210:1:0
WithP1GroundArena: TWI_202:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_210
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1DISCARDCOUNT:1
P1RESAVAILABLE:4
P1LEADER:EXHAUSTED

---

# UniquenessPrompt_DefeatTheNewlyPlayedCopy_NothingIsCaptured
#// SEC_018 DJ + CR 1050.3 — the mirror of the section above, and the branch that used to be broken.
#// P1 controls TWI_202 Jar Jar Binks and plays a second copy through DJ with SOR_210 Swoop Racer as the
#// captor. The uniqueness prompt offers both copies; this time P1 defeats the JUST-PLAYED one.
#// Because the uniqueness defeat "occurs immediately" (CR 1050.3) it must RESOLVE BEFORE DJ's capture —
#// so there is nothing left for the Swoop Racer to capture and it ends with zero captives.
#// Bug this pins: the capture used to run inline, ahead of the still-pending uniqueness choice. That
#// re-indexed the arena underneath a positional offer built when three units were present, so answering
#// with the new copy hit an out-of-range slot, did nothing, and the MANDATORY defeat was skipped entirely
#// — leaving the player controlling two copies of a unique unit.
#// P1 pays 1 (Jar Jar costs 2, reduced by 1), so 4 of 5 resources remain, and DJ exhausts.

## GIVEN
CommonSetup: gyk/grw/{myLeader:SEC_018;myResources:5;handCardIds:TWI_202}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_210:1:0
WithP1GroundArena: TWI_202:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-2

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_210
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:CARDID:TWI_202
P1DISCARDCOUNT:1
P1RESAVAILABLE:4
P1LEADER:EXHAUSTED
P1NODECISION

---

# Deployed_EnemyRescuedFromENEMYCaptor_NotReady
#// SEC_018 DJ (deployed) — "Friendly units that are rescued enter play ready" is keyed to the RESCUED
#// unit's owner having the deployed DJ, not to who owned the captor. This is the fourth corner of that
#// matrix: an ENEMY unit rescued from an ENEMY captor.
#// P1 (deployed DJ) plays SHD_120 Discerning Veteran, which captures P2's SOR_232 AT-ST. P2 then plays
#// SOR_224 Change of Heart to TAKE CONTROL of the Discerning Veteran, so the captor is now P2's. P1 plays
#// SOR_202 Cantina Bouncer and returns the Discerning Veteran to its owner's hand, rescuing the AT-ST.
#// The AT-ST goes back to P2, who has no deployed DJ, so it enters EXHAUSTED.
#// P1's ground ends as the deployed DJ + Cantina Bouncer. SHD_120 is off-aspect here (+2), hence the
#// generous resource budget.

## GIVEN
CommonSetup: yyw/ggk/{myLeader:SEC_018:1:1:1;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 14
WithP2Resources: 10
WithP1Hand: SHD_120
WithP1Hand: SOR_202
WithP2Hand: SOR_224
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_232
P2GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENACOUNT:2
P1LEADER:DEPLOYED
