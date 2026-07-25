# Iden_HealsOnRegroupEnemyDefeat
#// REGROUP-PHASE ENEMY-DEFEAT REACTION family (interaction hardening — turn-machinery regression guard).
#// Every variant in this file shares ONE setup + first steps, differing only in the "when an enemy unit is
#// defeated" V unit and how P1's reaction resolves. Shared shape:
#//   • P1 has the V unit in play PLUS SEC_080 Imperial Dark Trooper (a vanilla 3/3 sacrificial friendly).
#//   • P2 holds the initiative (UNCLAIMED), is the active player, and has JTL_216 Contracted Hunter in hand.
#//   • Both decks are seeded so the two regroup draws don't deal CR 6.1 empty-deck damage (2*3 = 6 to base).
#// Shared flow:
#//   1. P2 plays Contracted Hunter with Ambush into P1's Dark Trooper, defeating it. The Dark Trooper is
#//      FRIENDLY to P1, so the V unit's "when an ENEMY unit is defeated" reaction does NOT fire. The Hunter
#//      (4/4) takes the 3 counter and survives at 1 HP.
#//   2. P1 claims the unclaimed initiative.
#//   3. P2 passes; P1 passes — two consecutive passes end the action phase.
#//   4. At regroup start the Hunter self-defeats ("When the regroup phase starts: defeat this unit"). It is
#//      an ENEMY unit of P1, so the V unit's reaction fires DURING REGROUP.
#// The bug this family guards (fixed by the trigger/drain rewrite): a regroup-phase enemy defeat must still
#// drain P1's queued static "when an enemy unit is defeated" reaction. The net effect (exactly one heal /
#// one ping / one Experience) proves ONLY the regroup enemy defeat counted, never the friendly Dark Trooper.
#//
#// V = SOR_002 Iden Versio deployed as a leader unit (ground index 1): "When an enemy unit is defeated: Heal
#// 1 damage from your base." No decision — auto-heals 1 (base 3 -> 2).

## GIVEN
CommonSetup: bbk/yyk/{
  myBaseDamage:3;
  myLeaderDeployed:1
}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: false
WithActivePlayer: 2
WithP1GroundArena: SEC_080:1:0
WithP2Hand: JTL_216
WithP2Resources: 5
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:YES
- P2>AnswerDecision:theirGroundArena-0
- P1>Claim
- P2>Pass
- P1>Pass

## EXPECT
P1BASEDMG:2
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_002
P2GROUNDARENACOUNT:0

---

# GideonHask_SelfExpOnRegroupEnemyDefeat
#// Same shared flow. V = SOR_036 Gideon Hask (5/5, ground index 1): "When an enemy unit is defeated: Give an
#// Experience token to a friendly unit." After the Dark Trooper dies the only friendly unit left is Gideon
#// himself, so the single-target choice auto-resolves onto him — he gains one Experience token (+1/+1 -> 6/6).

## GIVEN
CommonSetup: bgw/yyk/{myBaseDamage:0}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: false
WithActivePlayer: 2
WithP1GroundArena: [SEC_080:1:0 SOR_036:1:0]
WithP2Hand: JTL_216
WithP2Resources: 5
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:YES
- P2>AnswerDecision:theirGroundArena-0
- P1>Claim
- P2>Pass
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_036
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:6
P2GROUNDARENACOUNT:0

---

# Hk47_PingsEnemyBaseOnRegroupEnemyDefeat
#// Same shared flow. V = LOF_130 HK-47 (2/4, ground index 1): "When an enemy unit is defeated: Deal 1 damage
#// to its controller's base." No decision — the defeated Hunter's controller is P2, so P2's base takes 1.

## GIVEN
CommonSetup: bgw/yyk/{myBaseDamage:0}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: false
WithActivePlayer: 2
WithP1GroundArena: [SEC_080:1:0 LOF_130:1:0]
WithP2Hand: JTL_216
WithP2Resources: 5
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:YES
- P2>AnswerDecision:theirGroundArena-0
- P1>Claim
- P2>Pass
- P1>Pass

## EXPECT
P2BASEDMG:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_130
P2GROUNDARENACOUNT:0

---

# BoKatan_SelfExpOnRegroupEnemyDefeat
#// Same shared flow. V = SEC_051 Bo-Katan Kryze (8/8, ground index 1): "When an enemy unit is defeated: Give
#// an Experience token to a friendly unit." After the Dark Trooper dies she is the only friendly unit, so
#// the single-target choice auto-resolves onto her — she gains one Experience token (+1/+1 -> 9/9). (Her
#// "When Played: give each enemy unit -3/-3" does NOT fire — she is pre-placed, not played.)

## GIVEN
CommonSetup: bgw/yyk/{myBaseDamage:0}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: false
WithActivePlayer: 2
WithP1GroundArena: [SEC_080:1:0 SEC_051:1:0]
WithP2Hand: JTL_216
WithP2Resources: 5
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:YES
- P2>AnswerDecision:theirGroundArena-0
- P1>Claim
- P2>Pass
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_051
P1GROUNDARENAUNIT:0:POWER:9
P1GROUNDARENAUNIT:0:HP:9
P2GROUNDARENACOUNT:0

---

# Chimaera_HealsTwoOnRegroupEnemyDefeat
#// Same shared flow, EXCEPT V = ASH_052 Chimaera is a SPACE unit, so P1's ground arena holds only the Dark
#// Trooper. The ground Hunter therefore has a single legal Ambush target (the Dark Trooper) and auto-resolves
#// it — no target-choice answer is needed. Chimaera: "When an enemy unit is defeated: Heal 2 damage from your
#// base." No decision — auto-heals 2 (base 3 -> 1). (Her "When Played" defeat clause does NOT fire — she is
#// pre-placed, not played.)

## GIVEN
CommonSetup: bgw/yyk/{myBaseDamage:3}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: false
WithActivePlayer: 2
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: ASH_052:1:0
WithP2Hand: JTL_216
WithP2Resources: 5
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:YES
- P1>Claim
- P2>Pass
- P1>Pass

## EXPECT
P1BASEDMG:1
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:ASH_052
P2GROUNDARENACOUNT:0
