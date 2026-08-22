# FewerResourcesBuff
#// LAW_202 Commence the Festivities (Aggression event, cost 1) — "Attack with a unit. It gains Saboteur
#// for this attack. If you control fewer resources than an opponent, it gets +2/+0 for this attack."
#// P1 controls 1 resource vs P2's 3 -> SEC_080 (power 3) attacks the base for 3+2 = 5.

## GIVEN
CommonSetup: rrk/bgw/{myResources:1;theirResources:3}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_202

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:5

---

# MoreResourcesNoBuff
#// LAW_202 Commence the Festivities — if you do NOT control fewer resources than the opponent, no +2/+0.
#// P1 controls 3 vs P2's 1 -> SEC_080 attacks the base for just 3.

## GIVEN
CommonSetup: rrk/bgw/{myResources:3;theirResources:1}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_202

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:3

---

# EqualResourcesNoBuff
#// LAW_202 Commence the Festivities — the +2/+0 requires controlling FEWER resources than an opponent.
#// With EQUAL resources (3 vs 3) the condition is false, so SEC_080 (power 3) attacks the base for 3.

## GIVEN
CommonSetup: rrk/bgw/{myResources:3;theirResources:3}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_202

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:3

---

# FewerResourcesBuff_SurvivesTheRequestBoundary
#// LAW_202 — request-boundary guard for FewerResourcesBuff. Production starts a FRESH process on every
#// answered decision, so everything the event recorded when the ATTACKER was chosen (which unit is
#// attacking, its Saboteur grant, and the resolved "fewer resources than an opponent" +2/+0 for this
#// attack) must come back out of the serialized gamestate rather than an in-memory continuation global.
#// The other sections' fixture auto-resolves both picks (one attacker, base-only target), which would
#// make a boundary vacuous, so a second friendly unit (SOR_095) and an enemy ground unit (SOR_046) are
#// seeded purely to make both choices real: attacker = MZCHOOSE [myGroundArena-0&myGroundArena-1],
#// target = MZCHOOSE [theirGroundArena-0&theirBase-0]. The boundary sits before the TARGET answer, so
#// the whole attack resolves after it, and the base must still take 3+2 = 5.

## GIVEN
CommonSetup: rrk/bgw/{myResources:1;theirResources:3}
P1OnlyActions: true
WithP1GroundArena: [SEC_080:1:0 SOR_095:1:0]
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_202

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:5

---

# ForeignOwnedResource_CountsForItsController
#// LAW_202 — control axis, clause "If you CONTROL fewer resources than an opponent". A resource is
#// counted for whoever CONTROLS it (whose resource zone it sits in), not for the player who owns the
#// card. P1's resource zone holds 1 own resource plus a P2-OWNED resource (SOR_095, seated via
#// WithP1ResourceControlled) = 2 controlled; P2 controls 2. The two totals are therefore EQUAL, the
#// "fewer resources" clause is false, and the P1-controlled attacker (SEC_080, power 3, itself owned
#// by P2) hits the base for a flat 3.
#// The board discriminates: drop the foreign-owned resource and P1 controls 1 vs P2's 2, the clause
#// turns true, and the base takes 3+2 = 5. So a 3 here is only reachable if the P2-OWNED resource was
#// counted for its CONTROLLER. P1RESCOUNT/P2RESCOUNT pin the split (2 vs 2) so the section can never
#// pass for the wrong reason.

## GIVEN
CommonSetup: rrk/bgw/{myResources:1;theirResources:2}
P1OnlyActions: true
WithP1ResourceControlled: SOR_095:2
WithP1GroundArenaControlled: SEC_080:2
WithP1Hand: LAW_202

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESCOUNT:2
P2RESCOUNT:2
P2BASEDMG:3

---

# ForeignOwnedAttacker_YouControlIsTheAbilityController
#// LAW_202 — control axis, the owner!=controller case for the ATTACKER. The only friendly unit is
#// SEC_080, sitting in P1's ground arena but OWNED by P2 (the end state after a control-take). P1
#// plays the event, so BOTH riders must resolve from P1, the ability's controller, not from the
#// attacking unit's owner:
#//   · the Saboteur grant lands on the foreign-owned attacker — P2's SOR_046 Consular Security Force
#//     is a Sentinel, so without Saboteur the base would not be a legal target at all. The pending
#//     attack-target pool is MZCHOOSE [theirGroundArena-0 & theirBase-0]: the base is reachable AND
#//     the Sentinel is still a legal target, so this is a real two-option choice, not an auto-resolve.
#//   · "if YOU control fewer resources" reads P1's 1 vs P2's 3 -> true -> +2/+0, base takes 3+2 = 5.
#// Inverted seat resolution is visible: resolved from the attacker's OWNER (P2), P2 controls 3 vs
#// P1's 1, the clause is false and the base would take 3. The untouched Sentinel (DAMAGE:0) proves
#// the damage went to the base and not to the only other legal target.
#//
#// COVERAGE: offer=the attack-target pool is pinned pending in
#//           ForeignOwnedAttacker_YouControlIsTheAbilityController (Sentinel + base both offered
#//           under the Saboteur grant); the attacker pick is a real 2-unit MZCHOOSE in
#//           FewerResourcesBuff_SurvivesTheRequestBoundary · decline=N/A (nothing here is a "you
#//           may" — "Attack with a unit" is a mandatory attack and both riders are automatic) ·
#//           control=ForeignOwnedResource_CountsForItsController (resources counted by controller)
#//           + ForeignOwnedAttacker_YouControlIsTheAbilityController (both riders resolve from the
#//           event's controller, not the attacker's owner) · reqboundary=
#//           FewerResourcesBuff_SurvivesTheRequestBoundary · boundary pair=FewerResourcesBuff vs
#//           MoreResourcesNoBuff vs EqualResourcesNoBuff (fewer / more / exactly equal).

## GIVEN
CommonSetup: rrk/bgw/{myResources:1;theirResources:3}
P1OnlyActions: true
WithP1GroundArenaControlled: SEC_080:2
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_202

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:5
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# TwinSuns_ANYRicherOpponentGrantsThePlus2
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-23 (Pass 1, DETERMINED). Re-filed out of PROMPT (47): "if an
#// opponent controls more resources than you" is an EXISTENTIAL CONDITION. Nothing downstream needs the
#// seat — the +2 lands on YOUR attacker — so this card must never prompt for a player.
#// This is the same "does AN opponent control X? must be does ANY" shape Pass 0 fixed in all five
#// instrumented GetOpponent() sites (SWUEnemySnokeCount, ASH_068 Loth-Cat, LAW_117, …).
#// P1 controls 3 resources. SEAT 2 — the seat the old code read — also controls 3, so no buff. SEAT 4
#// controls 6, so the condition IS true and the attacker gets +2: SEC_080 (3 power) hits seat 3's base
#// for 5 instead of 3.
#// ⚠ A 2-player version CANNOT FAIL — one opponent is the only comparison. The seat count IS the test.
#// ⚠ The attacker AUTO-RESOLVES (SEC_080 is P1's only ready unit), so the first and only prompt is the
#//   attack target — and at four seats that menu is p2Base-0&p3Base-0&p4Base-0, not a bare 'BASE'.
#// Mutation check: revert to SWUResourceCount($opp) and this reds while all four 2-player sections stay green.

## GIVEN
CommonSetup: rrk/bgw/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1Resources: 3
WithP2Resources: 3
WithP3Resources: 3
WithP4Resources: 6
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_202
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3Base-0

## EXPECT
SEATCOUNT:4
P3BASEDMG:5
