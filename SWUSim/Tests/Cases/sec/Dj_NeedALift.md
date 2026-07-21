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

# NoFriendlyUnit_NoOp
#// SEC_018 DJ — the action needs a friendly unit to capture with. With none in play, the ability can't be
#// used: the leader stays ready and the hand unit is not played.
## GIVEN
CommonSetup: yyk/brk/{myLeader:SEC_018}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: SOR_095
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:READY
P1HANDCOUNT:1

---

# NoAffordableHandUnit_NoOp
#// SEC_018 DJ — needs a hand unit affordable at the -1 discount. With a captor present but only SOR_038
#// (Count Dooku, cost 7) in hand and 3 resources (7-1 = 6 > 3), the action can't be used.
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
P1LEADER:READY
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
