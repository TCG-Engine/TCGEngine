# Front_Decline
#// SHD_014 Cad Bane (front) — declining the "may" leaves Cad Bane ready and deals no damage.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_014}
WithActivePlayer: 1
WithP1Resources: 1
WithP1Hand: SOR_204
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1LEADER:READY
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Front_NoEnemyUnit_NoOffer
#// SHD_014 Cad Bane (front) — with no enemy unit to damage, the reaction makes no offer (Cad Bane stays
#// ready); playing an Underworld card resolves with no prompt.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_014}
P1OnlyActions: true
WithP1Resources: 1
WithP1Hand: SOR_204

## WHEN
- P1>PlayHand:0

## EXPECT
P1LEADER:READY
P1GROUNDARENACOUNT:1

---

# Front_OpponentUnitTakesOne
#// SHD_014 Cad Bane (front, undeployed) — "When you play an Underworld card: You may exhaust this leader.
#// If you do, an opponent chooses a unit they control. Deal 1 damage to it." P1 plays SOR_204 (Underworld),
#// accepts (exhausting Cad Bane); P2 must choose one of their units (SOR_046) which then takes 1 damage.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_014}
WithActivePlayer: 1
WithP1Resources: 1
WithP1Hand: SOR_204
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# Front_TwinSuns_YouChooseWHICHOpponent
#// ⚠ THE REPORTED BUG (2026-08-21): "Cad Bane leader ping didn't ask which player to ping — it always
#// went to Player 1." Both handlers resolved the target seat with OtherPlayer($player), which is the
#// legacy `$player === 1 ? 2 : 1`: at four seats it always answers 2 for seat 1 and 1 for everyone else.
#// The FRONT text is "an opponent chooses a unit they control" — it does not spell out who picks the
#// opponent, but the DEPLOYED half of the same card says "You may CHOOSE an opponent", so the card's
#// own wording settles it: Cad Bane's controller picks, then that opponent picks their own unit.
#//
#// ⚠ FIXTURE: seats 3 and 4 exist only because WithSeatOrder/WithLiveSeats say so — CommonSetup builds
#//   seats 1 and 2 ONLY. Anything a section wants on a far seat (units here; BASES elsewhere) has to be
#//   seeded explicitly or it silently is not there.
#// Left pending: the pool is the OPPONENT PICKER, and it must offer all three opponents and never P1.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_014}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 1
WithP1Hand: SOR_204
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
SEATCOUNT:4
P1HASDECISION
P1OPTIONHAS:P2
P1OPTIONHAS:P3
P1OPTIONHAS:P4
P1OPTIONNOT:P1

---

# Front_TwinSuns_PickSeatThree_ThatSeatChoosesItsOwnUnit
#// The reported line driven end to end: P1 picks seat 3, and seat 3 — not seat 1, not seat 2 — is the
#// one asked to choose a unit they control. Only that unit takes the 1.
#// ⚠ The unit choice must land on the PICKED seat's queue: a chooser derived from OtherPlayer() would
#//   put it on seat 2's and the wrong player would be answering.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_014}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 1
WithP1Hand: SOR_204
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:P3
- P3>AnswerDecision:myGroundArena-0

## EXPECT
P3GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P4GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:EXHAUSTED

---

# Front_TwinSuns_NoOpponentAnywhereHasAUnit_NoOffer
#// The fizzle-only guard, widened to N seats: the "no enemy unit" check must consider EVERY opponent,
#// not just the one OtherPlayer() happens to name. With seat 2 empty but seats 3 and 4 empty too, there
#// is nothing to damage and the exhaust must not be offered at all.
#// ⚠ Its mirror is the section above — seat 2 empty while seat 3 HAS a unit still has to offer.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_014}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 1
WithP1Hand: SOR_204

## WHEN
- P1>PlayHand:0

## EXPECT
P1LEADER:READY
P1NODECISION

---

