# EachFriendlyDealsOneToADifferentEnemy
#// HMW_105 Nute Gunray - Perfectly Legal (Ground 2/2, cost 2, [Command,Villainy], Separatist/Official)
#// "When Played: Each friendly unit (including this one) deals 1 damage to a different enemy unit."
#//
#// COVERAGE: offer=OfferIsExactlyTheEnemyUnits (pool is the enemy units, no friendlies, no bases)
#//           decline=N/A (no "may" and no cost — the assignment is mandatory and the pick count is fixed
#//                        at exactly k, so there is no declining branch to take)
#//           boundary=MoreFriendliesThanEnemies_CappedAtEnemyCount +
#//                    FewerFriendliesThanEnemies_CappedAtFriendlyCount (k = min of the two counts)
#//           control=N/A (a When Played does not re-fire on a control change, and the effect names no
#//                        owner-scoped zone — "friendly"/"enemy" are read live at resolution)
#//           reqboundary=RequestBoundary_FriendlyListSurvives
#//           modes=2P,TeamSuns=TeamSuns_ATeammatesUnitCountsAsFriendly (text says "friendly")
#//           TwinSuns=N/A (no player reference — no choice of opponent and no per-seat loop)
#//
#// The plain positive: two seeded friendlies plus Nute himself = three dealers, three enemy units, so
#// each enemy takes exactly 1 and no enemy takes 2.
## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: HMW_105
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1&theirGroundArena-2
## EXPECT
P1GROUNDARENACOUNT:3
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:2:DAMAGE:1

---

# IncludingThisOne_NuteAloneStillDeals
#// "(including this one)" is load-bearing: with no other friendly unit on the board Nute is the ONLY
#// dealer, so exactly one enemy is damaged. An implementation that reads "each OTHER friendly unit"
#// finds zero dealers and does nothing at all. Two enemies are seeded so the pick is a real choice and
#// the untouched one proves only ONE damage instance was dealt.
## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_105
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1
## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:DAMAGE:1

---

# MoreFriendliesThanEnemies_CappedAtEnemyCount
#// "a DIFFERENT enemy unit" caps the effect at the number of enemy units — three dealers against one
#// enemy deal ONE damage, not three. This is the quantity discrimination: an implementation that loops
#// the friendlies and lets them share a target puts 3 damage on the lone enemy and kills it.
#//
#// ⚠ The single target is still ANSWERED: unlike a mandatory MZCHOOSE, a 1-of-1 MZMULTICHOOSE does not
#// auto-resolve through PASSPARAMETER, so a section that omits the pick asserts against an unresolved
#// decision and reads as the ability doing nothing.
## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: HMW_105
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:3
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# FewerFriendliesThanEnemies_CappedAtFriendlyCount
#// The other half of the cap: two dealers against four enemies damage exactly TWO of them. The two
#// untouched enemies are what prove the count came from the friendly side, not from the enemy pool.
## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: HMW_105
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1&theirGroundArena-3
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:2:DAMAGE:0
P2GROUNDARENAUNIT:3:DAMAGE:1

---

# NoEnemyUnits_CleanNoOp
#// No enemy units means no legal assignment at all: the ability resolves to nothing, raises NO decision,
#// and leaves no dangling prompt behind. (The enemy BASE is not a unit and must not be offered.)
## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: HMW_105
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:2
P2BASEDMG:0
P1NODECISION

---

# OfferIsExactlyTheEnemyUnits
#// Answering a pool proves the branch, never the pool. Left pending: the offer must be exactly the enemy
#// units across BOTH arenas — never a friendly unit (they are the dealers, not the targets) and never a
#// base ("a different enemy UNIT").
## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: HMW_105
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_237:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1&theirSpaceArena-0

---

# OverAnswer_IsClampedToTheOfferedCount
#// The harness feeds an AnswerDecision straight to the handler without enforcing the decision's cap, so
#// a resolver that trusts its input would deal three damage instances off a two-dealer board. The
#// offered count rides the continuation's own Param and is re-clamped there, so the third pick is
#// dropped and the last enemy is untouched.
## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: HMW_105
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1&theirGroundArena-2
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:2:DAMAGE:0

---

# LethalAssignment_BothDieWithoutIndexShift
#// Each damage instance can defeat its target, and a defeat compacts the arena underneath the remaining
#// assignments. Two 1-HP enemies are both chosen; both must die. Resolving the picks positionally
#// instead of by UniqueID would send the second point at a shifted slot and leave a survivor.
## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: HMW_105
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1
## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:2

---

# RequestBoundary_FriendlyListSurvives
#// The dealer list is built when the offer is queued and consumed by the handler behind it, so in
#// production those are two different PHP processes. It rides the CUSTOM decision's own Param rather
#// than an in-memory global; the boundary here is what proves it.
## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: HMW_105
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1&theirGroundArena-2
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:2:DAMAGE:1

---

# TeamSuns_ATeammatesUnitCountsAsFriendly
#// "FRIENDLY" spans the TEAM (seats 1+3 vs 2+4), so P1's teammate on seat 3 is a dealer too. Nute alone
#// would be one dealer and damage one enemy; with the teammate's unit counted there are two, so BOTH of
#// seat 2's units take 1. Reading the dealers with GetUnitsInPlay ("you control") answers one and leaves
#// the second enemy untouched.
## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: HMW_105
WithP3GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p2GroundArena-0&p2GroundArena-1
## EXPECT
SEATCOUNT:4
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:DAMAGE:1
