# Front_PlaysUnitGrantsAmbushAndAttacks
#// COVERAGE: offer=Front_OfferExcludesPowerFourUnit (SELECTABLEEXACT; the excluded card is CHEAPER
#//           than an included one, so only the POWER gate explains it)
#//           decline=Front_Decline_ActionCostIsStillPaid
#//           boundary=Front_OfferExcludesPowerFourUnit (power 3 in / power 4 out, same printed cost)
#//                  + Epic_DeployAtFiveResources / Epic_BlockedAtFourResources
#//           control=N/A — the front reads only "your hand" (owner-scoped, and the leader itself
#//                  cannot be stolen: every take-control effect reads "non-leader unit"). The played
#//                  unit is chosen and played in the same resolution, so no control change can
#//                  intervene. The deployed side is a plain attacker with two keywords.
#//           reqboundary=Front_AcrossTheRequestBoundary
#//
#// HMW_018 The Warrior, Deft Duelist — Leader (Ground) 3/6, cost 5, [Cunning][Heroism], Tusken, unique.
#// FRONT:  "Action [1 resource, Exhaust]: Play a unit with 3 or less power from your hand
#//          (paying its cost) and give it Ambush for this phase."
#// EPIC:   "Epic Action: If you control 5 or more resources, deploy this leader."  (threshold ==
#//          printed cost 5 == the engine default; no deploy code.)
#// DEPLOY: "Ambush" + "Raid 1" — keyword-only, both auto-derived into the generated registries.
#//
#// Base is Cunning (CommonSetup 'y'), so a Cunning card is on-aspect. ⚠ An UNDEPLOYED leader
#// contributes NO aspects, so the played unit is priced against the BASE alone.
#// SEC_214 Skyhopper Canyon Runner: cost 1, 1/4, Cunning, blank text (no rider to muddy the result).
#// Resources 4 → the action pays 1 (3 left) → SEC_214 costs 1 (2 left).
#// The Ambush attack: SEC_214 deals 1 to SOR_128 Death Star Stormtrooper (3/1) and defeats it;
#// SOR_128 deals 3 back simultaneously, and SEC_214 (4 HP) survives on 3 damage.
#// Only ONE enemy unit, so the Ambush target auto-fires (SWUAmbushAnswer short-circuits at count 1).

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_018;myResources:4}
P1OnlyActions: true
WithP1Hand: SEC_214
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_214
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:0
P1LEADER:EXHAUSTED
P1RESAVAILABLE:2
P1HANDCOUNT:0

---

# Front_OfferExcludesPowerFourUnit
#// THE LOAD-BEARING GATE + the boundary pair, in one offer assertion. Answering a target proves the
#// branch, never the POOL — so this section leaves the pick pending and inspects it.
#// THREE hand cards so the pool still has TWO legal members after the exclusion; with one left the
#// choose would auto-resolve and there would be no offer to read.
#//   myHand-0 SEC_214 Skyhopper Canyon Runner  1/4  cost 1, Cunning   → power 1, LEGAL
#//   myHand-1 SHD_200 Liberated Slaves         3/5  cost 3, Cunning+Heroism → power 3, LEGAL (boundary)
#//   myHand-2 SOR_210 Swoop Racer              4/3  cost 3, Cunning   → power 4, EXCLUDED
#// ⚠ The kept boundary card and the excluded one cost EXACTLY THE SAME (3, and both fully on-aspect:
#// PlayerAspects counts the UNDEPLOYED leader's [Cunning][Heroism] alongside the Cunning base, so
#// neither pays a penalty). Cost, aspect and text are identical across the pair — power is the only
#// variable left, so nothing else can explain the exclusion.
#// Resources 9 → 8 after the action, comfortably covering all three.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_018;myResources:9}
P1OnlyActions: true
WithP1Hand: [SEC_214 SHD_200 SOR_210]

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SELECTABLEEXACT:myHand-0&myHand-1
P1GROUNDARENACOUNT:0

---

# Front_UnaffordableUnitIsNotOffered
#// The other half of the filter: a unit whose POWER passes but whose COST the player cannot meet must
#// not be offered — an offer that can only fizzle is not an offer (the fizzle-only-optional family).
#// Both hand cards have power ≤ 3, so power cannot explain the exclusion.
#// Resources 2 → the action pays 1, leaving 1 ready. SEC_214 costs 1 (payable);
#// SHD_200 costs 3 (payable only with 3), so it drops out.
#// ⚠ Both are fully ON-ASPECT: PlayerAspects reads the leader zone whether or not the leader is
#// deployed, so HMW_018's [Cunning][Heroism] plus the Cunning base cover every pip either card needs.
#// Pricing SHD_200 as off-aspect (+2) is how this section was wrong on the first pass.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_018;myResources:2}
P1OnlyActions: true
WithP1Hand: [SEC_214 SHD_200]

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SELECTABLEEXACT:myHand-0

---

# Front_Decline_ActionCostIsStillPaid
#// "Play a unit from your HAND" is ALWAYS declinable (user ruling 2026-08-15) — the hand is a hidden
#// zone, so a player can never be forced to reveal that they held a playable unit. The card prints no
#// "you may"; it is declinable anyway.
#// ⚠ And declining does NOT refund: the [1 resource, Exhaust] buys the ABILITY, not the effect
#// resolving. Asserting the SPENT state is the point — a "fix" that refunded would pass a
#// nothing-was-played-only assertion.
#// Two legal targets so the choice is genuinely open rather than a formality.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_018;myResources:9}
P1OnlyActions: true
WithP1Hand: [SEC_214 SHD_200]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:2
P1LEADER:EXHAUSTED
P1RESAVAILABLE:8
P1NODECISION

---

# Front_NoLegalUnit_SoftPassStillPaysCost
#// NO-VALID-TARGET, and distinct from the decline branch above: here the offer is never raised at all.
#// SWUSim deliberately omits "use it anyway?" confirmations — an action that fizzles still pays its
#// cost (house ruling; Thrawn ASH_004 relies on it as a soft pass). So the leader still exhausts and
#// still spends the resource, and no decision is left dangling.
#// The only hand card is SOR_210 Swoop Racer (power 4) — affordable, and excluded purely on power.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_018;myResources:9}
P1OnlyActions: true
WithP1Hand: SOR_210

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1LEADER:EXHAUSTED
P1RESAVAILABLE:8
P1NODECISION

---

# Front_NoReadyResource_FullNoOp
#// The unaffordable-COST case is a different branch again from both of the above: the ability is never
#// used, so the leader must keep its readiness and the player keeps their action.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_018;myResources:0}
P1OnlyActions: true
WithP1Hand: SEC_214

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1LEADER:READY
P1RESAVAILABLE:0
P1NODECISION

---

# Front_NoEnemyUnit_AmbushRaisesNoOfferAndCannotHitTheBase
#// CR 5.9.a/5.9.c: Ambush attacks an enemy UNIT, never a base, and a unit with Ambush "cannot attack
#// if there are no enemy units it can attack" — so with an empty enemy board no trigger is added at
#// all. Asserting P1NODECISION (not merely "the base is undamaged") is what proves the offer was
#// skipped rather than raised and silently resolved.
#// The grant itself still happens — it is not conditional on there being a target.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_018;myResources:4}
P1OnlyActions: true
WithP1Hand: SEC_214

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_214
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
P1NODECISION
P2BASEDMG:0

---

# Front_AmbushExpiresAtEndOfPhase
#// THE DURATION ENDING. "give it Ambush for THIS PHASE" — the positive alone passes identically if the
#// grant were permanent, so the only thing that pins the duration is re-reading the keyword after the
#// phase closes. Pairs with Front_NoEnemyUnit_… above, which asserts the grant is present.
#// No enemy units, so nothing interrupts the pass chain. Both decks are seeded: an empty deck at the
#// regroup draw puts 6 damage on that player's base (CR 6.1) and would move numbers here for no reason.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_018;myResources:4}
P1OnlyActions: true
WithP1Hand: SEC_214
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_214
P1GROUNDARENAUNIT:0:NOTKEYWORD:Ambush

---

