# PicksLeia_NestedChain
#// Nested-chain interaction: Rebel Assault (SOR_103) chooses the deployed Leia (SOR_009) as its
#// first attacker. Ordering must be: (1) Leia attacks BUFFED — Rebel Assault +1 AND her own Raid 1
#// → 3+1+1 = 5 to base; (2) her deployed OnAttackEnd nests FIRST → a second Rebel attacks UNbuffed
#// → 3; (3) THEN Rebel Assault continues → a third Rebel attacks BUFFED (+1) → 4. Total 5+3+4 = 12.
#// COVERAGE: offer=FirstAttackerOffer_ReadyRebelsOnly_NonRebelAndExhaustedExcluded +
#//           SecondAttackerOffer_ExcludesTheFirstAttacker (both pending SELECTABLEEXACT; the excluded
#//           targets are a non-Rebel, an exhausted Rebel, and — for the chained clause — the unit that
#//           just attacked) · reqboundary=ChainSurvivesRequestBoundary ·
#//           control=ControlChange_AStolenRebelIsALegalAttacker ("a Rebel unit" names the trait, not
#//           the owner) · boundary pair=NoRebelUnitAtAll_EventDoesNothing (0 Rebels) vs
#//           OnlyOneRebel_SecondAttackHasNoReferentAndFizzles (1 Rebel → one attack, 4 damage) vs
#//           TwoBuffedAttacks (2 Rebels → two attacks, 8) · decline=N/A — the printed text is "Then,
#//           ATTACK with another Rebel unit" with no "you may", and the chained attack is armed as
#//           mandatory, so the second attacker pick offers no pass. (Contrast SOR_009 Leia's deployed
#//           side, which says "you MAY attack with another Rebel unit" — exercised by
#//           PicksLeia_NestedChain.)

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SOR_103
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>DeployLeader
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-2
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:12
P1LEADER:DEPLOYED

---

# TwoBuffedAttacks
#// SOR_103 Rebel Assault — Event (cost 1, Command/Heroism): "Attack with a Rebel unit. It gets
#// +1/+0 for this attack. Then, attack with another Rebel unit. It gets +1/+0 for this attack."
#// P1 has two 3-power Rebels; each attacks the base for 3+1=4 → 8 total. The +1 is one-shot per
#// attack (POWER stays 3 on both afterward).

## GIVEN
CommonSetup: ggw/grk/{myResources:1;handCardIds:SOR_103}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:8
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:1:POWER:3

---

# ChainSurvivesRequestBoundary
#// SOR_103 Rebel Assault is a GENUINE chained action (attack with a Rebel, THEN another) — distinct from the
#// event/Support "extra action" bug. The event owns the single After Action via SWU_COMBAT_OWNS_AFTERACTION
#// (persisted), so both chained attacks resolve and the turn passes to P2 exactly ONCE even when each attack's
#// target choice crosses a request boundary. Two Rebels (3 power) each attack P2's base for 3+1 = 4 → 8; P2's
#// unit is left untouched (both chose the base). No initiative claimed (so a double-swap would surface).
## GIVEN
CommonSetup: ggw/grk/{myResources:1;handCardIds:SOR_103}
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_021:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirBase-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:8
TURNPLAYER:2

---

# FirstAttackerOffer_ReadyRebelsOnly_NonRebelAndExhaustedExcluded
#// Intended: "Attack with a REBEL unit" — the attacker pool is P1's units that are BOTH Rebel and
#// able to attack. The Battlefield Marine and Consular Security Force (ready Rebels) are in; the
#// Imperial Dark Trooper is out for lacking the trait, and the EXHAUSTED second Marine is out for
#// being unable to attack. Two legal attackers keep the pick interactive, so the decision is left
#// PENDING and the offer itself is the assertion.

## GIVEN
CommonSetup: ggw/grk/{myResources:1;handCardIds:SOR_103}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 SOR_046:1:0 SEC_080:1:0 SOR_095:0:0]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# SecondAttackerOffer_ExcludesTheFirstAttacker
#// Intended: "Then, attack with ANOTHER Rebel unit" — the chained pick must drop the unit that just
#// attacked. Three ready Rebels are seated plus a non-Rebel; after the Marine at index 0 makes the
#// first (buffed) attack, the second offer is exactly the other two Rebels — never the first attacker
#// and never the Imperial Dark Trooper. The chained decision is left PENDING; the base already shows
#// the first attack's 3+1 = 4.

## GIVEN
CommonSetup: ggw/grk/{myResources:1;handCardIds:SOR_103}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 SOR_046:1:0 SOR_095:1:0 SEC_080:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-1&myGroundArena-2
P2BASEDMG:4

---

# OnlyOneRebel_SecondAttackHasNoReferentAndFizzles
#// SOR_103 — boundary against TwoBuffedAttacks (two Rebels → 4+4 = 8). With exactly ONE Rebel on the
#// board the sole attacker auto-resolves, attacks for 3+1 = 4, and the "then, attack with ANOTHER
#// Rebel unit" clause has no referent: it fizzles cleanly with no prompt. The friendly Imperial unit
#// is never eligible and stays ready.

## GIVEN
CommonSetup: ggw/grk/{myResources:1;handCardIds:SOR_103}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 SEC_080:1:0]

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:READY
P1NODECISION

---

# NoRebelUnitAtAll_EventDoesNothing
#// SOR_103 — boundary at zero eligible attackers. P1's only unit is an Imperial Dark Trooper, so
#// neither clause has an attacker: no damage is dealt, the Trooper stays ready and is never asked to
#// attack, and no decision is raised. The event itself is still paid for and discarded.

## GIVEN
CommonSetup: ggw/grk/{myResources:1;handCardIds:SOR_103}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:0
P1GROUNDARENAUNIT:0:READY
P1DISCARDCOUNT:1
P1NODECISION

---

# ControlChange_AStolenRebelIsALegalAttacker
#// SOR_103 — "attack with a Rebel unit" names no owner, only the trait, so a Battlefield Marine P1
#// CONTROLS but P2 OWNS (the end state after a take-control effect) is a legal attacker. It is the
#// only Rebel P1 controls, so it auto-resolves as the first attacker and hits for 3+1 = 4; the second
#// clause then fizzles for want of another Rebel.

## GIVEN
CommonSetup: ggw/grk/{myResources:1;handCardIds:SOR_103}
P1OnlyActions: true
WithP1GroundArenaControlled: SOR_095:2

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:4
P1NODECISION
