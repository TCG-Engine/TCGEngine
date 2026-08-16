# CompletesAttack_DefenderDefeated_Heal
#// SHD_059 Embo (3-cost 3/4 ground, Vigilance, Underworld/Bounty Hunter) — "When this unit completes an
#// attack: If the defender was defeated, heal up to 2 damage from a unit." Embo (3 power) attacks and
#// defeats SOR_128 (3/1), taking 3 counter (survives at 4 HP). Defender defeated → onAttackEnd heals the
#// damaged friendly SOR_046 by 2 (2 damage → 0). Both Embo and SOR_046 are damaged, so the pick is explicit.
#// COVERAGE: offer=CompletesAttack_DefenderDefeated_Heal (two damaged friendlies keep the heal pick
#//           explicit — the winner is named in the answer) · decline=N/A ("up to 2" heal with a single
#//           damaged unit still prompts, but declining heals nobody, an immaterial branch) ·
#//           control=N/A (the heal pool is every damaged unit on either side; no control-change input) ·
#//           boundary=CompletesAttack_DefenderDefeated_Heal (heal) vs
#//           CompletesAttack_DefenderSurvives_NoHeal (no heal) · reqboundary=N/A (the "was the defender
#//           defeated" flag is read inside the same attack resolution)

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_059:1:0
WithP1GroundArena: SOR_046:1:2
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SHD_059
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:DAMAGE:0

---

# CompletesAttack_DefenderSurvives_NoHeal
#// SHD_059 Embo — the heal is gated on the defender being defeated. Embo (3 power) attacks SOR_046 (3/7),
#// which survives → SWU_LAST_DEFENDER_DEFEATED is not set → no heal offer. The damaged friendly SEC_080
#// stays at 2 damage, and there is no pending decision.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_059:1:0
WithP1GroundArena: SEC_080:1:2
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:DAMAGE:2
P1NODECISION

---

# DefenderKilledByTheFlamethrower_HealStillFires
#// Intended: "if the defender was defeated" is satisfied by a defeat at ANY point during the attack, not
#// only by combat damage. Embo (3/4, 3 damage) wears SHD_177 Vambrace Flamethrower (+1/+1 -> 4/5, and an
#// On Attack that deals 3 divided among enemy ground units). He attacks SOR_095 Battlefield Marine (3/3)
#// and all 3 flamethrower damage go to it, so the DEFENDER is dead before combat damage resolves. Embo
#// ends on 1 damage: 3 - 2 healed, and ZERO counter damage — proof the Marine died to the ability, since
#// a surviving 3-power defender would have dealt 3 back. Embo is the only damaged unit, but the heal is a
#// "you may" so it still prompts rather than auto-resolving.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_059:1:3
WithP1GroundArenaUpgrade: 0:SHD_177
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0:3
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SHD_059
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:1
P1NODECISION
