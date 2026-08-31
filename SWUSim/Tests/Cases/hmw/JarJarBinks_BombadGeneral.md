# Front_GaveAnExperienceToken_DealsOneAndHealsOne
#// COVERAGE: offer=Front_TheUnitOfferSpansBOTHSides / Front_TheBaseOfferSpansBOTHBases
#//           decline=Deployed_OnAttack_Decline (the "you may" lives on the DEPLOYED side only; the front
#//           Action has no decline — see Front_NoTokenGivenThisPhase_SOFTPASS for its no-effect branch)
#//           boundary=Front_ConditionLAPSESNextPhase (the condition is phase-scoped, so the round turn
#//           is its threshold) · control=N/A (a leader — every take-control effect reads "non-leader
#//           unit", and the seat-scoping negative is Front_OPPONENTGaveTheToken_DoesNotQualify)
#//           reqboundary=Front_RequestBoundary_TheFlagSURVIVES
#//           close=Front_TheActionCLOSES_TurnPassesExactlyOnce + Front_SoftPassALSOClosesTheAction
#//           (both WITHOUT P1OnlyActions — with it, a missing close and a double close are
#//           indistinguishable from a correct one, and the first 14 sections were all blind to it)
#//           modes=2P only (no player reference; "a unit"/"a base" are unqualified, not friendly/enemy)
#//
#// HMW_005 Jar Jar Binks, Bombad General — Leader, cost 6, Vigilance/Heroism, Gungan. Deployed: 4/5.
#//   FRONT:    "Action [1 resource, Exhaust]: If you gave a token upgrade to a unit this phase, deal 1
#//              damage to a unit and heal 1 damage from a base."
#//             "Epic Action: If you control 6 or more resources, deploy this leader."
#//   DEPLOYED: "Shielded" + "On Attack: If you gave a token upgrade to a unit this phase, you MAY deal
#//              1 damage to a unit and heal 1 damage from a base."
#//
#// ⚠ PREVIEW SET — no HMW rulings. Read from the CR plus released twins.
#// ⚠ THE TWO SIDES DIFFER IN OPTIONALITY and that is the whole reason they are not one handler with one
#// gate: the front Action is MANDATORY once its cost is paid, the deployed On Attack is a "you may".
#// Same shape as IC27_001 Darth Vader. Flattening them would silently make one side wrong.
#// ⚠ THE CONDITION IS AN EFFECT GATE, NOT A COST. "[1 resource, Exhaust]" is the cost (in brackets);
#// everything after the colon is the effect. So an unmet condition is a SOFT PASS — the leader still
#// exhausts and still pays — NOT an unavailable action. Putting it in SWULeaderActionAffordable would
#// make the action vanish instead of resolving to nothing (the TS26_02 Anakin lesson).
#//
#// "GAVE A TOKEN UPGRADE TO A UNIT THIS PHASE" is first-of-its-kind state: no such flag existed. There
#// are FOUR token upgrades (SOR_T01 Experience, SOR_T02 Shield, HMW_T02 Weakness, ASH_T02 Advantage) and
#// four sibling giver functions that each append their own subcard — there is no single chokepoint — so
#// each is hooked, and each has its own section below.
#//
#// THIS SECTION: SHD_040 Clan Wren Rescuer (Heroism/Vigilance, so no aspect penalty under this leader)
#// gives an Experience token, then the front Action deals 1 to the enemy and heals 1 off P1's base.

