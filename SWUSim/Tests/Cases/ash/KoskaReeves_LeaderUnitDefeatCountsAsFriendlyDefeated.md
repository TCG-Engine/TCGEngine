# KoskaReeves_DefeatedDeployedLeaderCountsAsFriendlyUnitDefeated
#// ASH_079 Koska Reeves — "When Played: If a friendly unit was defeated this phase, create a Mandalorian token."
#// A deployed leader IS a unit; when it's defeated its owner should register "a friendly unit was defeated
#// this phase" (SWU_FRIENDLY_DEFEATED). P1 defeats P2's deployed Cad Bane (ASH_011) with Rival's Fall
#// (SHD_079, "Defeat a unit."), then P2 plays Koska Reeves — the token MUST be created.
## GIVEN
CommonSetup: ngw/ngw/{myLeader:ASH_001;myBase:ASH_020;theirLeader:ASH_011:true:true:false:0;theirBase:ASH_020}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 12
WithP2Resources: 12
WithP1GroundArena: [SOR_095:0:0]
WithP1Hand: [SHD_079]
WithP2Hand: [ASH_079]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:2

---

# KoskaReeves_LeaderDefeatedInCombatAlsoCountsAsFriendlyDefeated
#// Same rule via the COMBAT path: P1 attacks & defeats P2's deployed leader (defender-death branch). The
#// leader-unit defeat must still register "a friendly unit was defeated this phase" for P2, so Koska's token
#// is created. Pre-damage the leader (3) so a big vanilla walker finishes it regardless of exact leader HP.
## GIVEN
CommonSetup: ngw/ngw/{myLeader:ASH_001;myBase:ASH_020;theirLeader:ASH_011:true:true:false:3;theirBase:ASH_020}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP2Resources: 12
WithP1GroundArena: [SOR_119:1:0]
WithP2Hand: [ASH_079]
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P2>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:2
