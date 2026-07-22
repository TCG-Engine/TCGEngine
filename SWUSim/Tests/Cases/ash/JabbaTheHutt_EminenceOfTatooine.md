# ReturnEnemyUpgrade_NoReplay
#// ASH_042 Jabba the Hutt — returning an ENEMY-owned upgrade sends it to the opponent's hand, and the free
#// replay is NOT offered (the upgrade did not return to YOUR hand). P1 returns SOR_120 off the enemy SEC_080
#// (which reverts to 3 power) and it lands in P2's hand.
## GIVEN
CommonSetup: byk/byk/{myResources:4;handCardIds:ASH_042}
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:POWER:3
P2HANDCOUNT:1

---

# ReturnOwnUpgrade_DeclineReplay
#// ASH_042 Jabba the Hutt — declining the free replay leaves the returned upgrade in P1's hand. P1 returns
#// its own SOR_120 (SOR_095 reverts to 3 power) but declines to replay it, so it stays in hand.
## GIVEN
CommonSetup: byk/byk/{myResources:4;handCardIds:ASH_042}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1HANDCOUNT:1

---

# ReturnOwnUpgrade_ReplayFree
#// ASH_042 Jabba the Hutt (Ground, 2/6, cost 4) — When Played: you may return an upgrade to its owner's
#// hand; if it's returned to YOUR hand, you may play it for free. P1 returns its own SOR_120 (+2/+2) off
#// SOR_095, then replays it free onto Jabba (Jabba 2 → 4 power; SOR_095 reverts to 3).
## GIVEN
CommonSetup: byk/byk/{myResources:4;handCardIds:ASH_042}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:1:POWER:4

---

# ReturnEnemyLeaderPilotDefeatsItToBase
#// ASH_042 Jabba the Hutt — "When Played: You may return an upgrade to its owner's hand." When the
#// chosen upgrade is an enemy LEADER attached as a Pilot, it can't go to hand: losing its host defeats
#// it and it returns to its owner's leader zone exhausted (a state-based consequence, not a direct
#// enemy-ability defeat, so a leader's enemy-immunity would not apply). P2 has JTL_012 Luke Skywalker
#// deployed as a Pilot on SOR_237; P1 plays Jabba and returns that pilot. Luke goes back to P2's base
#// (undeployed, exhausted), its host survives with no upgrades, and — since P1 doesn't own the returned
#// card — there is no free-replay offer.

## GIVEN
CommonSetup: ybk/rrw/{theirLeader:JTL_012;theirLeaderDeployedPilot:true}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: ASH_042
WithP1Resources: 4
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:UPGRADECOUNT:0
P2LEADER:NOTDEPLOYED
P2LEADER:EXHAUSTED
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_042
P1NODECISION
