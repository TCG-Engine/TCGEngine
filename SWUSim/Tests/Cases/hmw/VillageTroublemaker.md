# EndorBase_GainsHidden_CannotBeAttackedThePhaseItWasPlayed
#// HMW_176 Village Troublemaker (Aggression, Ewok, 1-cost 2/2 Ground) —
#// "While you control an Endor base, this unit gains Hidden and Saboteur."
#// COVERAGE: offer=N/A (a static keyword grant; it raises no decision of its own — the observable is the
#//           enemy's attack-target POOL, asserted here with ATTACKTARGETS) ·
#//           negative=NonEndorBase_* for BOTH keywords (same boards, a non-Endor base) ·
#//           control=StolenTroublemaker_ReadsTheNEWControllersBase (the base checked is the
#//           CONTROLLER's, proven in both directions) · reqboundary=SaboteurSurvivesTheRequestBoundary ·
#//           decline=N/A (no "you may" anywhere) · suppression=LostAbilities_NoKeywordsEvenWithEndorBase
#// Hidden = "this unit can't be attacked if it was played this phase", so the Troublemaker must be
#// PLAYED here rather than seeded — a seeded unit carries no played-this-phase marker and Hidden would
#// be untestable.
#// DISCRIMINATES via the pool size: P1 also fields SOR_095 and has a base, so P2's attacker would
#// normally see THREE legal targets. With Hidden live it sees TWO — the Troublemaker is removed from
#// the pool, and the other two prove the attack itself is legal.

## GIVEN
CommonSetup: rrw/bgw/{myBase:HMW_023;myResources:2}
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP1Hand: HMW_176
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:HMW_176
P1GROUNDARENAUNIT:1:HASKEYWORD:Hidden
ATTACKTARGETS:2:G:0:2

---

# NonEndorBase_NoHidden_IsAttackable
#// HMW_176 — the negative that makes the gate load-bearing. Identical board with a NON-Endor base
#// (SOR_029 Administrator's Tower, Cloud City): no Hidden, so P2's attacker sees all THREE targets
#// including the freshly-played Troublemaker.

## GIVEN
CommonSetup: rrw/bgw/{myBase:SOR_029;myResources:2}
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP1Hand: HMW_176
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:HMW_176
P1GROUNDARENAUNIT:1:NOTKEYWORD:Hidden
ATTACKTARGETS:2:G:0:3

---

# EndorBase_GainsSaboteur_DefeatsTheDefendersShield
#// HMW_176 — the OTHER granted keyword. Saboteur defeats the defender's Shields before damage, so the
#// 2-power Troublemaker kills a shielded 2/2 outright instead of being absorbed.
#// Seeded here (Saboteur, unlike Hidden, does not depend on being played this phase).
#// Both are 2/2, so they TRADE — the attacker is not alive afterwards and the discrimination is
#// entirely on the defender: defeated (shield broken) vs alive at 0 damage (shield absorbed it).

## GIVEN
CommonSetup: rrw/bgw/{myBase:HMW_023}
P1OnlyActions: true
WithP1GroundArena: HMW_176:1:0
WithP2GroundArena: SOR_207:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1

---

# NonEndorBase_NoSaboteur_ShieldAbsorbsTheHit
#// HMW_176 — the Saboteur negative. Same board, non-Endor base: the Shield is not defeated, absorbs the
#// entire damage instance (CR 8.31), and the 2/2 survives undamaged.

## GIVEN
CommonSetup: rrw/bgw/{myBase:SOR_029}
P1OnlyActions: true
WithP1GroundArena: HMW_176:1:0
WithP2GroundArena: SOR_207:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# StolenTroublemaker_ReadsTheNEWControllersBase
#// HMW_176 — "while YOU control an Endor base" is scoped to whoever controls the Troublemaker, not its
#// owner. P1 OWNS it, P2 CONTROLS it (`WithP2GroundArenaControlled: HMW_176:1` — controller = arena
#// seat, owner = the `:N` argument). P2's base is Endor and P1's is not, so the grant must read P2's.
#// Saboteur is the observable (Hidden needs a played-this-phase marker a seeded unit lacks).
#// P1DISCARDCOUNT is 2, not 1: the two 2/2s TRADE, and the Troublemaker — which P1 OWNS even though
#// P2 controls it — goes to its OWNER's discard alongside P1's own defeated Smuggler. That is the
#// owner-vs-controller split showing up a second time in the same section.

## GIVEN
CommonSetup: rrw/bgw/{myBase:SOR_029;theirBase:HMW_023}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2GroundArenaControlled: HMW_176:1
WithP1GroundArena: SOR_207:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2

---

# StolenTroublemaker_NewControllerHasNoEndorBase_NoSaboteur
#// HMW_176 — the mirror, and the half that proves the scoping is real rather than incidental: the OWNER
#// (P1) now holds the Endor base and the CONTROLLER (P2) does not. An owner-scoped read would defeat the
#// Shield here; a correct controller-scoped read leaves it up and the defender survives.

## GIVEN
CommonSetup: rrw/bgw/{myBase:HMW_023;theirBase:SOR_029}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2GroundArenaControlled: HMW_176:1
WithP1GroundArena: SOR_207:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# SaboteurSurvivesTheRequestBoundary
#// HMW_176 — the request-boundary cell. Production starts a fresh process per answer, so the granted
#// keyword must be recomputed from durable state (the base in play), not anything held in memory.
#// A boundary is inserted before the attack resolves.

## GIVEN
CommonSetup: rrw/bgw/{myBase:HMW_023}
P1OnlyActions: true
WithP1GroundArena: HMW_176:1:0
WithP2GroundArena: SOR_207:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>SimulateRequestBoundary
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1

---

# LostAbilities_NoKeywordsEvenWithEndorBase
#// HMW_176 — the grant is the unit's OWN ability, so a unit that has lost its abilities does not get it
#// even with the Endor base in play. SHD_072 Imprisoned blanks it; the Shield then survives the attack
#// exactly as in the non-Endor negative.

## GIVEN
CommonSetup: rrw/bgw/{myBase:HMW_023}
P1OnlyActions: true
WithP1GroundArena: HMW_176:1:0
WithP1GroundArenaUpgrade: 0:SHD_072
WithP2GroundArena: SOR_207:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
