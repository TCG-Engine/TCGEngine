# GrantedSaboteurBreaksShield
#// COVERAGE: offer=N/A - ⚠ SITUATIONAL GAP: "attack with a unit" offers the caster's ready units and
#//           no section asserts that pool. Open cell.
#//           decline=N/A - STRUCTURAL: printed mandatory (an event that attacks; the attacker choice is
#//           the effect, not an optional rider).
#//           boundary=TrooperAttacker_Buff / NonTrooper_NoBuff (the Trooper gate as a pair)
#//           control=N/A - STRUCTURAL: an Event with a fixed caster and no owner-scoped zone.
#//           reqboundary=SimulateRequestBoundary_GrantsSurviveTargetPick
#//           modes=2P only - no player reference, no friendly/enemy wording.
#// SOR_168 Precision Fire — the chosen attacker GAINS Saboteur for this attack (it isn't innately a
#// Saboteur). SOR_095 (Trooper, 3/3, no innate Saboteur) gets +2/+0 → 5 power and the granted Saboteur
#// breaks the defender's Shield before combat, so the shielded LAW_124 (4/7) takes the full 5 (DAMAGE:5,
#// shield gone). Without the granted Saboteur the Shield would absorb the hit (DAMAGE:0). The attacker
#// dies to the 4-power counter.

## GIVEN
CommonSetup: rrw/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
WithP1Hand: SOR_168

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENACOUNT:0

---

# NonTrooper_NoBuff
#// SOR_168 Precision Fire — the +2/+0 is conditional on the attacker being a TROOPER. A non-Trooper
#// attacker (LAW_124, 4/7, Underworld/Bounty Hunter) gets Saboteur but NO buff, so it deals only its
#// base 4 to the enemy base.

## GIVEN
CommonSetup: rrw/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP1Hand: SOR_168

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:POWER:4

---

# TrooperAttacker_Buff
#// SOR_168 Precision Fire (Aggression event, cost 1, Tactic) — "Attack with a unit. It gains Saboteur
#// for this attack. If it's a TROOPER, it also gets +2/+0 for this attack." The chosen attacker is a
#// Trooper (SOR_095, 3/3), so it gets +2/+0 → deals 5 to the enemy base. The buff is a one-shot for the
#// attack (ObjectCurrentPower stays 3, only the dealt damage rises).

## GIVEN
CommonSetup: rrw/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SOR_168

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# EventAttack_PassesTheTurn
#// SOR_168 Precision Fire — an "Attack with a unit" EVENT must pass the turn after its attack, like any
#// other action. (Regression: the event's play-finalizer and the attack's combat BOTH used to run the
#// after-action, double-swapping the turn so it never actually passed. Fixed centrally in BeginSWUAttack:
#// an attack launched while a play-finalizer is pending lets the combat own the single turn pass.) Here
#// P1 plays Precision Fire, its lone Trooper auto-attacks the enemy base, and the turn passes to P2.

## GIVEN
CommonSetup: rrw/rrk/{myResources:1}
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SOR_168

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:5
TURNPLAYER:2

---

# SimulateRequestBoundary_GrantsSurviveTargetPick
#// SOR_168 Precision Fire — the attack-target pick ends the request in production, so combat resolves in
#// a fresh process. Both attack-duration grants written at play time (the Saboteur grant and the Trooper
#// +2/+0) must be serialized to still break the Shield and deal 5. Mirrors GrantedSaboteurBreaksShield
#// with the boundary inserted before the answer.

## GIVEN
CommonSetup: rrw/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
WithP1Hand: SOR_168

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENACOUNT:0
