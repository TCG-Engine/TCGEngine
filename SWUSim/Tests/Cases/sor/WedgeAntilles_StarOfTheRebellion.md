# NonVehicleNoBoost
#// COVERAGE: offer=N/A (a static aura has no target choice; the Ambush attack pick is exercised in
#//           VehicleGetsAmbush) · decline=N/A (no "you may" on the aura; the Ambush YESNO decline is
#//           generic keyword behavior) · boundary=NonVehicleNoBoost + EnemyVehicle_NoBuffNoAmbush
#//           (trait and friendliness gates) · control=VehicleGetsAmbush (a Vehicle entering P2's
#//           control from P1's discard gets P2-Wedge's grant — the aura keys on the CONTROLLER)
#//           · reqboundary=VehicleGetsAmbush (grant applied at entry survives into the ambush attack
#//           across the decision boundary)
#// Wedge Antilles (SOR_100): only friendly VEHICLE units get +1/+1.
#// ASH_259 (LEP Ratcatcher, base 1/1) is a non-Vehicle Ground unit.
#// ASH_259 should NOT get a boost — it stays 1/1.
#// Wedge is at index 0; ASH_259 is at index 1.

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: SOR_100:2:0
WithP1GroundArena: ASH_259:2:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:1:POWER:1
P1GROUNDARENAUNIT:1:HP:1

---

# VehicleGetsAmbush
#// Wedge Antilles (SOR_100): friendly Vehicle units gain Ambush when entering play.
#// P2 has Wedge in ground arena. P1 has JTL_221 (Stolen AT-Hauler, 3/5) with 3 pre-damage.
#// P2's SHD_135 (Kylo's TIE Silencer, base 2/2, Vehicle) attacks P1's JTL_221.
#// JTL_221 takes 2 damage (total 5 = HP 5) → defeated. Sets OTPF on P1's discard.
#// SHD_135 takes JTL_221 counter (power 3) vs SHD_135 HP 2 → SHD_135 also defeated.
#// P2 plays JTL_221 from P1's discard (OTPF). JTL_221 is a Vehicle entering P2's control.
#// P2 has Wedge → SWUApplyPassiveEntryGrants grants AMBUSH to JTL_221.
#// Ambush fires: P2's only valid target is P1's SOR_237 (Alliance X-Wing, 2/3) in space.
#// Single target → auto-attacks SOR_237. JTL_221 power 3 kills SOR_237 (HP 3).
#// SOR_237 power 2 deals 2 damage to JTL_221. JTL_221 survives (HP 5, takes 2 damage).
#//
#// AnswerDecision step sequence for Ambush via PlayFromOpponentDiscard:
#//   Step 1 "YES": pops the auto-queued RESOLVE_NEXT_TRIGGER DQ entry, which processes
#//                 SWU_TRIGGER_RESUME → re-queues RESOLVE_NEXT_TRIGGER → dispatches Ambush
#//                 → adds YESNO "Ambush_attack?" to DQ and stops.
#//   Step 2 "YES": pops YESNO, answers Ambush = YES. Single target → ExecuteSWUAttack auto-fires.

## GIVEN
CommonSetup: grw/grw
WithP1SpaceArena: JTL_221:2:3
WithP1SpaceArena: SOR_237:2:0
WithP2SpaceArena: SHD_135:1:0
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

---

# VehicleGetsPowerBoost
#// Wedge Antilles (SOR_100): friendly Vehicle units get +1/+1 while Wedge is in play.
#// JTL_221 (Stolen AT-Hauler, base 4/5) is a Vehicle in P1's space arena.
#// With Wedge on the ground, JTL_221 should read 5/6.

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: SOR_100:2:0
WithP1SpaceArena: JTL_221:2:0

## WHEN

## EXPECT
P1SPACEARENAUNIT:0:POWER:5
P1SPACEARENAUNIT:0:HP:6

---

# EnemyVehicle_NoBuffNoAmbush
#// SOR_100 Wedge — "each FRIENDLY Vehicle unit": an ENEMY Vehicle gets neither the +1/+1 nor
#// Ambush. P1 controls Wedge; P2 plays AT-ST (SOR_232, 6/7 Vehicle). It enters at printed stats,
#// no Ambush prompt fires for anyone, and the action simply passes back.

## GIVEN
SkipPreGame: true
CommonSetup: rrk/bbk
WithActivePlayer: 2
WithP2Resources: 6
WithP1GroundArena: SOR_100:1:0
WithP2Hand: SOR_232

## WHEN
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_232
P2GROUNDARENAUNIT:0:POWER:6
P2GROUNDARENAUNIT:0:HP:7
P2GROUNDARENAUNIT:0:NOTKEYWORD:Ambush
P1NODECISION
P2NODECISION

---

# TokenVehicle_GetsBuff
#// SOR_100 Wedge — the +1/+1 covers friendly TOKEN Vehicles too. P1 plays Veteran Fleet Officer
#// (JTL_099), whose When Played creates an X-Wing token (JTL_T02, 2/2 Space Vehicle). With Wedge in
#// play the token reads 3/3. The non-Vehicle Fleet Officer itself stays unbuffed (2/1).
#// Intended per CR: whether the granted AMBUSH fires for a CREATED token (created is not "played",
#// and Ambush triggers on playing the unit) is withheld pending a ruling — the engine deliberately
#// skips WhenPlayed/Ambush on token creation, so only the stat half is pinned here.

## GIVEN
SkipPreGame: true
CommonSetup: grw/rrk
P1OnlyActions: true
WithP1Resources: 3
WithP1GroundArena: SOR_100:1:0
WithP1Hand: JTL_099
WithP2SpaceArena: SHD_060:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_T02
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:1:CARDID:JTL_099
P1GROUNDARENAUNIT:1:POWER:2
P1GROUNDARENAUNIT:1:HP:1
