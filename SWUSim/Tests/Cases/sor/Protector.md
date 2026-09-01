# GrantsSentinel
#// SOR_057 Protector grants Sentinel to its host (upgrade keyword-grant guard)
#// P2 has a vanilla Battlefield Marine (SOR_095, 3/3, no innate Sentinel) with
#// Protector (SOR_057, +1/+1) attached → it becomes a 4/4 and gains Sentinel. While a
#// Sentinel unit is in the arena, P1 cannot attack P2's base — the base-attack is
#// force-redirected onto the only valid target (the Sentinel host).
#// Combat lethality uses CURRENT HP: the attacker (3/3) takes the host's 4 power and dies;
#// the host (4/4) takes the attacker's 3 power and SURVIVES at 3 damage. P2's base takes 0.
#// (Contrast: without Protector the same attack would deal 3 to P2's base and leave
#// both units alive — proving the redirect comes from the granted Sentinel.)
#// COVERAGE: offer=AttachPool_AnyUnitEitherSideBothArenas (decision left pending; the host pool is
#//           every unit in play, both sides and both arenas, per CR 2.e) ·
#//           decline=N/A (the attach target is part of playing the upgrade, and the granted Sentinel
#//           is a static keyword with no "you may" — nothing on this card is ever offered as optional) ·
#//           control=StolenHost_SentinelWorksForTheNewController (owner ≠ controller: the grant is read
#//           from the host's CONTROLLER, so a stolen host guards its new controller's base) ·
#//           boundary pair=GrantsSentinel / Sentinel_ProtectsTheUnhostedAllyToo (grant ON: base and
#//           allies out of the attack pool) vs NoProtector_BaseTakesTheHit (grant OFF: same board,
#//           base takes the full hit) and ArenaScope_GroundHostDoesNotStopASpaceAttack (in-arena vs
#//           other-arena attacker) ·
#//           reqboundary=N/A (a static keyword grant, recomputed from the upgrade on every read — no
#//           phase- or turn-scoped state is written that a request boundary could drop; the pending
#//           attach offer in AttachPool_AnyUnitEitherSideBothArenas is itself read at end state)

## GIVEN
CommonSetup: yrw/yrw
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0    # Battlefield Marine (3/3), ready
WithP2GroundArena: SOR_095:1:0    # Battlefield Marine (3/3), ready
WithP2GroundArenaUpgrade: 0:SOR_057   # Protector → host gains Sentinel

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENACOUNT:0

---

# NoProtector_BaseTakesTheHit
#// SOR_057 Protector — the NEGATIVE that makes GrantsSentinel load-bearing. Identical board with the
#// upgrade removed: the Battlefield Marine has no Sentinel, so P1's declared BASE attack is NOT
#// redirected — P2's base takes the full 3 and both units end untouched. Without this control the
#// section above could pass on an engine that redirected every base attack.

## GIVEN
CommonSetup: yrw/yrw
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# HostOnly_GainsSentinelAndStats
#// SOR_057 Protector — the static half read directly, with no combat. "ATTACHED unit gains Sentinel"
#// is scoped to the host alone: the hosted Battlefield Marine has Sentinel and is a 4/4 (the printed
#// +1/+1 of the upgrade), while P2's OTHER ground unit — same controller, no upgrade — has neither the
#// keyword nor a stat change. The attack-target count for P1's ground unit collapses to exactly 1
#// (base and the unhosted ally are both out of the pool), which is the offer this card creates.

## GIVEN
CommonSetup: yrw/yrw
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_046:1:0]
WithP2GroundArenaUpgrade: 0:SOR_057

## WHEN
- P1>Pass

## EXPECT
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P2GROUNDARENAUNIT:0:POWER:4
P2GROUNDARENAUNIT:0:HP:4
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:1:NOTKEYWORD:Sentinel
P2GROUNDARENAUNIT:1:POWER:3
P2GROUNDARENAUNIT:1:HP:7
ATTACKTARGETS:1:G:0:1

---

# Sentinel_ProtectsTheUnhostedAllyToo
#// SOR_057 Protector — the reminder text is "can't attack your non-Sentinel UNITS or your base", so the
#// grant shields the whole side, not just its own host. P2 fields the hosted Marine plus a bare
#// Consular Security Force; P1's declared BASE attack has only one legal target left and auto-resolves
#// onto the Sentinel host. The bare ally ends at zero damage and the base is clean — the ally being
#// untouched is the assertion a self-only reading of Sentinel would fail.

## GIVEN
CommonSetup: yrw/yrw
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_046:1:0]
WithP2GroundArenaUpgrade: 0:SOR_057

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:0
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENACOUNT:0

---

# ArenaScope_GroundHostDoesNotStopASpaceAttack
#// SOR_057 Protector — "Units in THIS ARENA can't attack…". The grant is arena-scoped: a Sentinel on a
#// GROUND unit constrains ground attackers only. P2's hosted ground Marine is Sentinel, but P1's space
#// TIE fighter is in the other arena, so P2's base is still a legal target and takes the full 2. The
#// ground host is untouched. Pairs with Sentinel_ProtectsTheUnhostedAllyToo, where the same Sentinel
#// DOES bind a ground attacker.

## GIVEN
CommonSetup: yrw/yrw
P1OnlyActions: true
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_057

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# FriendlyHost_ForcesTheEnemyAttackOntoIt
#// SOR_057 Protector — the other dispatch side. Every section above hangs the upgrade on a P2 unit and
#// reads it from P1's attack; here P1 owns the host and P2 is the one constrained, proving the grant is
#// read from the HOST's controller and not from a fixed seat. P2's Battlefield Marine declares an
#// attack on P1's base and is redirected onto the 4/4 hosted Marine: the base takes nothing, the host
#// takes 3, and P2's attacker takes 4 and dies.

## GIVEN
CommonSetup: yrw/yrw
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_057
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:0
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:0

---

# StolenHost_SentinelWorksForTheNewController
#// SOR_057 Protector — the CONTROL axis. The Battlefield Marine wearing Protector is OWNED by P2 but
#// CONTROLLED by P1 (the end state after a take-control effect). "your non-Sentinel units or your base"
#// is read from the CONTROLLER, so the stolen unit must now guard P1's base against its own owner:
#// P2's attack on P1's base is redirected onto it. A seat-bound reading of the grant would leave P1's
#// base exposed here while still passing every section above.

## GIVEN
CommonSetup: yrw/yrw
WithActivePlayer: 1
WithP1GroundArenaControlled: SOR_095:2
WithP1GroundArenaUpgrade: 0:SOR_057
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:0
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:0

---

# AttachPool_AnyUnitEitherSideBothArenas
#// SOR_057 Protector — the OFFER axis. Protector prints no attach restriction ("Attached unit gains
#// Sentinel" and nothing more), so per CR 2.e the host pool is EVERY unit in play, friendly and enemy,
#// ground and space. Answering a target would only prove one branch, so the decision is left PENDING
#// and the pool read directly: four units across all four arena/side combinations, all four offered.
#// This is also the case that would expose a wrongly-copied "friendly only" or "same arena" filter.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_057
WithP1GroundArena: SOR_128:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SHD_060:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0&theirSpaceArena-0
