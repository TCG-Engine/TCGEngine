# AttackBase_NotDoubled
#// TWI_135 Darth Maul (5/6) — the double attack is OPTIONAL and only applies to units. With two enemy
#// units + a legal base, Maul is asked Base-vs-Units; choosing "Base" makes an ordinary base attack: deals
#// his 5 to it, no unit multi-select follows, and both enemy units are left untouched.
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: [SOR_095:1:0 SEC_080:1:0]
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Base
## EXPECT
P2BASEDMG:5
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:DAMAGE:0
P1NODECISION

---

# DeclineSecond_SingleAttack
#// TWI_135 Darth Maul (5/6) — attacking a SINGLE unit is still allowed: choose "Units", then the 1-or-2
#// multi-select accepts just ONE unit. Maul picks only LAW_124 (4/7) → an ordinary single attack: LAW_124
#// takes 5 (survives, DAMAGE:5), Maul takes only LAW_124's 4 (DAMAGE:4), and the other enemy SOR_236 is
#// untouched (DAMAGE:0). Both boards keep all units.
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: [LAW_124:1:0 SOR_236:1:0]
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Units
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:DAMAGE:5
P2GROUNDARENAUNIT:1:CARDID:SOR_236
P2GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:4

---

# DoubleAttack_FullPowerToEach
#// TWI_135 Darth Maul (Ground, 5/6) — "This unit can attack 2 units instead of 1. It deals its combat
#// damage to BOTH defenders (full power to each, not split) and both deal their combat damage to it; all
#// simultaneous." Maul double-attacks LAW_124 (4/7) and SOR_236 (1/4): each takes Maul's FULL 5 (LAW_124
#// survives at 5, SOR_236 dies), Maul takes 4+1 = 5 (survives at 5). Proves full-to-each: a split would
#// leave LAW_124 at ~2-3, not 5. UX: base + 2 units in play → an OPTIONCHOOSE (Base vs Units); choosing
#// "Units" opens a 1-or-2 unit multi-select — here both units are picked.
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
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:0:CARDID:TWI_135
P1GROUNDARENAUNIT:0:DAMAGE:5

---

# DoubleAttack_SimultaneousAllDie
#// TWI_135 Darth Maul (5/6) — simultaneity: all combat damage is dealt at once. Maul double-attacks two
#// 3/3 units (SOR_095, SEC_080). Each defender takes Maul's full 5 → both die. Both defenders deal 3 back
#// → Maul takes 3+3 = 6 on 6 HP → Maul dies too, SIMULTANEOUSLY (a defeated defender still deals its
#// combat damage). Board ends empty on both sides.
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: [SOR_095:1:0 SEC_080:1:0]
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Units
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:0

---

# Overwhelm_CombinedExcessToBase
#// TWI_135 Darth Maul — official ruling (2024-10-31): if Maul has Overwhelm, he deals the COMBINED excess
#// of his attack on both defenders to the defending player's base. Maul (5/6) carries TWI_119 Nameless
#// Valor (+2/+2, "Attached unit gains Overwhelm") → 7/8 with Overwhelm. He double-attacks two 3/3 units
#// (SOR_095, SEC_080): each takes 7 → dies with 4 excess → combined 4+4 = 8 to P2's base. Maul takes 3+3 = 6 (survives
#// at 6 on 8 HP).
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP1GroundArenaUpgrade: 0:TWI_119
WithP2GroundArena: [SOR_095:1:0 SEC_080:1:0]
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Units
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1
## EXPECT
P2BASEDMG:8
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:TWI_135
P1GROUNDARENAUNIT:0:DAMAGE:6

---

# Sentinel_FillBothSentinels
#// TWI_135 Darth Maul (5/6) — with 2+ Sentinels present the base is not a legal target, so there is NO
#// Base-vs-Units mode prompt: the unit multi-select is offered directly, restricted to the Sentinels. The
#// opponent controls TWO Sentinel units (SOR_035 2/2, SOR_063 2/4) AND a non-Sentinel (SOR_095 3/3); only
#// the two Sentinels are offered. Maul attacks both: they take 5 each → both die. The non-Sentinel SOR_095
#// is left untouched (reindexes to slot 0, DAMAGE:0). Maul takes 2+2 = 4 (survives).
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: [SOR_035:1:0 SOR_063:1:0 SOR_095:1:0]
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:4

---

# Sentinel_OneSentinelSingleAttackOnly
#// TWI_135 Darth Maul (5/6) — official ruling (2024-10-31): if the defending player controls ANY Sentinel,
#// Maul may only choose Sentinels as his defenders (unless he has Saboteur). With exactly ONE Sentinel
#// (SOR_035, 2/2) and a non-Sentinel (SOR_236, 1/4), Maul CANNOT pair the Sentinel with the free unit — he
#// is limited to a single attack on the Sentinel. SOR_035 takes 5 → dies; Maul takes only 2 (DAMAGE:2);
#// SOR_236 is untouched; and there is NO second-target prompt.
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: [SOR_035:1:0 SOR_236:1:0]
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_236
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:CARDID:TWI_135
P1GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION

---

# SingleEnemy_AttackBaseNormally
#// TWI_135 Darth Maul (5/6) — with only ONE eligible enemy unit, there is no double-attack option: it is
#// an ordinary combat prompt (choose the unit OR the base), NOT a Base-vs-Units mode picker. Here Maul
#// attacks the base normally → deals 5 to it, the lone enemy unit is untouched, and no extra prompt is
#// left pending. (Sibling of the single-enemy test that attacks the unit.)
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:5
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# SingleEnemy_NoDoublePrompt
#// TWI_135 Darth Maul (5/6) — the double attack needs at least 2 eligible enemy units. With only ONE
#// enemy unit (SOR_095, 3/3) in his arena, Maul makes an ordinary single attack: SOR_095 takes 5 → dies,
#// Maul takes 3 (DAMAGE:3), and there is NO second-target prompt left pending.
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:TWI_135
P1GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION
