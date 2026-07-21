# DealOneAdvantageOnKill
#// ASH_146 Justifier (Space, 4/5) — When Played/On Attack: you may deal 1 to a unit; if it's defeated this
#// way, give an Advantage token to a unit. Deals 1 to a 3/1 Stormtrooper (dies) → Advantage to itself.
## GIVEN
CommonSetup: rrk/rrk/{myResources:6;handCardIds:ASH_146}
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:1

---

# Pass_NoDamage
#// ASH_146 Justifier — the deal is optional ("you may"). P1 plays Justifier with an enemy present but passes;
#// no damage and no Advantage.
## GIVEN
CommonSetup: rrk/rrk/{myResources:6;handCardIds:ASH_146}
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0

---

# DealNonLethal_NoAdvantage
#// ASH_146 Justifier — the Advantage rider only fires if the unit is DEFEATED by the 1 damage. Dealing 1 to
#// SOR_046 (7 HP) leaves it alive, so no Advantage token is given.
## GIVEN
CommonSetup: rrk/rrk/{myResources:6;handCardIds:ASH_146}
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0

---

# OnAttack_DealOne
#// ASH_146 Justifier — the deal-1 also fires On Attack (not just When Played). A seated Justifier attacks
#// P2's base; On Attack it deals 1 to SOR_046 (survives).
## GIVEN
CommonSetup: rrk/rrk
WithP1SpaceArena: ASH_146:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2BASEDMG:4
