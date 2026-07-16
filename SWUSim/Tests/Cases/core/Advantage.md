# ConsumedAfterAttack
#// Advantage token (ASH_T02) — consumed when the host's ATTACK ends. A Marine (3/3) with 2 Advantage
#// tokens (5 power) attacks the base for 5, then both tokens defeat → power back to 3, 0 tokens left.

## GIVEN
CommonSetup: yrw/grw
WithP1GroundArena: SOR_095:1:0          # Marine (3/3)
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP1GroundArenaUpgrade: 0:ASH_T02

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# DefeatOne_ReQueues
#// Advantage shed "defeat 1" path + re-queue. Krell (2/9, Grit) has 3 Advantage tokens → power 5; attacks
#// the base for 5. At attack end, player resolves the Advantage shed first (EffectStack-1) and chooses
#// "defeat 1" (power 5→4, 2 tokens remain) — the shed re-enters the bag. Player then resolves Krell
#// (EffectStack-0) at power 4 and defeats the 3-HP Snowtrooper (3 < 4). The re-queued shed finally
#// auto-resolves the last 2 tokens. (Had all 3 shed first, Krell's power would be 2 and the Snowtrooper
#// would survive — so its defeat proves exactly one token was shed and the re-queue ordered correctly.)

## GIVEN
CommonSetup: yrw/grw
WithP1GroundArena: LOF_038:1:0          # Pong Krell (2/9, Grit)
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP2GroundArena: IBH_063:2:0          # Snowtrooper (1/3)

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:Defeat_1_Advantage_token
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:5
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1GROUNDARENAUNIT:0:POWER:2

---

# DefenseBonusAndConsumed
#// Advantage token (ASH_T02) — applies on DEFENSE and is consumed when the host's defense ends. P1 Marine
#// (3/3) attacks P2's 1/5 wall (ASH_036) that has 1 Advantage token. The wall defends at 1+1 = 2 power, so
#// the attacker takes 2 counter damage (proves the +1 applied on defense). After combat the wall's token
#// defeats → 0 tokens left, wall took 3 combat damage and survives (5 HP).

## GIVEN
CommonSetup: yrw/grw
WithP1GroundArena: SOR_095:1:0          # Marine attacker (3/3)
WithP2GroundArena: ASH_036:2:0          # 1/5 wall (defender)
WithP2GroundArenaUpgrade: 0:ASH_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P2GROUNDARENAUNIT:0:POWER:1

---

# OrderBeforeKrell_KeepsPower
#// Advantage shed is an ORDERED When-Attack-Ends trigger. LOF_038 Pong Krell (2/9, Grit) has 2 Advantage
#// tokens → power 4. Krell attacks P2's base (4 damage). At attack end there are two triggers: Krell's
#// "defeat a unit with less HP than this unit's power" and the Advantage shed. Resolving Krell FIRST
#// (EffectStack-0) keeps the tokens on, so his power is still 4 and he can defeat the 3-HP Snowtrooper.
#// The Advantage shed then auto-resolves (nothing else pending) → tokens gone, power back to 2.

## GIVEN
CommonSetup: yrw/grw
WithP1GroundArena: LOF_038:1:0          # Pong Krell (2/9, Grit)
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP2GroundArena: IBH_063:2:0          # Snowtrooper (1/3)

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:4
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1GROUNDARENAUNIT:0:POWER:2

---

# ShedAllFirst_LowersPower
#// Advantage shed offers "defeat 1 / defeat all" while another When-Attack-Ends trigger is pending. Same
#// Krell setup, but the player resolves the Advantage shed FIRST (EffectStack-1) and chooses "defeat all".
#// Krell's power drops to 2 before his ability resolves, so the 3-HP Snowtrooper is no longer a legal
#// target (3 is not < 2) and survives. Base still took 4 (power was 4 at combat time; tokens shed after).

## GIVEN
CommonSetup: yrw/grw
WithP1GroundArena: LOF_038:1:0          # Pong Krell (2/9, Grit)
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP2GroundArena: IBH_063:2:0          # Snowtrooper (1/3)

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:Defeat_all_Advantage_tokens

## EXPECT
P2BASEDMG:4
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1GROUNDARENAUNIT:0:POWER:2

---

# StatBonusAndStacking
#// Advantage token (ASH_T02) — a +1/+0 Token Upgrade that stacks. Marine A has 1 token (3→4 power),
#// Marine B has 2 tokens (3→5 power). HP is unaffected (+0).

## GIVEN
CommonSetup: yrw/grw
WithP1GroundArena: SOR_095:1:0          # Marine A (3/3, index 0)
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP1GroundArena: SOR_095:1:0          # Marine B (3/3, index 1)
WithP1GroundArenaUpgrade: 1:ASH_T02
WithP1GroundArenaUpgrade: 1:ASH_T02

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:2
