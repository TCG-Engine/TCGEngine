# AttackDefeats_DealsToGroundUnit
#// COVERAGE: offer=Offer_AllGroundUnitsIncludingSelf_SpaceExcluded (pending SELECTABLEEXACT: every
#//           ground unit incl. Zeb, space + dead defender excluded, post-cleanup indexes)
#//           · decline=AttackDefeats_DeclineDeal ("you may" answered with -)
#//           · boundary=OnAttackAbilityKill_StillTriggers (defeat at ANY point of the attack counts)
#//           + TradesWithDefender_NoTrigger (survival gate) · control=N/A (the trigger owner is the
#//           attacking controller by construction; no control-change interaction in the text)
#//           · reqboundary=Offer_AllGroundUnitsIncludingSelf_SpaceExcluded (the may-choose pends
#//           across the request boundary)
#// SOR_146 Zeb Orrelios (5/5) — "When this unit completes an attack: If the defender was defeated,
#// you may deal 4 damage to a ground unit." Zeb (5 power) attacks a 3/3 (SEC_080), defeats it, takes 3
#// back. The defender died → the may-choose fires; deal 4 to the opponent's other ground unit (SOR_046
#// 3/7, which reindexes to idx 0 after SEC_080 is cleaned up, and survives at 4 damage).

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_146:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# AttackDefeats_DeclineDeal
#// SOR_146 Zeb Orrelios — the deal-4 is optional ("you may"). Zeb defeats the defender, then DECLINES
#// the may-choose (AnswerDecision:-), so the surviving ground unit takes no extra damage. Zeb still has
#// the 3 combat damage from the defender.

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_146:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# AttackNoDefeat_NoTrigger
#// SOR_146 Zeb Orrelios — the ability is conditional on the defender being defeated. Zeb (5 power)
#// attacks a 3/7 (SOR_046) that survives, so the defender was NOT defeated: no may-choose is queued
#// (P1NODECISION) and the defender takes only the 5 combat damage (not 5 + 4). Proves the gate.

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_146:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION

---

# Offer_AllGroundUnitsIncludingSelf_SpaceExcluded
#// SOR_146 Zeb — the deal-4 pool is EVERY ground unit (Zeb himself, his friendly ground ally, the
#// surviving enemy) and excludes space units and the already-dead defender. Zeb kills SEC_080 (3/3),
#// survives, and the may-choose is left PENDING to pin the offer (post-cleanup indexes: the wampa
#// compacts to theirGroundArena-0).

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_146:1:0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0

---

# TradesWithDefender_NoTrigger
#// SOR_146 Zeb — "completes an attack" requires Zeb to SURVIVE it. Zeb (5/5) trades with SOR_116
#// (5/5): the defender IS defeated but Zeb dies too, so no deal-4 prompt fires for either player.

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_146:1:0
WithP2GroundArena: SOR_116:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
P2NODECISION

---

# DefendsAndKillsAttacker_NoTrigger
#// SOR_146 Zeb — the ability reads "when THIS UNIT completes an attack": Zeb defeating an enemy
#// while DEFENDING is not his attack, so nothing fires. P2's SEC_080 (3/3) attacks Zeb (5/5): the
#// attacker dies to Zeb's 5 combat damage, Zeb takes 3, and no deal-4 prompt appears.

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: SOR_146:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P1GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION
P2NODECISION

---

# OnAttackAbilityKill_StillTriggers
#// SOR_146 Zeb — "the defender was defeated" counts a defeat at ANY point in the attack, not just
#// combat damage. Zeb + SHD_177 (6/6) attacks SEC_080 (3/3); the granted On Attack deals all 3 to
#// the defender, killing it BEFORE combat damage. The attack still completes (Zeb takes nothing
#// back) and the deal-4 fires: 4 damage to the wampa.

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_146:1:0
WithP1GroundArenaUpgrade: 0:SHD_177
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0:3
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# KillsDeployedLeaderUnit_Triggers
#// SOR_146 Zeb — a defeated deployed LEADER unit counts as "the defender was defeated" even though
#// it returns to the leader zone instead of the discard. Zeb (5/5) attacks deployed Sabine (2/5),
#// defeating her; Zeb takes 2 back; the deal-4 fires and hits the wampa for 4.

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021;
  theirLeader:SOR_014:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_146:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:1
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:DAMAGE:2
P2LEADER:NOTDEPLOYED

---

# DefenderTitleDefeatedEarlier_ReplayedCopySurvives_NoTrigger
#// SOR_146 Zeb — "the defender was defeated" is tracked per THIS attack, not per title or per game.
#// P1's SOR_078 defeats P2's SOR_046; P2 plays a fresh SOR_046 from hand; Zeb then attacks the new
#// copy, which survives at 5 damage (3/7) — no deal-4 prompt even though "Consular Security Force
#// was defeated" earlier this phase.

## GIVEN
CommonSetup: rrk/bbk
SkipPreGame: true
WithP1Resources: 5
WithP2Resources: 4
WithP1Hand: SOR_078
WithP2Hand: SOR_046
WithP1GroundArena: SOR_146:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION
P2NODECISION