# Front_PilotCardIsPlayedAsAUnit
#// DISPATCH-PATH cell. The ability says "play a UNIT", so a Piloting card must be played as a unit —
#// the Unit/Pilot choice that a normal hand play raises must NOT appear, even with a legal Vehicle host
#// already in play, and the card's "When played as an upgrade" text must not fire.
#// JTL_215 BoShek: Piloting, cost 3, unit 3/4, Cunning — unit power 3, so he clears the gate on his
#// UNIT statline (a Piloting card carries separate upgrade stats; the wrong one would be read here).
#// SEC_214 Skyhopper Canyon Runner is a Fringe/Vehicle/Speeder, i.e. exactly the host that would make
#// the Pilot mode available. Enemy board empty so no Ambush prompt competes for the answers.
#// Resources 5 → 4 after the action → BoShek costs 3 on-aspect → 1 left.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_018;myResources:5}
P1OnlyActions: true
WithP1Hand: JTL_215
WithP1GroundArena: SEC_214:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:JTL_215
P1GROUNDARENAUNIT:1:HASKEYWORD:Ambush
P1GROUNDARENAUNIT:0:CARDID:SEC_214
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1RESAVAILABLE:1
P1NODECISION

---

# Front_AcrossTheRequestBoundary
#// THE REQUEST-BOUNDARY CELL. The hand pick is an interactive decision, so in production it ENDS the
#// request: the continuation that plays the card and stamps the Ambush grant resumes in a FRESH
#// process. Anything the ability held in an in-memory global between raising the offer and resolving it
#// is gone by then, and the card silently never gets played.
#// Same board and answers as Front_PlaysUnitGrantsAmbushAndAttacks, with one boundary inserted.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_018;myResources:4}
P1OnlyActions: true
WithP1Hand: SEC_214
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>UseLeaderAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_214
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:0
P1RESAVAILABLE:2

---

# TwinSuns_GrantedAmbushReachesAFarSeat
#// ⚠ THE SEAT-COUNT CELL, and it CANNOT PASS AT TWO SEATS. The granted Ambush attacks "an enemy unit";
#// the only enemy unit in the game belongs to SEAT 3, and seat 2's board is empty.
#// Legacy two-seat reasoning resolves the opponent as OtherPlayer(1) == 2, finds no units there,
#// and adds no Ambush trigger at all — so P3's Stormtrooper would end undamaged and alive.
#// Correct four-seat behaviour unions the ambushable units across every live opponent, finds exactly
#// one, and auto-fires onto it.
#// SOR_128 Death Star Stormtrooper is 3/1: SEC_214's single point of power defeats it, and its 3
#// counter-damage lands on SEC_214 (4 HP), so both halves of the combat are observable.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_018;myResources:4}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: SEC_214
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0
WithP3GroundArena: SOR_128:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:YES

## EXPECT
SEATCOUNT:4
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_214
P1GROUNDARENAUNIT:0:DAMAGE:3
P3GROUNDARENACOUNT:0

---

# Epic_DeployAtFiveResources
#// "Epic Action: If you control 5 or more resources, deploy this leader." The threshold equals the
#// leader's printed cost (5), which is the ENGINE DEFAULT — this leader needs no deploy code.
#// Deployed she is a 3/6 Ground leader unit. Enemy board deliberately EMPTY, so her deployed Ambush
#// finds no target and adds no trigger: the deploy is observed in isolation.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_018;myResources:5}
P1OnlyActions: true

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_018
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
P1LEADER:DEPLOYED
P1LEADER:EPICUSED
P1NODECISION
P2BASEDMG:0

---

# Epic_BlockedAtFourResources
#// Boundary partner — one under the threshold is a full no-op, with the Epic still available.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_018;myResources:4}
P1OnlyActions: true

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENACOUNT:0
P1LEADER:NOTDEPLOYED
P1LEADER:EPICAVAILABLE

---

