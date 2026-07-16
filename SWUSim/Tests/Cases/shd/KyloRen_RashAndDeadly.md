# 170_CaptureReplacement_DefeatAndAoE
#// SHD_170 IG-11 — "If this unit would be captured, defeat him and deal 3 damage to each enemy ground unit
#// instead." P2's Discerning Veteran (SHD_120) tries to capture IG-11; instead IG-11 is defeated and 3 damage
#// hits each of P2's ground units (SOR_046 and SHD_120 itself).

## GIVEN
CommonSetup: rrk/ggk/{theirResources:5}
WithActivePlayer: 2
WithP1GroundArena: SHD_170:1:0
WithP2GroundArena: SOR_046:1:0
WithP2Hand: SHD_120

## WHEN
- P2>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:1:CARDID:SHD_120
P2GROUNDARENAUNIT:1:DAMAGE:3

---

# 170_OnAttack_Deal3ToDamagedGround
#// SHD_170 IG-11 (5-cost 6/5 ground) — "On Attack: You may deal 3 damage to a damaged ground unit." IG-11
#// attacks the base and deals 3 to the already-damaged SOR_046 (2 → 5).

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_170:1:0
WithP2GroundArena: SOR_046:1:2

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# KyloRen_Deployed_MinusPerHandCard
#// SHD_011 Kylo Ren (deployed passive) — "This unit gets -1/-0 for each card in your hand." Deployed
#// (4 resources) with 2 cards in hand: his printed 5 power drops to 3.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_011;myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_046
WithP1Hand: SOR_095

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3

---

# KyloRen_Front_DiscardBuff
#// SHD_011 Kylo Ren (front Action [Exhaust, discard a card]) — "Give a unit +2/+0 for this phase." P1
#// discards SOR_046 from hand (cost) and buffs SOR_095 (3/3 → 5/3).

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_011}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_046
WithP1Hand: SOR_095
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1HANDCOUNT:1
