# AttackEnd_NoShield_WhenHaloDiesToCombat
#// ASH_223 Halo (Space, 4/4, Support) — When Attack Ends: if the defending unit was defeated, give a Shield
#// token to Halo. If Halo itself is defeated by the counterattack the rider cannot resolve. Halo attacks
#// ASH_037 (6/6): Halo deals 4 (defender survives) and takes 6 counter, so Halo is defeated — no Shield.
## GIVEN
CommonSetup: grk/grk
WithP1SpaceArena: ASH_223:1:0
WithP2SpaceArena: ASH_037:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENAUNIT:0:CARDID:ASH_037
P2SPACEARENAUNIT:0:DAMAGE:4

---

# AttackEnd_GainsNewShield_WhileAlreadyShielded
#// ASH_223 Halo — the rider grants a fresh Shield even when Halo already carries one. Halo (with a Shield)
#// attacks SOR_225 (2/1) and kills it; the 2 counter is prevented by the existing Shield (which pops, so
#// Halo takes 0), then the rider grants a new Shield — Halo ends with 1 Shield and 0 damage.
## GIVEN
CommonSetup: grk/grk
WithP1SpaceArena: ASH_223:1:0
WithP1SpaceArenaUpgrade: 0:SOR_T02
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:ASH_223
P1SPACEARENAUNIT:0:DAMAGE:0
P1SPACEARENAUNIT:0:SHIELDCOUNT:1

---

# AttackEnd_NoShield_WhenDefeatedByOwnAbilityInSameWindow
#// ASH_223 Halo — if Halo is defeated in the same resolution window, the Shield rider does not persist. Via
#// SOR_150 Heroic Sacrifice, Halo attacks with +2/+0 and gains "When this unit deals combat damage: Defeat
#// it." It kills SOR_225 (2/1) and, in the same trigger window, the defeat-it and the give-Shield riders are
#// both pending; resolving the defeat-it defeats Halo, so it lands in the discard with no Shield.
## GIVEN
CommonSetup: rrw/grw/{myResources:9;handCardIds:SOR_150}
WithP1SpaceArena: ASH_223:1:0
WithP2SpaceArena: SOR_225:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:EffectStack-0
## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:0
P1DISCARDUNIT:1:CARDID:ASH_223

---

# AttackEnd_GainsShield_FromZero_WhenDefenderDefeated
#// ASH_223 Halo (Space, 4/4) — When Attack Ends: if the defending unit was defeated, give Halo a Shield.
#// From no prior Shield, Halo attacks SOR_225 (2/1) and kills it, taking the 2 counter (survives), then the
#// rider grants a Shield — Halo ends with 1 Shield and 2 damage.
## GIVEN
CommonSetup: grk/grk
WithP1SpaceArena: ASH_223:1:0
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:ASH_223
P1SPACEARENAUNIT:0:DAMAGE:2
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