## GIVEN
CommonSetup: bbw/bbw/{myLeader:HMW_005;myBaseDamage:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: [SHD_040]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
#// SHD_040's own When Played gives the Experience token; the Rescuer and the enemy are both legal, so
#// this pick is real. Aim it at the enemy so the token and the later damage land on the same body and
#// the two effects stay separable.
- P1>AnswerDecision:theirGroundArena-0
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myBase-0

## EXPECT
P1LEADER:EXHAUSTED
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:1
P1BASEDMG:2

---

# Front_NoTokenGivenThisPhase_SOFTPASS
#// THE NEGATIVE, and the branch that decides whether the condition was built as a cost or an effect.
#// No token has been given, so the ability resolves to nothing — but the cost is still paid: the leader
#// EXHAUSTS, one resource is spent, and no decision is left dangling. An implementation that gated this
#// in SWULeaderActionAffordable would leave the leader READY with 8 resources.
## GIVEN
CommonSetup: bbw/bbw/{myLeader:HMW_005;myBaseDamage:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
P1RESAVAILABLE:7
P1NODECISION
P2GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:3

---

# Front_ShieldTokenAlsoQualifies
#// FUNNEL 2 of 4 — DoGiveShieldToken. SOR_207 Crafty Smuggler is Shielded, so playing it gives ITSELF a
#// Shield token; that is a token upgrade given to a unit and must satisfy the condition.
#// Each funnel gets its own section because they are four separate functions that each build their own
#// subcard — hooking three of four leaves one whole token KIND silently invisible.
## GIVEN
CommonSetup: bbw/bbw/{myLeader:HMW_005;myBaseDamage:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1Hand: [SOR_207]
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myBase-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1BASEDMG:2

---

# Front_WeaknessTokenAlsoQualifies
#// FUNNEL 3 of 4 — DoGiveTokenUpgrade, the arbitrary-CardID path. HMW_100 Torrent (implemented earlier
#// in this same run) gives a Weakness token; P1's base is Tatooine here so it gives exactly one.
## GIVEN
CommonSetup: bbw/bbw/{myLeader:HMW_005;myBase:HMW_019;myBaseDamage:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1Hand: [HMW_100]
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
#// ⚠ ONE unit on the board, so BOTH Torrent's give and the leader's "deal 1 damage to a unit" pick
#// auto-resolve onto it via PASSPARAMETER — there is no unit answer to give, and a spare one here would
#// be eaten by the base decision behind it.
- P1>UseLeaderAbility
- P1>AnswerDecision:myBase-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1BASEDMG:2

---

# Front_AdvantageTokenAlsoQualifies
#// FUNNEL 4 of 4 — DoGiveAdvantageToken. ASH_264 A New Order is neutral-aspect and cost 1, so it plays
#// under any leader with no penalty. "Up to 2 units", answered with one.
## GIVEN
CommonSetup: bbw/bbw/{myLeader:HMW_005;myBaseDamage:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1Hand: [ASH_264]
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
#// This answer is ASH_264's own "up to 2 units" pick.
- P1>AnswerDecision:theirGroundArena-0
- P1>UseLeaderAbility
#// ⚠ ASH_264 is an EVENT, so SOR_046 stays the only unit and the leader's unit pick auto-resolves.
- P1>AnswerDecision:myBase-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1BASEDMG:2

---

# Front_OPPONENTGaveTheToken_DoesNotQualify
#// SEAT SCOPING. "If YOU gave a token upgrade" is the leader's controller, not anybody. P2 plays the
#// Rescuer and gives the token; P1's Action must still soft-pass.
#// A flag stored globally rather than per-player reads as satisfied here and passes every other section.
#// ⚠ P2 must actually act, so no P1OnlyActions — the turn alternates and P1 acts again after.
## GIVEN
CommonSetup: bbw/bbw/{myLeader:HMW_005;myBaseDamage:3}
SkipPreGame: true
WithActivePlayer: 2
WithP1Resources: 8
WithP2Resources: 8
WithP2Hand: [SHD_040]
WithP2GroundArena: SOR_046:1:0
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:myGroundArena-0
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
P1NODECISION
P1BASEDMG:3

---

# Front_ConditionLAPSESNextPhase
#// THE PHASE BOUNDARY — the condition says "this phase", so the flag must clear at the round turn.
#// P1 gives a token, then the round advances; in the NEW action phase the Action soft-passes.
#// Without this a flag that is set once and never cleared passes every positive section forever.
#// ⚠ The leader readies during regroup, which is what makes it usable again in the new phase.
## GIVEN
CommonSetup: bbw/bbw/{myLeader:HMW_005;myBaseDamage:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: [SHD_040]
WithP1Deck: [SOR_046 SOR_046 SOR_046 SOR_046]
WithP2Deck: [SOR_046 SOR_046 SOR_046 SOR_046]
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
P1NODECISION
P1BASEDMG:3

---

# Front_UnaffordableResource_COMPLETENoOp
#// THE COST GATE, which is a different branch from the soft pass above: with no ready resource the
#// Action cannot be paid for at all, so NOTHING happens — the leader stays READY and keeps its action.
#// Contrast Front_NoTokenGivenThisPhase_SOFTPASS, where the cost IS paid and the effect is what fizzles.
## GIVEN
CommonSetup: bbw/bbw/{myLeader:HMW_005;myBaseDamage:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:READY
P1NODECISION
P1BASEDMG:3

---

# Front_TheUnitOfferSpansBOTHSides
#// OFFER CELL, first pick. "Deal 1 damage to a unit" names no controller and no arena, so the pool is
#// every unit on the board. The decision is left PENDING and the pool itself asserted — answering a
#// target would prove the branch and say nothing about the pool.
#// Board is four units across both players and both arenas so a friendly-only, enemy-only or
#// single-arena pool would all be visible here and nowhere else.
## GIVEN
CommonSetup: bbw/bbw/{myLeader:HMW_005;myBaseDamage:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1Hand: [ASH_264]
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_225:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>UseLeaderAbility
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0&theirSpaceArena-0

---

# Front_TheBaseOfferSpansBOTHBases
#// OFFER CELL, second pick. "Heal 1 damage from A BASE" carries no qualifier, so EITHER base is legal —
#// including the opponent's, which is a real (if odd) choice on a card with no controller word.
#// The unit pick is answered first so the base pick is the pending one.
## GIVEN
CommonSetup: bbw/bbw/{myLeader:HMW_005;myBaseDamage:3;theirBaseDamage:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1Hand: [ASH_264]
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
#// ASH_264's own pick — it is what SETS the flag; the board deliberately holds ONE unit so the leader's
#// unit pick auto-resolves and the BASE pick is the pending decision this section reads.
- P1>AnswerDecision:theirGroundArena-0
- P1>UseLeaderAbility
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myBase-0&theirBase-0

---

# Front_RequestBoundary_TheFlagSURVIVES
#// THE REQUEST-BOUNDARY CELL, and this card is exactly the shape the rule exists for: the flag is
#// written by ONE player action (the token give) and read by a LATER one (the leader Action), so in
#// production those are two separate processes. A flag kept in an in-memory global would be empty by
#// the time the Action runs, and the card would silently soft-pass in real games while every section
#// above stayed green.
#// Same board as the first positive, with the boundary inserted between the give and the read.
## GIVEN
CommonSetup: bbw/bbw/{myLeader:HMW_005;myBaseDamage:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: [SHD_040]
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>SimulateRequestBoundary
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myBase-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1BASEDMG:2

---

# Deployed_EntersWithAShield_WHICHITSELFSatisfiesTheCondition
#// THE DEPLOYED SIDE, and the prettiest interaction on the card: his own Shielded gives HIM a token
#// upgrade as he deploys, which satisfies "you gave a token upgrade to a unit this phase" for his own
#// On Attack — no other card needed. Deploy, attack, and the offer appears.
#// Also verifies the deployed Shielded is auto-wired from $Shielded_Cards (no code of ours).
## GIVEN
CommonSetup: bbw/bbw/{myLeader:HMW_005;myBaseDamage:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myBase-0
## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:1
P1BASEDMG:2

---

# Deployed_OnAttack_DECLINE
#// THE OPTIONALITY THAT DISTINGUISHES THE TWO SIDES. The deployed On Attack is a "you may", so it must
#// be declinable — and declining must leave the board untouched (no damage, no heal), while the attack
#// itself still resolves normally.
#// The front Action has no equivalent branch; that asymmetry is the point.
## GIVEN
CommonSetup: bbw/bbw/{myLeader:HMW_005;myBaseDamage:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-
## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:3
P2BASEDMG:4

---

# Deployed_OnAttack_NoTokenGiven_NOOFFER
#// The deployed negative. Placing the leader unit on the board with WithP1GroundArena does NOT deploy
#// it, so no Shielded trigger fires and no token is ever given — the On Attack must raise no offer at
#// all rather than an offer that then does nothing.
#// ⚠ This is exactly why the section above deploys for real: a seeded leader unit never ENTERS play, so
#// its entry keyword never fires. The two sections differ only in that, and they must disagree.
## GIVEN
CommonSetup: bbw/bbw/{myLeader:HMW_005:1:1;myBaseDamage:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:3
P2BASEDMG:4

---

# Front_TheActionCLOSES_TurnPassesExactlyOnce
#// THE ACTION-CLOSE CELL, and it is written WITHOUT `P1OnlyActions` on purpose. With that directive P1
#// claims initiative, the opponent auto-passes, and the turn comes back to P1 either way — so a missing
#// close and a DOUBLE close are both indistinguishable from a correct one, and every section above is
#// structurally blind to them. Measured: deleting the closer from the resolve path left all 14 green.
#// At two seats the swap is an INVOLUTION, so `TURNPLAYER:2` catches BOTH failure directions at once —
#// no close leaves it on 1, a double close swaps back to 1.
#// ⚠ The leader Action's two picks resolve BEHIND the close in the queue, which is exactly why this has
#// to assert the turn after the answers rather than immediately after UseLeaderAbility.
## GIVEN
CommonSetup: bbw/bbw/{myLeader:HMW_005;myBaseDamage:3}
SkipPreGame: true
WithP1Resources: 8
WithP1Hand: [SHD_040]
WithP2GroundArena: SOR_046:1:0
## WHEN
#// The Rescuer enters play, so there are now two units and its Experience pick is a real choice.
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
#// P2 takes a REAL action so the turn returns to P1 without initiative being claimed.
#// ⚠ NOT `P2>Drain` — that verb only drains P2's decision queue and takes no action, so it does not
#// swap the turn and P1's next line would run out of turn (which the harness permits, and which then
#// reads as an off-by-one close).
#// ⚠ The Experience token was aimed at SOR_046 above, so it attacks at 4 power, not its printed
#// 3: P1's base goes 3 → 7, and the heal brings it back to 6.
- P2>AttackGroundArena:0:BASE
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myBase-0
## EXPECT
TURNPLAYER:2
P2GROUNDARENAUNIT:0:DAMAGE:1
P1BASEDMG:6

---

# Front_SoftPassALSOClosesTheAction
#// THE CONTROL for the section above, and a distinct code path: the soft pass closes from the leader
#// ability's own early return, not from the resolve chain. Without it the two closers could not be
#// broken independently — and the soft pass is the branch a player hits most often.
#// No token given, so the Action pays, exhausts, does nothing, and the turn still passes exactly once.
## GIVEN
CommonSetup: bbw/bbw/{myLeader:HMW_005;myBaseDamage:3}
SkipPreGame: true
WithP1Resources: 8
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
TURNPLAYER:2
P1LEADER:EXHAUSTED
P1NODECISION
P1BASEDMG:3
