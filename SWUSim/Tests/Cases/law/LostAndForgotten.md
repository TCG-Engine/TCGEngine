# DefeatHealBase
#// LAW_133 Lost and Forgotten (Vigilance event, cost 6) — "Defeat a non-leader unit. If you do, heal 3
#// damage from your base." Defeat P2's SEC_080 (single -> auto), heal 3 from P1 base (was at 3 -> 0).

## GIVEN
CommonSetup: bbw/bgw/{myResources:6;myBaseDamage:3}
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_133

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1BASEDMG:0
P1DISCARDCOUNT:1
P2DISCARDCOUNT:1

---

# DefeatFriendlyHealBase
#// LAW_133 Lost and Forgotten — the defeated unit may be FRIENDLY. With only P1's SEC_080 in play it is
#// the lone legal target (auto): P1 defeats its own unit and heals 3 from base (3 -> 0). P1 discard = 2
#// (the event + the defeated unit), P2 untouched.

## GIVEN
CommonSetup: bbw/bgw/{myResources:6;myBaseDamage:3}
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_133

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:0
P1DISCARDCOUNT:2
P2DISCARDCOUNT:0

---

# NoDefeatNoHeal_PhantomImmune
#// LAW_133 Lost and Forgotten — Lurking TIE Phantom (SHD_187) can't be defeated by enemy card abilities.
#// It is the lone legal target (auto): the defeat is prevented, so "if you do" never triggers and the
#// base is NOT healed (stays at 4). The phantom remains in the space arena.

## GIVEN
CommonSetup: bbw/bgw/{myResources:6;myBaseDamage:4}
WithP2SpaceArena: SHD_187:1:0
WithP1Hand: LAW_133

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:1
P1BASEDMG:4
P1DISCARDCOUNT:1

---

# OfferPool_NonLeaderBothSidesBothArenas
#// LAW_133 Lost and Forgotten — offer assertion for "Defeat a NON-LEADER unit". The single printed
#// restriction is "non-leader", so the pool must reach across both controllers and both arenas: the
#// friendly ground unit is in alongside the enemy ground unit and the enemy space unit, while the deployed
#// Cad Bane leader unit at theirGroundArena-1 is the one exclusion — and it is the only body on the board
#// that the restriction can actually remove, which is what makes the assertion non-vacuous. All three
#// existing sections deliberately seed a LONE legal target so the choice auto-resolves, so none of them
#// could have shown a friendly-excluding, ground-only or leader-including pool.
#// COVERAGE: offer=OfferPool_NonLeaderBothSidesBothArenas (pending SELECTABLEEXACT: friendly and enemy,
#//           ground and space, deployed enemy leader unit excluded) · decline=N/A (no "you may" — the
#//           defeat is mandatory and the heal is an "if you do" rider) · boundary
#//           pair=DefeatHealBase / DefeatFriendlyHealBase (defeat lands -> base healed 3) vs
#//           NoDefeatNoHeal_PhantomImmune (defeat prevented -> base untouched) · control=N/A (a one-shot
#//           defeat plus a heal on the caster's own base; no per-unit marker and no controller-scoped
#//           wording to break under a control change) · reqboundary=not encoded (the play and the defeat
#//           answer are separate requests in production; no serialize round-trip section exists yet)

## GIVEN
CommonSetup: bbw/rrk/{myResources:6; theirLeader:ASH_011:1:1:1}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_225:1:0
WithP1Hand: LAW_133

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0&theirSpaceArena-0