# Deployed_AmbushFiresOnDeployWithRaidOne
#// THE DEPLOYED SIDE'S HEADLINE, exercised through the real deploy→entry-trigger dispatch rather than a
#// WithP1GroundArena stand-in. HMW_018 is the FIRST leader in the game whose deployed side has Ambush,
#// so "the keyword registry covers it" is a claim this section has to actually prove.
#// Deployed HMW_018 is 3/6 with Raid 1, so while attacking she has 4 power.
#// SOR_046 Consular Security Force is 3/7 and survives on 4 damage — DAMAGE:4 is the Raid discriminator
#// (a missing Raid reads 3), and choosing a defender that survives keeps the number visible.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_018;myResources:5}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_018
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:4
P1LEADER:DEPLOYED

---

# Deployed_AmbushAttackExhaustsTheLeaderUnit
#// CR 6.3 step 3 "Begin attack: EXHAUST THE ATTACKER", and CR 5.9.e: an attack resulting from Ambush
#// "is resolved like any other attack, with all of the same steps." Ambush waives the ready
#// REQUIREMENT (5.9.a "even if this unit is exhausted"); it does not waive the exhaust.
#// ⚠ This is invisible on every other Ambush card in the game: a played unit enters play EXHAUSTED
#// (5.9.c), so failing to exhaust it changes nothing. A DEPLOYED LEADER enters play READY — so if the
#// Ambush attack leaves her ready she can attack a second time off a single deploy action.
#// HMW_018 is the first leader with Ambush, i.e. the first card that can observe this at all.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_018;myResources:5}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_018
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# Deployed_AmbushOfferIsUnitsOnly
#// THE AMBUSH OFFER, asserted rather than answered. CR 5.9.a says Ambush attacks "that enemy unit" —
#// units only, never a base — so with two enemy units the pool must be exactly those two and must NOT
#// contain theirBase-0. Two units also means the pick does NOT auto-resolve, which is the only way
#// there is a pending offer left to inspect at all.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_018;myResources:5}
P1OnlyActions: true
WithP2GroundArena: [SOR_046:1:0 SOR_128:1:0]

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:YES

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# Deployed_AmbushMultiTargetPath_AlsoExhausts
#// THE SECOND AMBUSH CODE PATH. With ONE legal target the Ambush YESNO auto-fires the attack; with two
#// it queues a target MZCHOOSE and resolves through a different handler. Same rule, two implementations
#// — so the exhaust has to be proven on both or the untested one keeps the old behaviour.
#// Attacking the 3/7 (index 0): leader 3 power + Raid 1 = 4 damage out, 3 counter-damage back.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_018;myResources:5}
P1OnlyActions: true
WithP2GroundArena: [SOR_046:1:0 SOR_128:1:0]

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_018
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENAUNIT:1:DAMAGE:0

---

# Deployed_AmbushDeclined_NoAttack
#// The decline branch of the deployed side. Ambush is a "may", so answering NO must leave the board
#// untouched — and, because she deployed READY and never attacked, must leave her READY too.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_018;myResources:5}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_018
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# Deployed_RaidOneAppliesOnAnOrdinaryAttack
#// Raid on the ordinary attack path, with the leader seeded already deployed (ready, deployed, epic
#// used) so no entry trigger is involved. 3 power + Raid 1 = 4 damage to the 3/7 defender.
#// POWER:3 afterwards is the other half: Raid is a while-attacking bonus, not a standing stat change —
#// asserting only the damage would pass for a permanent +1/+0 too.
#// The normal attack path (BeginSWUAttack) exhausts the attacker; this is the passing control that
#// makes the Ambush-path exhaust section above meaningful rather than a lone red.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_018:1:1:1;myResources:3}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:CARDID:HMW_018
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# Deployed_RaidDoesNotApplyWhileDefending
#// THE RAID NEGATIVE. "This unit gets +1/+0 WHILE ATTACKING" — when she is the DEFENDER her counter
#// damage must be her printed 3, not 4. A Raid implemented as an unconditional buff passes every
#// attacking section and only this one catches it.
#// P2 attacks with SOR_046 (3/7); initiative is left unclaimed so the turn genuinely alternates.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_018:1:1:1;myResources:3;theirResources:3}
WithActivePlayer: 2
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:CARDID:HMW_018
P1GROUNDARENAUNIT:0:DAMAGE:3
