# FirstDamagePrevented
#// SEC_067 Umbaran Mobile Cannon (Ground, 7/3) — "The first time this unit would take damage each phase,
#//   prevent that damage." SOR_046 (3 power) attacks SEC_067; the first damage instance is prevented →
#//   SEC_067 takes 0 (and its 7-power counter kills SOR_046).

## GIVEN
CommonSetup: bbk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_067:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:0

---

# OnlyFirstDamagePrevented_SecondTaken
#// SEC_067 Umbaran Mobile Cannon — only the FIRST damage instance each phase is prevented; a second
#//   instance in the same phase is taken normally. Umbaran (7/3) attacks SOR_095 Battlefield Marine and
#//   the 3-power counter is prevented (first instance). Then P2's Porg (LOF_254, 1 power) attacks Umbaran
#//   — the second instance this phase — dealing 1 damage. Both enemies die to Umbaran's 7 counter/attack.

## GIVEN
CommonSetup: bbk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_067:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: LOF_254:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENACOUNT:0

---

# PreventionResetsEachPhase
#// SEC_067 Umbaran Mobile Cannon — the prevention is "each phase", not once per game. Umbaran attacks a
#//   Battlefield Marine (SOR_095) and the counter is prevented (phase 1's first instance). After passing
#//   to the next action phase, Umbaran attacks a second Marine and that counter is ALSO prevented (phase
#//   2's first instance), leaving Umbaran at 0 damage.

## GIVEN
CommonSetup: bbk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_067:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>AttackGroundArena:0:0
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# WithShield_FreePreventionKeepsShield
#// SEC_067 Umbaran Mobile Cannon — when Umbaran also carries a Shield token (SOR_T02), the FIRST damage
#//   each phase is prevented by its own free ability, so the Shield is NOT spent. Umbaran attacks SOR_095
#//   (3 power); the counter is prevented and the Shield remains. (End state matches choosing the
#//   prevention over the shield.)

## GIVEN
CommonSetup: bbk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_067:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
