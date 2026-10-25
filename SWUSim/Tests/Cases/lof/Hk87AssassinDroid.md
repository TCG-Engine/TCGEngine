# WhenDefeated_Deal2EachGround
#// LOF_235 HK-87 Assassin Droid (4/4) — When Defeated: deal 2 damage to each ground unit. It attacks a 4/7
#// and dies; on death its friendly SOR_046 takes 2 and the enemy 4/7 takes 4 (combat) + 2 = 6.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_235:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:6

---

# DefeatedByEvent_HitsAllGroundNotSpace
#// LOF_235 HK-87 Assassin Droid — the When Defeated "deal 2 to each ground unit" fires no matter how it dies
#// (here a removal event, not combat), hits EACH ground unit on BOTH sides, and never touches space units.
#// P2 defeats HK-87 with Takedown (SOR_077); the friendly Marine (SOR_095) and enemy Consular Security Force
#// (SOR_046) each take 2, while the friendly A-Wing (JTL_095) and enemy A-Wing (SOR_141) in space take 0.
#// Intended: HK-87's when-defeated deals 2 to each ground unit (space unaffected) — via Takedown/No Glory.

## GIVEN
CommonSetup: rrk/bbk/{theirBase:SOR_021}
WithActivePlayer: 2
WithP1Resources: 3
WithP2Resources: 8
WithP2Hand: SOR_077
WithP1GroundArena: LOF_235:1:0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: JTL_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_141:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Drain

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:2
P1SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:0

---

# EachGroundUnit_TheUnitBehindADefeatedOneIsNotSkipped
#// ★ REPORTED 2026-10-19 (game 1647080, HK-87 + Grand Admiral Thrawn): "4 damage to some ground units and only 2
#// to others." Each pass of "deal 2 damage to each ground unit" skipped the unit right AFTER any unit it defeated:
#// the handler walked a list of arena SLOTS, a defeat compacted the arena mid-loop, and the next unit slid into a
#// slot already visited. "Each" means every ground unit takes it (CR: one ability's damage is dealt at once).
#// HK-87 trades into P2's 4/7 LAW_124. Its When Defeated kills a 1/1 Battle Droid on EACH side, and the unit
#// sitting right behind each droid must still take its 2: P1's SOR_046 → 2, P2's LAW_124 → 4 combat + 2 = 6.
## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_235:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: TWI_T01:1:0
WithP2GroundArena: LAW_124:1:0
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-1
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:DAMAGE:6

---

# Thrawn_UsesItAgain_EveryGroundUnitTakesFour
#// The reported combo: JTL_002 Grand Admiral Thrawn ("When you use a When Defeated ability: You may exhaust this
#// leader. If you do, use that ability again.") re-uses HK-87's, so EVERY ground unit takes 2 + 2. HK-87 trades into
#// P2's 4/7 LAW_124 (4 combat + 2 + 2 = 8, defeated). P2's 1/1 Battle Droid dies in the first pass, and the SOR_046
#// sitting behind it must take BOTH passes: 4. Before the fix it was skipped in a pass and ended on 2.
## GIVEN
CommonSetup: rrk/ggw/{myLeader:JTL_002}
P1OnlyActions: true
WithP1GroundArena: LOF_235:1:0
WithP2GroundArena: [LAW_124:1:0 TWI_T01:1:0 SOR_046:1:0]
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:YES
## EXPECT
P1LEADER:EXHAUSTED
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4
