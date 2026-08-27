# OnAttack_GroundLosesKeywords
#// SEC_185 TIE/ln Fighter (Space, 2/1) — On Attack: you may choose a ground unit; it loses its keywords
#//   (and can't gain keywords) for this phase. SEC_185 attacks P2's base; on attack it strips Sentinel
#//   from the enemy ground unit SOR_037.

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1SpaceArena: SEC_185:1:0
WithP2GroundArena: SOR_037:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:2
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# OnAttack_AutoSkip_NoGroundUnits
#// SEC_185 — the On Attack keyword-strip auto-skips when there are no ground units in play to target;
#//   the attack resolves for 2 with no decision.

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1SpaceArena: SEC_185:1:0
WithP2SpaceArena: SOR_141:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:2
P1NODECISION

---

# OnAttack_MayDecline
#// SEC_185 — the "may" keyword-strip can be declined; the enemy ground unit Gor (TWI_118) keeps Sentinel.

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1SpaceArena: SEC_185:1:0
WithP2GroundArena: TWI_118:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:2
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1NODECISION

---

# KeywordStripExpiresNextPhase
#// SEC_185 — DURATION. The strip is "for this phase", so after passing into the next action phase the
#// enemy SOR_037 Academy Defense Walker must have its printed Sentinel back. The attacker's own READY
#// state is the control: SEC_185 exhausted to attack and the regroup Ready step readies it, proving the
#// phase boundary was crossed. (SEC_185 suppresses '*', so a permanent token blanks that unit for good.)

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithActivePlayer: 1
WithP1SpaceArena: SEC_185:1:0
WithP2GroundArena: SOR_037:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SEC_185
P1SPACEARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:CARDID:SOR_037
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