# Front_TwinSuns_OnlyAFarSeatHasAUnit_StillOffers
#// Seat 2 is EMPTY and only seat 3 has a unit. A target check written against OtherPlayer() sees an
#// empty board and silently declines to offer — the card does nothing all game against those seats.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_014}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 1
WithP1Hand: SOR_204
WithP3GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:P3
- P3>AnswerDecision:myGroundArena-0

## EXPECT
P3GROUNDARENAUNIT:0:DAMAGE:1
P1LEADER:EXHAUSTED

---

# Deployed_OpponentUnitTakesTWO_NoCost
#// ⚠ THE DEPLOYED SIDE WAS ENTIRELY UNIMPLEMENTED (found 2026-08-21 while fixing the front side's
#// opponent choice, and not listed in leader-gaps.md). The trigger was collected only under
#// _SWULeaderReadyUndeployed, so a DEPLOYED Cad Bane never fired it — in any format, since release.
#// DeployText: "Raid 2 | When you play an Underworld card: You may choose an opponent. They choose a
#//              unit they control. Deal 2 damage to it. Use this ability only once each round."
#//
#// Same shape as the front side but with two differences that are the whole point of testing it
#// separately: the damage is 2 rather than 1, and there is NO exhaust cost — the deployed leader unit is
#// still READY afterwards. Its limiter is a once-per-round budget instead.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_014; myLeaderDeployed:true}
WithActivePlayer: 1
WithP1Resources: 6
WithP1Hand: SOR_204
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1LEADER0DEPLOYED:true
P1LEADER0:READY

---

# Deployed_OncePerRound_TheSecondUnderworldPlayMakesNoOffer
#// "Use this ability only once each round." P1 plays Greedo (Underworld) and takes the reaction, then
#// plays Underworld Thug in the SAME round — no second offer, and no further damage.
#// ⚠ Without a budget this is a machine gun: every Underworld card in the turn would ping again.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_014; myLeaderDeployed:true}
WithActivePlayer: 1
WithP1Resources: 6
WithP1Hand: [SOR_204 SOR_247]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P2>AnswerDecision:myGroundArena-0
- P2>Pass
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION

---

# Deployed_Declined_TheUseIsNOTConsumed
#// DECLINE, and the cheapest way to prove the budget is spent on USE rather than on being OFFERED:
#// P1 refuses the first Underworld play, then plays a second Underworld card the same round and must
#// still be offered it.
#// ⚠ A budget consumed at offer time passes every other section in this file and silently costs the
#//   player their once-per-round for saying no.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_014; myLeaderDeployed:true}
WithActivePlayer: 1
WithP1Resources: 6
WithP1Hand: [SOR_204 SOR_247]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P2>Pass
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# Deployed_TwinSuns_PickSeatFour
#// The deployed side's own seat-count cell — it does NOT inherit the front side's. Its text is the one
#// that says "You may CHOOSE an opponent" outright, so the picker is explicit here, not inferred.
#// ⚠ Far-seat units must be seeded: CommonSetup builds seats 1 and 2 only.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_014; myLeaderDeployed:true}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 6
WithP1Hand: SOR_204
WithP2GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:P4
- P4>AnswerDecision:myGroundArena-0

## EXPECT
SEATCOUNT:4
P4GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Deployed_NoOpponentHasAUnit_NoOffer
#// The fizzle-only guard on the deployed side: nothing to hit anywhere, so no prompt — and, because the
#// budget is only spent on use, nothing is quietly burned either.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_014; myLeaderDeployed:true}
WithActivePlayer: 1
WithP1Resources: 6
WithP1Hand: SOR_204

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION

---

# Deployed_HasRaid2
#// The keyword half of the deployed side. Cad Bane deploys as a 2/8; Raid 2 makes him a 4 while
#// attacking, so his hit on an untouched 3/7 lands 4 — not 2.
#// ⚠ Membership in $Raid_Cards is a generated LITERAL (SHD_014 => 2) and a wrong one is invisible
#//   without a section that reads the number back off a real attack.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_014; myLeaderDeployed:true}
WithActivePlayer: 1
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
