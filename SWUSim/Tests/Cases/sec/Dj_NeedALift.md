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
