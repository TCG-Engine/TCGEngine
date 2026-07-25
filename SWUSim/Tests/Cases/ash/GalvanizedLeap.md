# ReadyDamagedUnit
#// ASH_188 Galvanized Leap (Event, cost 4) — Ready a unit that was damaged this phase. P1's SOR_046
#// attacks SEC_080 (taking 3 counter damage, becoming exhausted and "damaged this phase"); then Galvanized
#// Leap readies it.
## GIVEN
CommonSetup: rrk/rrk/{myResources:4;handCardIds:ASH_188}
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:READY

---

# NotDamagedThisPhase_NoReady
#// ASH_188 Galvanized Leap — it can only ready a unit that was DAMAGED this phase. With an exhausted but
#// undamaged SOR_046, there is no legal target: the event fizzles and SOR_046 stays exhausted.
## GIVEN
CommonSetup: rrk/rrk/{myResources:4;handCardIds:ASH_188}
WithP1GroundArena: SOR_046:0:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# ReadyEnemyUnit_DamagedByCombat
#// ASH_188 Galvanized Leap — the target may be ANY unit, including an enemy one. P1's SEC_213 (A-Wing, Raid 1
#// → 2 power) attacks the exhausted enemy SOR_141 (Green Squadron A-Wing), dealing 2. Galvanized Leap then
#// readies the ENEMY SOR_141 (both damaged units are selectable; P1 chooses the enemy).
## GIVEN
CommonSetup: rrk/rrk/{myResources:4;handCardIds:ASH_188}
WithP1SpaceArena: SEC_213:1:0
WithP2SpaceArena: SOR_141:0:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P2SPACEARENAUNIT:0:CARDID:SOR_141
P2SPACEARENAUNIT:0:DAMAGE:2
P2SPACEARENAUNIT:0:READY

---

# ReadyFriendlyUnit_DamagedByOpponentEvent
#// ASH_188 Galvanized Leap — "damaged this phase" counts non-combat damage too. On P2's turn, P2 plays SHD_178
#// (Daring Raid) dealing 2 to P1's exhausted SOR_141; P1 then plays Galvanized Leap, which auto-targets the
#// only damaged unit (SOR_141) and readies it.
## GIVEN
CommonSetup: rrk/rrk/{myResources:4;handCardIds:ASH_188;theirResources:3;theirhandCardIds:SHD_178}
WithP1SpaceArena: SOR_141:0:0
WithActivePlayer: 1
WithInitiativePlayer: 1
## WHEN
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_141
P1SPACEARENAUNIT:0:DAMAGE:2
P1SPACEARENAUNIT:0:READY

---

# ReadyFriendlyLeaderUnit_DamagedByCombat
#// ASH_188 Galvanized Leap — a deployed LEADER unit is a valid target. On P2's turn, P2's SEC_080 attacks P1's
#// exhausted deployed leader SOR_005 (which defeats SEC_080 on the counter-swing but takes 3 combat damage).
#// Galvanized Leap then auto-targets the leader (the only damaged unit) and readies it.
## GIVEN
CommonSetup: rrk/rrk/{handCardIds:ASH_188;myResources:4;myLeader:SOR_005:0:1}
WithP2GroundArena: SEC_080:1:0
WithActivePlayer: 1
WithInitiativePlayer: 1
## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:READY

---

# ReadyEnemyLeaderUnit_DamagedByCombat
#// ASH_188 Galvanized Leap — the readied unit may be an ENEMY LEADER unit. P2's exhausted deployed leader
#// (SOR_005) is attacked by P1's SOR_046 (dealing 3 combat damage this phase); Galvanized Leap then readies
#// that enemy leader unit (SOR_046 is also damaged by the counter, so P1 picks the enemy leader).
## GIVEN
CommonSetup: rrk/rrk/{handCardIds:ASH_188;myResources:4;theirLeader:SOR_005:0:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:ISLEADERUNIT
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:READY

---

# ReadyFriendlyUnit_DamagedByAmbushUnit
#// ASH_188 Galvanized Leap — "damaged this phase" also counts combat damage dealt by an enemy unit's
#// Ambush attack on entry. On P2's turn, P2 plays SOR_149 Mace Windu (Ambush, 5 power) which Ambush-attacks
#// P1's exhausted SOR_046 (3/7), dealing 5 (SOR_046 survives). P1 then plays Galvanized Leap and readies
#// SOR_046 (Mace also took the counter, so P1 picks the friendly SOR_046).
## GIVEN
CommonSetup: rrw/rrk/{handCardIds:ASH_188;myResources:4;theirResources:10;theirhandCardIds:SOR_149}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SOR_046:0:0
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:YES
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:0:READY
