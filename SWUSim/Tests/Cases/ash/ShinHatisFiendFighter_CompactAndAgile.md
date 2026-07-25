# CombatDefeat_TwoAdvantage
#// ASH_191 Shin Hati's Fiend Fighter (Space, 3/1) — When Defeated: you may give 2 Advantage tokens to a
#// unit; if NOT defeated by combat, 3 instead. Here ASH_191 attacks SOR_225 (2/1) and dies to the counter
#// (combat defeat) → may give 2 Advantage tokens. The bystander SOR_095 receives them.
## GIVEN
CommonSetup: yyk/yyk
WithP1SpaceArena: ASH_191:1:0
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:2

---

# EffectDefeat_ThreeAdvantage
#// ASH_191 Shin Hati's Fiend Fighter (Space, 3/1) — When Defeated: you may give 2 Advantage tokens to a
#// unit; if NOT defeated by combat damage, give 3 instead. P1 plays Vanquish (SOR_078) on its OWN ASH_191
#// (an effect defeat, not combat) → may give 3 Advantage tokens. The bystander SOR_095 receives them.
## GIVEN
CommonSetup: yyk/yyk/{myResources:7;handCardIds:SOR_078}
WithP1SpaceArena: ASH_191:1:0
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:3

---

# WhenDefeated_MayBePassed
#// ASH_191 Shin Hati's Fiend Fighter — the When Defeated advantage grant is a "may". P1 plays Vanquish
#// (SOR_078) on its OWN ASH_191 (non-combat defeat → offered 3 Advantage), then declines the ability with
#// PASS. No Advantage tokens are handed out; the bystander SOR_095 gets none.
## GIVEN
CommonSetup: yyk/yyk/{myResources:7;handCardIds:SOR_078}
WithP1SpaceArena: ASH_191:1:0
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:PASS
## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0

---

# WhenDefeatedUnderEnemyControl_ThreeAdvantage
#// ASH_191 Shin Hati's Fiend Fighter — When P2 takes control of it with No Glory, Only Results (JTL_043) and
#// defeats it (non-combat → 3 Advantage), the "may give Advantage" resolves under P2's control: P2 may give 3
#// Advantage tokens to any unit and puts them on its OWN SOR_046. The friendly SOR_095 gets none.
## GIVEN
CommonSetup: yyk/yyk
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1SpaceArena: ASH_191:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2Resources: 10
WithP2Hand: JTL_043
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P2>AnswerDecision:myGroundArena-0
## EXPECT
P1SPACEARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:ADVANTAGECOUNT:3
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
