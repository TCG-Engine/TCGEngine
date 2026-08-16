# Imprisoned_SuppressesAbilities
#// SHD_072 Imprisoned — "Attach to a non-leader unit. Attached unit loses its current abilities and
#// can't gain abilities." A Sentinel unit (SOR_049) wearing Imprisoned loses Sentinel; an identical
#// unit without it keeps Sentinel.
#// COVERAGE: offer=AttachOffer_SpansBothSides (the attach pool reaches an enemy unit — CR 2.e) ·
#//           boundary pair=SuppressesAbilities (a PRINTED keyword lost) paired with
#//           CantGainAbilities_BlocksAnUpgradeGrant (a GRANTED keyword refused) — the card's two
#//           clauses — and each is paired in-fixture with an identical unwearing twin that keeps the
#//           keyword · control=N/A (nothing on this card reads or changes control; the upgrade blanks
#//           whichever unit it is attached to regardless of who controls either) · decline=N/A (no "you
#//           may" — the attach is the whole card and the blanking is constant) ·
#//           reqboundary=N/A (a single attach decision; no state is carried between two requests) ·
#//           removal=RemovedThenTheConstantGrantApplies (the suppression lifts when the upgrade leaves)

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SOR_049:1:0
WithP1GroundArenaUpgrade: 0:SHD_072
WithP1GroundArena: SOR_049:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:HASKEYWORD:Sentinel

---

# Imprisoned_AttachOffer_SpansBothSides
#// SHD_072 — "Attach to a non-leader unit" names no controller, so per CR 2.e an ENEMY unit is just as
#// legal a host as a friendly one (and jailing an opponent's unit is the point of the card). Two units,
#// one per side, keep the pick interactive; the decision is left PENDING so the pool is the assertion.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_072
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# Imprisoned_CantGainAbilities_BlocksAnUpgradeGrant
#// SHD_072 — "can't GAIN abilities" is a SECOND clause, distinct from "loses its current abilities"
#// (which the section above covers via a printed keyword). Here the keyword is not printed at all: it
#// comes from another upgrade, Protector (SOR_057, "attached unit gains Sentinel"). The jailed unit must
#// not get it. Its twin — same card, same Protector, no Imprisoned — does, which is what makes this a
#// suppression assertion rather than a Protector-is-broken assertion.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SHD_072
WithP1GroundArenaUpgrade: 0:SOR_057
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 1:SOR_057

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:HASKEYWORD:Sentinel

---

# Imprisoned_RemovedThenTheConstantGrantApplies
#// SHD_072 — the suppression is not permanent. Confiscate (SOR_251, "defeat an upgrade") removes the
#// Imprisoned from the jailed unit, and Protector's CONSTANT grant — which was live the whole time and
#// merely unreadable — takes effect immediately. Two upgrades sit on the host so the Confiscate pick
#// stays interactive; u0 is the Imprisoned (upgrades seat in declaration order) and u1 the Protector,
#// which is why the host is left holding exactly one upgrade.

## GIVEN
CommonSetup: bbw/bbw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SOR_251
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SHD_072
WithP1GroundArenaUpgrade: 0:SOR_057

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0.u0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_057
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# Imprisoned_AttachOffer_ExcludesDeployedLeaders
#// SHD_072 — "Attach to a NON-LEADER unit." Both players have a deployed leader unit in the ground arena
#// (leaders seat LAST, so they are at index 1 on each side); neither may be offered as a host, while both
#// ordinary units may. The pool spans both sides because the restriction names no controller (CR 2.e).

## GIVEN
CommonSetup: bbw/bbw/{myResources:2;myLeaderDeployed:true;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1Hand: SHD_072
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# Imprisoned_LastingEffectAppliedWhileAttached_DoesNotReturnAfterRemoval
#// SHD_072 — "can't gain abilities" is the stronger half of the card: a grant handed out while the unit
#// was jailed never applied to it, and it does NOT arrive late when the jail is removed. In the Heat of
#// Battle (JTL_077) gives EACH unit Sentinel for the phase; the jailed unit must not have it, and must
#// STILL not have it after Confiscate (SOR_251) defeats the Imprisoned. The unjailed twin having it
#// throughout is what proves the event resolved at all.
#// (Contrast the weaker half, "loses its CURRENT abilities": a keyword the unit already had BEFORE the
#// Imprisoned arrived is only suppressed and does come back — which is why the purge is scoped to the
#// grants gained during the jail window rather than to every grant on the unit.)

## GIVEN
CommonSetup: bbw/bbw/{myResources:3}
P1OnlyActions: true
WithP1Hand: JTL_077
WithP1Hand: SOR_251
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SHD_072
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:HASKEYWORD:Sentinel
