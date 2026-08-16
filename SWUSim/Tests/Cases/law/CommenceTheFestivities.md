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
