# HighestCostEnemyDefeatedCredit
#// LAW_053 Dengar (4/3) — When a unit with the highest cost among enemy units is defeated: create a
#// Credit token (once each round). P1's SOR_046 attacks and kills SEC_080 (cost 2, the highest enemy
#// cost — SOR_128 is cost 1), so Dengar's controller gets a Credit.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_053:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:1:0

## EXPECT
P1CREDITCOUNT:1

---

# MultipleDefeatedOneHighest
#// LAW_053 Dengar — when multiple enemy units are defeated simultaneously and one has the highest
#// cost, the Credit still triggers. Dengar (ground) survives while Bombing Run (SOR_173) deals 3 to
#// both enemy space units: JTL_256 (cost 2, the highest) and TWI_228 (cost 1) both die → 1 Credit.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1Resources: 5
WithP1GroundArena: LAW_053:1:0
WithP1Hand: SOR_173
WithP2SpaceArena: JTL_256:1:0
WithP2SpaceArena: TWI_228:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Space

## EXPECT
P1CREDITCOUNT:1
P2SPACEARENACOUNT:0

---

# OnlyEnemyDefeatedZeroCost
#// LAW_053 Dengar — the only enemy unit in play counts as the highest cost even at cost 0. Dengar
#// attacks the lone LOF_254 Porg (cost 0, 1/1) and defeats it → 1 Credit.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_053:1:0
WithP2GroundArena: LOF_254:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1CREDITCOUNT:1
P2GROUNDARENACOUNT:0

---

# OncePerRound
#// LAW_053 Dengar — the Credit is created only once each round. Two friendly SOR_046 attack: the
#// first defeats SOR_095 Battlefield Marine (cost 2, the highest enemy) → 1 Credit; the second
#// defeats SOR_128 Death Star Stormtrooper (cost 1, now the highest remaining) but no second Credit.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_053:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:1:1
- P1>AttackGroundArena:2:0

## EXPECT
P1CREDITCOUNT:1
P2GROUNDARENACOUNT:0

---

# TwoSameHighestResolvesOnce
#// LAW_053 Dengar — if multiple enemy units share the highest cost and are defeated simultaneously,
#// the Credit still resolves only once (once per round). Dengar (ground) survives while Bombing Run
#// (SOR_173, Space) defeats two JTL_256 Swarming Vulture Droids (each cost 2, both highest) → 1 Credit.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1Resources: 5
WithP1GroundArena: LAW_053:1:0
WithP1Hand: SOR_173
WithP2SpaceArena: JTL_256:1:0
WithP2SpaceArena: JTL_256:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Space

## EXPECT
P1CREDITCOUNT:1
P2SPACEARENACOUNT:0

---

# NoneHighestDefeated
#// LAW_053 Dengar — no Credit if the defeated units are not the highest cost. LOF_236 Army of the
#// Dead (cost 6, ground) is the highest-cost enemy and survives; Bombing Run (SOR_173, Space) defeats
#// only the two enemy space units (JTL_256 cost 2, TWI_228 cost 1) → no Credit.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1Resources: 5
WithP1GroundArena: LAW_053:1:0
WithP1Hand: SOR_173
WithP2GroundArena: LOF_236:1:0
WithP2SpaceArena: JTL_256:1:0
WithP2SpaceArena: TWI_228:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Space

## EXPECT
P1CREDITCOUNT:0
P2SPACEARENACOUNT:0
P2GROUNDARENACOUNT:1

---

# UpgradeHighestCostDestroyed
#// LAW_053 Dengar — an upgrade is not a "unit", so destroying the highest-cost enemy CARD (an upgrade)
#// does not trigger the Credit. Enemy LOF_046 Ezra Bridger (cost 3) is the highest-cost enemy UNIT and
#// survives; SOR_142 Sabine Wren (cost 2) carries SHD_126 The Darksaber (cost 4 upgrade). Vanquish
#// (SOR_078) defeats Sabine and her Darksaber, but Sabine was not the highest-cost unit → no Credit.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1Resources: 8
WithP1GroundArena: LAW_053:1:0
WithP1Hand: SOR_078
WithP2GroundArena: LOF_046:1:0
WithP2GroundArena: SOR_142:1:0
WithP2GroundArenaUpgrade: 1:SHD_126

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P1CREDITCOUNT:0
P2GROUNDARENACOUNT:1

---

# OpponentControlsDengarAndDefeatsHim
#// LAW_053 Dengar — if an opponent takes control of Dengar and defeats him while he is the highest
#// cost, neither player gets a Credit (no enemy unit, from Dengar's perspective, was defeated). P2
#// plays No Glory, Only Results (JTL_043) on the lone enemy Dengar, taking control and defeating him.

## GIVEN
CommonSetup: grk/bbk/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 8
WithP2Hand: JTL_043
WithP1GroundArena: LAW_053:1:0

## WHEN
- P2>PlayHand:0

## EXPECT
P1CREDITCOUNT:0
P2CREDITCOUNT:0
P1GROUNDARENACOUNT:0

---

# OpponentControlsUnitAndDefeatsHighest
#// LAW_053 Dengar — if an opponent takes control of a friendly unit and defeats it while it is the
#// highest cost, Dengar's controller (P1) still gets the Credit, because the unit is an enemy of
#// Dengar at the moment it is defeated. P1 controls Dengar plus SHD_090 Maul (cost 7, the highest).
#// P2 plays No Glory, Only Results (JTL_043) on Maul, taking control and defeating him → P1 Credit.

## GIVEN
CommonSetup: grk/bbk/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 8
WithP2Hand: JTL_043
WithP1GroundArena: LAW_053:1:0
WithP1GroundArena: SHD_090:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-1
- P1>Drain

## EXPECT
P1CREDITCOUNT:1

---

# UsableAgainNextRound
#// LAW_053 Dengar — the once-per-round limit resets each round. Round 1: friendly SOR_046 defeats
#// SOR_095 Battlefield Marine (cost 2, highest) → 1 Credit. After advancing to the next action phase,
#// Round 2: the readied SOR_046 defeats SOR_128 Death Star Stormtrooper (now the highest) → 2nd Credit.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_053:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Deck: SOR_046 SOR_046 SOR_046 SOR_046
WithP2Deck: SOR_046 SOR_046 SOR_046 SOR_046

## WHEN
- P1>AttackGroundArena:1:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>AttackGroundArena:1:0

## EXPECT
P1CREDITCOUNT:2
P2GROUNDARENACOUNT:0

---

# DengarDefeatedSameBatch_StillCredits
#// LAW_053 Dengar — "When the highest-cost enemy unit is defeated, create a Credit (once per round)." This
#// fires even when Dengar himself is defeated in the SAME combat (CR simultaneous-removal: the condition is
#// evaluated before the batch's removals). Dengar (4/3) attacks the enemy 5/4 (P2's only, so highest-cost)
#// and both are defeated — P1 still gets the Credit.

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1GroundArena: LAW_053:1:0
WithP2GroundArena: SEC_167:1:0
WithP1Deck: SOR_095
WithP2Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1CREDITCOUNT:1
