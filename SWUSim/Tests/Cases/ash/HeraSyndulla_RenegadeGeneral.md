# HealBaseOnBaseHit
#// ASH_031 Hera Syndulla (Ground, 3/4) — When Attack Ends: if this unit dealt combat damage to a base,
#// heal that much damage from your base. P1's base starts at 3 damage; Hera attacks the enemy base (deals
#// 3), then heals 3 from her own base (3 → 0).
## GIVEN
CommonSetup: bbw/bbk/{myBaseDamage:3}
WithP1GroundArena: ASH_031:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:3
P1BASEDMG:0

---

# AttackUnit_NoBaseHit_NoHeal
#// ASH_031 Hera Syndulla — the heal requires combat damage to a BASE. Hera attacks the enemy unit SEC_080
#// (no base damage), so P1's base is not healed (stays at 3).
## GIVEN
CommonSetup: bbw/bbk/{myBaseDamage:3}
WithP1GroundArena: ASH_031:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1BASEDMG:3

---

# HealScalesWithModifiedPower
#// ASH_031 Hera Syndulla — the heal equals the combat damage actually dealt to the base, including power
#// buffs. LAW_205 Flash the Vents makes Hera attack with +2/+0 (power 3 → 5); she deals 5 to the enemy base,
#// so P1's base (8 damage) heals 5 → 3. (Flash the Vents then defeats her, after the attack-ends heal.)
## GIVEN
CommonSetup: rrw/bbk/{myResources:5;handCardIds:LAW_205;myBaseDamage:8}
WithP1GroundArena: ASH_031:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P2BASEDMG:5
P1BASEDMG:3

---

# HealCountsOverwhelmDamageToBase
#// ASH_031 Hera Syndulla — the heal equals combat damage this unit dealt to a base, and OVERWHELM damage
#// that spills onto the enemy base counts. LAW_205 Flash the Vents gives Hera +2/+0 (power 5) and Overwhelm,
#// attacking P2's Vanguard Infantry (SOR_108, 1/2): 2 lethal to the unit + 3 Overwhelm onto the enemy base.
#// Hera then heals 3 from her own base (8 → 5). (Flash the Vents defeats her afterward, after the heal.)
## GIVEN
CommonSetup: rrw/bbk/{myResources:5;handCardIds:LAW_205;myBaseDamage:8}
WithP1GroundArena: ASH_031:1:0
WithP2GroundArena: SOR_108:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:3
P1BASEDMG:5
