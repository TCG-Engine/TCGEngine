# BuffAndAttack
#// ASH_109 T-6 Shuttle 1974 (Space, 2/6, Sentinel) — Action [Exhaust]: give another unit +2/+2 for this
#// phase. You may attack with that unit. T-6 buffs SOR_095 (3/3 → 5/5); the player attacks the enemy base
#// with it for 5.
## GIVEN
CommonSetup: ggk/ggk
WithP1SpaceArena: ASH_109:1:0
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:mySpaceArena-0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:5
P2BASEDMG:5

---

# BuffEnemyUnit
#// ASH_109 T-6 Shuttle 1974 — "another unit" may target an ENEMY unit. T-6 buffs the enemy SOR_164 (Wampa,
#// 4/5 → 6/7). It is not a friendly unit, so no attack follows; the buff still applies for the phase.
## GIVEN
CommonSetup: ggk/ggk
WithP1SpaceArena: ASH_109:1:0
WithP2GroundArena: SOR_164:1:0
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:mySpaceArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:POWER:6
P2GROUNDARENAUNIT:0:HP:7

---

# BuffWithoutAttacking
#// ASH_109 T-6 Shuttle 1974 — the follow-up attack is optional. Buff SOR_095 (+2/+2 → 5/5) then decline
#// the attack: the buff stands and no damage is dealt to the enemy base.
## GIVEN
CommonSetup: ggk/ggk
WithP1SpaceArena: ASH_109:1:0
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:mySpaceArena-0
- P1>AnswerDecision:PASS
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P2BASEDMG:0

---

# BuffExhaustedUnit
#// ASH_109 T-6 Shuttle 1974 — the +2/+2 target need not be ready; an already-exhausted friendly unit can
#// be chosen. SOR_095 (exhausted) is buffed to 5/5. It cannot then attack (already exhausted).
## GIVEN
CommonSetup: ggk/ggk
WithP1SpaceArena: ASH_109:1:0
WithP1GroundArena: SOR_095:0:0
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:mySpaceArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
