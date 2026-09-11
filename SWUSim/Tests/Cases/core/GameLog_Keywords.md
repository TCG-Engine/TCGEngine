# Uniqueness_DefeatSaysWhy
#// Game-log follow-up (2026-09-11). A uniqueness-rule defeat was credited to whatever ability resolved last
#// (at worst "P1's Del Meeko defeated P1's Del Meeko"). It is a game RULE: no source, and a reason.
#// (Fixture from core/UniquenessRule_AsksDefeat.md.)

## GIVEN
CommonSetup: ybk/grw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_034
WithP1GroundArena: SOR_034:1:0

## WHEN
- P1>PlayHand:0
- P1>ChooseMyGroundUnit:1

## EXPECT
P1GROUNDARENACOUNT:1
LOGCONTAINS:P1's [[SOR_034|Del Meeko]] was defeated (uniqueness rule)

---

# Exploit_DefeatSaysWhy
#// Exploit fodder is a COST of playing the card, not the card's ability — "was defeated (Exploit, X)".
#// TWI_115 Osi Sobeck (Exploit 2). (Fixture from twi/OsiSobeck_WardenOfTheCitadel.md.)

## GIVEN
CommonSetup: ggk/grw/{myResources:8;handCardIds:TWI_115}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_035:1:0
WithP2GroundArena: SOR_067:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_115
LOGCONTAINS:P1's [[SEC_080|Imperial Dark Trooper]] was defeated (Exploit, [[TWI_115|Osi Sobeck]])
LOGCOUNT:0:[[TWI_115|Osi Sobeck]] defeated P1's

---

# Saboteur_ShieldsDefeated_HasALine
#// Saboteur's shield half ("defeat the defender's Shields") broke the Shield silently; the attack then read
#// as if the defender never had one. LOF_215 Ascension Cable grants Saboteur. (Fixture from
#// core/UpgradeSaboteur_Grant.md.)

## GIVEN
CommonSetup: yyk/grw
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LOF_215
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
LOGCONTAINS:P1's [[SEC_080|Imperial Dark Trooper]] defeated 1 Shield token on P2's [[SOR_095|Battlefield Marine]] (Saboteur)

---

# DefeatReplacement_L337_SaysWhereHeWent
#// JTL_049 L3-37: "If this unit would be defeated, you may instead attach him as an upgrade to a friendly
#// Vehicle without a Pilot on it." The replacement moved him silently. (Fixture: jtl/L337_GetOutOfMySeat.md.)

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_049:1:0
WithP1GroundArena: SEC_214:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:JTL_049
LOGCONTAINS:P1's [[JTL_049|L3-37]] was attached to P1's [[SEC_214|Skyhopper Canyon Runner]] as a pilot instead of being defeated

---

# DefeatReplacement_LukePilot_MovesToGround
#// JTL_094 Luke Skywalker (pilot): "If this upgrade would be defeated, you may move him to the ground
#// arena as an exhausted unit instead." (Fixture from jtl/LukeSkywalker_YouStillWithMe.md.)

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Resources: 8
WithP2Hand: JTL_078
WithP1GroundArena: SEC_214:1:0
WithP1GroundArenaUpgrade: 0:JTL_094

## WHEN
- P2>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_094
LOGCONTAINS:P1's [[JTL_094|Luke Skywalker]] moved to the ground arena instead of being defeated

---

# DefeatReplacement_Rampart_SaysInsteadOfWhat
#// HMW_060 Vice Admiral Rampart: "If an upgrade on your base would be defeated, you may defeat this unit
#// instead." (Fixture from hmw/ViceAdmiralRampart_ANewEraOfSafety.md.)

## GIVEN
CommonSetup: bbk/rrk
WithActivePlayer: 2
WithP1BaseUpgrade: HMW_081
WithP1GroundArena: HMW_060:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2GroundArena: HMW_121:1:0

## WHEN
- P2>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1BASE:UPGRADECOUNT:1
LOGCONTAINS:P1's [[HMW_060|Vice Admiral Rampart]] was defeated (instead of [[HMW_081|Alliance Shield Generator]] on P1's base)

---

# MaulTwoDefenders_NumbersOnTheAttackLine
#// Known gap from the first sweep: TWI_135 Darth Maul's two-defender attack printed per-hit DAMAGE lines
#// ABOVE a bare "attacked 2 units" summary. Its resolver now suppresses them like the single-defender one
#// and puts the numbers on the ATTACK line. (Fixture from twi/DarthMaul_RevengeAtLast.md.)

## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: [LAW_124:1:0 SOR_236:1:0]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Units
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5
LOGCONTAINS:P1's [[TWI_135|Darth Maul]] attacked 2 units — dealt 5 to P2's [[LAW_124|Industrious Team]] and 5 to P2's [[SOR_236|R2-D2]], took 5 — [[SOR_236|R2-D2]] defeated
#// No per-hit lines ("P2's X took 5 damage") — the ATTACK line owns the numbers.
LOGCOUNT:0:took 5 damage
