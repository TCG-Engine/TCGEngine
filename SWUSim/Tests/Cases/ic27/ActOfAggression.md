# AllThreeClauses
#// IC27_127 Act of Aggression (Event, cost 4, Aggression/Aggression, Innate)
#// "Draw a card. You may deal 3 damage to a unit. Give a friendly unit +3/+0 and Overwhelm for this phase."
#// Three independent clauses, chained like IC27_046 Vigilant Defense so each pool is built when its clause runs:
#// draw -> an optional pick over every unit (MZMAYCHOOSE) dealing 3 -> a mandatory pick over the friendly (team)
#// units, which gets the registered IC27_127 STAT_BUFF (+3/+0) and the OVERWHELM grant, both for this phase.
#//
#// COVERAGE: offer=Offer_DamagePool_EveryUnit + Offer_BuffPool_FriendlyUnitsOnly ·
#//           decline=DeclineTheDamage_BuffStillResolves ·
#//           boundary=AllThreeClauses (3 damage kills a 3/3) paired with Offer_DamagePool_EveryUnit's survivor
#//           check — see DamageIsThree_SevenHpSurvives ·
#//           control=N/A (no owner-scoped zone) ·
#//           reqboundary=AnsweredAcrossRequestBoundary ·
#//           modes=2P,TeamSuns (text says "friendly") · TeamSuns=TeamSuns_TeammatesUnitIsFriendly
#//
#// P1 draws, deals 3 to P2's Imperial Dark Trooper (3/3 — defeated), and the lone friendly Battlefield Marine
#// auto-receives +3/+0 and Overwhelm.

## GIVEN
CommonSetup: rrw/bbk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_127
WithP1Deck: [SOR_128 SOR_128 SOR_128]
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:2
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm
P1NODECISION

---

# DamageIsThree_SevenHpSurvives
#// Quantity: exactly 3 — Industrious Team (4/7) ends on 3 damage.

## GIVEN
CommonSetup: rrw/bbk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_127
WithP1Deck: [SOR_128 SOR_128 SOR_128]
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:1:DAMAGE:3
P2GROUNDARENACOUNT:2

---

# Offer_DamagePool_EveryUnit
#// "a unit" — the whole table, P1's own included. Left pending.

## GIVEN
CommonSetup: rrw/bbk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_127
WithP1Deck: [SOR_128 SOR_128 SOR_128]
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: IC27_116:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0

---

# DeclineTheDamage_BuffStillResolves
#// "You may" — declining the damage still leaves the draw and the buff.

## GIVEN
CommonSetup: rrw/bbk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_127
WithP1Deck: [SOR_128 SOR_128 SOR_128]
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm

---

# Offer_BuffPool_FriendlyUnitsOnly
#// The buff pool: P1's units only (both arenas), never the enemy's. Damage declined first.

## GIVEN
CommonSetup: rrw/bbk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_127
WithP1Deck: [SOR_128 SOR_128 SOR_128]
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: IC27_116:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0

---

# DamageKillsOwnUnit_BuffPoolBuiltAfter
#// Ordering: the buff pool is built AFTER the damage. P1 deals 3 to its own Death Star Stormtrooper (3/1 — dies);
#// the only friendly unit left (the Marine) then auto-receives the buff.

## GIVEN
CommonSetup: rrw/bbk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_127
WithP1Deck: [SOR_128 SOR_128 SOR_128]
WithP1GroundArena: SOR_128:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:6
P1NODECISION

---

# NoFriendlyUnit_DrawAndDamageStillHappen

## GIVEN
CommonSetup: rrw/bbk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_127
WithP1Deck: [SOR_128 SOR_128 SOR_128]
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1HANDCOUNT:1
P2GROUNDARENACOUNT:0
P1NODECISION

---

# OverwhelmIsReal_ThenExpires
#// The buffed Consular Security Force (6/7, Overwhelm) attacks a Death Star Stormtrooper (3/1): 5 excess to P2's
#// base. After the
#// phase ends both the +3/+0 and the Overwhelm are gone.

## GIVEN
CommonSetup: rrw/bbk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_127
WithP1Deck: [SOR_128 SOR_128 SOR_128 SOR_128]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P1>AttackGroundArena:0:0
- P1>Pass

## EXPECT
P2BASEDMG:5
PHASEISNOT:MAIN
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:NOTKEYWORD:Overwhelm

---

# AnsweredAcrossRequestBoundary

## GIVEN
CommonSetup: rrw/bbk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_127
WithP1Deck: [SOR_128 SOR_128 SOR_128]
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: IC27_116:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1SPACEARENAUNIT:0:POWER:5
P1SPACEARENAUNIT:0:HASKEYWORD:Overwhelm
P1GROUNDARENAUNIT:0:POWER:3

---

# TeamSuns_TeammatesUnitIsFriendly
#// Team Suns: the buff pool holds teammate P3's unit (and no enemy's). P1 has no unit; damage declined.

## GIVEN
CommonSetup: rrw/bbk/{myResources:4}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: IC27_127
WithP1Deck: [SOR_128 SOR_128 SOR_128]
WithP2GroundArena: SEC_080:1:0
WithP3GroundArena: SOR_095:1:0
WithP4GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:POWER:6
P3GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm
P2GROUNDARENAUNIT:0:POWER:3
P4GROUNDARENAUNIT:0:POWER:3
