# Wedge Antilles (SOR_100): friendly Vehicle units gain Ambush when entering play.
# P2 has Wedge in ground arena. P1 has JTL_221 (Stolen AT-Hauler, 3/5) with 3 pre-damage.
# P2's SHD_135 (Kylo's TIE Silencer, base 2/2, Vehicle) attacks P1's JTL_221.
# JTL_221 takes 2 damage (total 5 = HP 5) → defeated. Sets OTPF on P1's discard.
# SHD_135 takes JTL_221 counter (power 3) vs SHD_135 HP 2 → SHD_135 also defeated.
# P2 plays JTL_221 from P1's discard (OTPF). JTL_221 is a Vehicle entering P2's control.
# P2 has Wedge → SWUApplyPassiveEntryGrants grants AMBUSH to JTL_221.
# Ambush fires: P2's only valid target is P1's SOR_237 (Alliance X-Wing, 2/3) in space.
# Single target → auto-attacks SOR_237. JTL_221 power 3 kills SOR_237 (HP 3).
# SOR_237 power 2 deals 2 damage to JTL_221. JTL_221 survives (HP 5, takes 2 damage).
#
# AnswerDecision step sequence for Ambush via PlayFromOpponentDiscard:
#   Step 1 "YES": pops the auto-queued RESOLVE_NEXT_TRIGGER DQ entry, which processes
#                 SWU_TRIGGER_RESUME → re-queues RESOLVE_NEXT_TRIGGER → dispatches Ambush
#                 → adds YESNO "Ambush_attack?" to DQ and stops.
#   Step 2 "YES": pops YESNO, answers Ambush = YES. Single target → ExecuteSWUAttack auto-fires.

## GIVEN
CommonSetup: grw/grw
WithP1SpaceArena: JTL_221:2:3
WithP1SpaceArena: SOR_237:2:0
WithP2SpaceArena: SHD_135:2:0
WithP2GroundArena: SOR_100:2:0

## WHEN
- P1>Pass
- P2>AttackSpaceArena:0:0
- P2>PlayFromOpponentDiscard:0
- P2>AnswerDecision:YES
- P2>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:JTL_221
P2SPACEARENAUNIT:0:DAMAGE:2
