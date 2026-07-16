# Asajj_HostDefeatedByEffect_ReturnsToZone
#// JTL_001 Asajj deployed as pilot on SOR_225 (TIE/ln Fighter, Space).
#// P2 plays SOR_077 Takedown ("Defeat a unit with 5 or less remaining HP.") targeting the host.
#// SOR_225 + JTL_001 → host 5/5; remaining HP = 5 ≤ 5 → eligible target.
#// After: host goes to P1 discard, Asajj returns to P1 leader zone (NOTDEPLOYED, NOT in discard).
#// P2_ENEMY_DEFEATED fired (SWU_ENEMY_DEFEATED counter); space arena empty.

## GIVEN
CommonSetup: gbk/brw/{
  myLeader:JTL_001;
  myBase:SOR_022;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithP1Resources: 6
WithP2Resources: 4
WithP2Hand: SOR_077
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot
- P2>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P1LEADER:NOTDEPLOYED
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_225
P2RESAVAILABLE:0

---

# Asajj_HostDefeatedInCombat_ReturnsToZone
#// JTL_001 Asajj pre-placed as a pilot on a measly JTL_T01 TIE token (1/1 → 4/5 with Asajj's +3/+4).
#// P2 attacks with SOR_086 (5/6): host takes 5 (=5 HP, defeated); attacker takes 4 (survives at 6 HP).
#// Combat-defeat of a leader-piloted vehicle → host leaves play, Asajj returns to the leader zone
#// (NOTDEPLOYED, NOT in discard). P2's attacker survives.

## GIVEN
CommonSetup: gbk/brw/{
  myLeader:JTL_001;
  myBase:SOR_022;
  myLeaderDeployedPilot:1;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithP1SpaceArena: JTL_T01:1:0
WithP2SpaceArena: SOR_086:1:0

## WHEN
- P2>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENACOUNT:0
P1LEADER:NOTDEPLOYED
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_086

---

# Confiscate_DefeatsLeaderUpgrade_ReturnsToZone
#// JTL_001 Asajj deployed as pilot on SOR_225 (TIE/ln Fighter, Space).
#// P2 plays SOR_251 Confiscate ("Defeat an upgrade.") targeting the JTL_001 pilot.
#// SOR_225 base 2/1; JTL_001 upgradePower=3, upgradeHp=4 → host 5/5.
#// After Confiscate: host SURVIVES (space arena count=1), JTL_001 returns to leader zone
#// (NOT in discard, P1LEADER:NOTDEPLOYED), P2 spends 1 resource on Confiscate.

## GIVEN
CommonSetup: gbk/brw/{
  myLeader:JTL_001;
  myBase:SOR_022;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithP1Resources: 6
WithP2Resources: 1
WithP2Hand: SOR_251
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot
- P2>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1LEADER:NOTDEPLOYED
P1DISCARDCOUNT:0
P2RESAVAILABLE:0

---

# Poe_HostDefeated_LeaderReturnsNotDiscarded
#// JTL_013 Poe Dameron attached as pilot to SOR_225 (TIE/ln Fighter, Space) via leader action.
#// Host becomes 4/2. P2 plays SOR_077 Takedown ("Defeat a unit with 5 or less remaining HP.")
#// Host remaining HP = 2 ≤ 5 → valid target. Auto-defeated.
#// After: host discarded, Poe returns to P1 leader zone (NOTDEPLOYED, NOT in discard).
#// P1 space arena empty, P1 discard has SOR_225.

## GIVEN
CommonSetup: grw/brw/{
  myLeader:JTL_013;
  myBase:SOR_022;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithP1Resources: 1
WithP2Resources: 4
WithP2Hand: SOR_077
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:mySpaceArena-0
- P2>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P1LEADER:NOTDEPLOYED
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_225
P2RESAVAILABLE:0
