# WhenPlayedReadyAnother
#// LAW_185 Ben Solo (8/8, Hidden) — When Played/When Defeated: ready another friendly unit; it can't be
#// attacked this phase. Ready the exhausted SEC_080.

## GIVEN
CommonSetup: rrw/bgw/{myResources:9}
WithP1GroundArena: SEC_080:0:0
WithP1Hand: LAW_185

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:READY

---

# WhenPlayedProtectionCantBeAttacked
#// LAW_185 Ben Solo — When Played readies another friendly unit AND it can't be attacked this phase.
#// With only the readied SEC_080 (Ben Solo himself is Hidden while controlling it), the enemy SOR_164
#// Wampa has no legal unit target and must attack the base (4 damage). SEC_080 stays ready & undamaged.

## GIVEN
CommonSetup: rrw/bgw/{myResources:9}
WithP1GroundArena: SEC_080:0:0
WithP2GroundArena: SOR_164:1:0
WithP1Hand: LAW_185

## WHEN
- P1>PlayHand:0
- P2>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:4

---

# WhenPlayedSentinelStillAttackable
#// LAW_185 Ben Solo — the "can't be attacked" protection is overridden by Sentinel. Ben Solo readies the
#// exhausted SOR_098 Echo Base Defender (Sentinel, 4/3); despite the protection, Sentinel forces the enemy
#// SOR_164 Wampa (4/5, Overwhelm) to attack it. Echo Base Defender is defeated and 1 excess spills to base.

## GIVEN
CommonSetup: rrw/bgw/{myResources:9}
WithP1GroundArena: SOR_098:0:0
WithP2GroundArena: SOR_164:1:0
WithP1Hand: LAW_185

## WHEN
- P1>PlayHand:0
- P2>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_185
P1BASEDMG:1

---

# WhenDefeatedReadyAnother
#// LAW_185 Ben Solo — the same ability also triggers When Defeated. P2's SHD_079 Rival's Fall defeats
#// Ben Solo; his When Defeated readies the only other friendly unit, the exhausted SEC_080.

## GIVEN
CommonSetup: rrw/bgw/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 6
WithP1GroundArena: [LAW_185:1:0 SEC_080:0:0]
WithP2Hand: SHD_079

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Drain

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:READY
