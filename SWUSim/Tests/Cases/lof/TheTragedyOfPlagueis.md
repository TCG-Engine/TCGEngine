# NoHpDefeat
#// LOF_043 The Tragedy of Plagueis — Choose a friendly unit; this phase it can't be defeated by having no
#// remaining HP. An opponent chooses a unit they control; defeat it. P1 protects Plo Koon; P2 sacrifices
#// SOR_059; then Plo Koon attacks SOR_039 (8 power) and takes 8 lethal counter but SURVIVES at 0 HP.

## GIVEN
CommonSetup: bbk/ggw/{myResources:5;handCardIds:LOF_043}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_039:1:0
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myGroundArena-1
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:8
P2GROUNDARENACOUNT:1

---

# NoTargets_NoEffect
#// LOF_043 — with no units on either side, neither sub-ability has a target; the event still plays for its
#// full cost (5) and goes to discard. Ref: "can be played when there are no targets for either ability".
## GIVEN
CommonSetup: bbk/ggw/{myResources:5;handCardIds:LOF_043}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1HANDCOUNT:0
P1RESAVAILABLE:0

---

# FriendlyOnly_NoEnemy
#// LOF_043 — with only a friendly unit, P1 chooses it for the no-HP-defeat protection; there is no enemy
#// unit for the opponent to defeat, so play passes to P2. Ref: "will allow choosing a friendly unit even
#// if there are no enemy units".
## GIVEN
CommonSetup: bbk/ggw/{myResources:5;handCardIds:LOF_043}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:0
P1RESAVAILABLE:0

---

# EnemyOnly_NoFriendly
#// LOF_043 — with only an enemy unit, there is no friendly unit to protect; the opponent must defeat a
#// unit they control. Ref: "will make an opponent defeat a unit even if there are no friendly units".
## GIVEN
CommonSetup: bbk/ggw/{myResources:5;handCardIds:LOF_043}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:0
P1HANDCOUNT:0

---

# NotPreventDefeatEffect
#// LOF_043 — the protection only stops defeat from having no remaining HP; it does NOT stop a defeat
#// EFFECT. P1 protects Moisture Farmer (0/4), then P2 plays Takedown (defeat a unit with <=5 HP) on it and
#// it is defeated. Ref: "will not prevent friendly unit from dying from a defeat effect".
## GIVEN
CommonSetup: bbk/ggw/{myResources:5;handCardIds:LOF_043}
WithP1GroundArena: SHD_055:1:0
WithP2Hand: SOR_077
WithP2Resources: 6
## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:0

---

# PreventNoHp_ThenExpiresNextPhase
#// LOF_043 — the no-HP-defeat protection lasts only "for this phase". P1 protects Moisture Farmer (0/4),
#// then P2 Open Fire deals 4 (0 remaining HP) but it SURVIVES the action phase. Both players pass to end the
#// phase; the protection expires and the 0-HP unit is defeated. Ref: "will prevent friendly unit from dying
#// due to 0 HP for the current phase" (then dies next phase). Decks seeded so regroup draws add no base dmg.
## GIVEN
CommonSetup: bbk/ggw/{myResources:5;handCardIds:LOF_043}
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: false
WithP1GroundArena: SHD_055:1:0
WithP2Hand: SOR_172
WithP2Resources: 6
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Pass
- P2>Pass
## EXPECT
P1GROUNDARENACOUNT:0
