# BaseHitGivesAdvantage
#// ASH_144 Vane's Snub Fighter (Space, 2/4) — When a friendly unit's attack ends: if it dealt combat
#// damage to a base, give an Advantage token to this unit. A friendly Dark Trooper attacks P2's base →
#// ASH_144 gains an Advantage token.
## GIVEN
CommonSetup: rrk/rrk
WithP1SpaceArena: ASH_144:1:0
WithP1GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:3
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:1

---

# AttackUnit_NoBaseHit_NoAdvantage
#// ASH_144 Vane's Snub Fighter — the Advantage needs combat damage to a BASE. When the friendly SEC_080
#// attacks the enemy unit SOR_046 (no base damage), ASH_144 gains nothing.
## GIVEN
CommonSetup: rrk/rrk
WithP1SpaceArena: ASH_144:1:0
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0
