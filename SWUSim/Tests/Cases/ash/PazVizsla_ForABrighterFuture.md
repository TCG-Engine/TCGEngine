# CombatDefeat_NoTokens
#// ASH_028 Paz Vizsla (Ground, 5/7, Sentinel) — When Defeated: if NOT defeated by combat damage, create
#// 2 Mandalorian tokens. Here it IS defeated by combat (pre-damaged to 1 HP, attacks SEC_080 and dies to
#// the counter), so NO tokens are created. (ASH_028 deals 5 → SEC_080 dies too.)

## GIVEN
CommonSetup: yrw/grw
WithP1GroundArena: ASH_028:1:6
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0

---

# EffectDefeat_TwoTokens
#// ASH_028 Paz Vizsla — When Defeated NOT by combat damage → create 2 Mandalorian tokens. P1 plays
#// Vanquish (SOR_078, "defeat a non-leader unit") on its OWN ASH_028, so the defeat is an effect (not
#// combat) and resolves inline on P1's action → 2 Mandalorian tokens replace ASH_028.

## GIVEN
CommonSetup: brk/rrk/{myResources:5;handCardIds:SOR_078}
WithP1GroundArena: ASH_028:1:0
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:ASH_T01
P1GROUNDARENAUNIT:1:CARDID:ASH_T01
