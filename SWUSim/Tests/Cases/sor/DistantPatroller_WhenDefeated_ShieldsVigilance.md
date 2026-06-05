# SOR_060 Distant Patroller (2/1, Space) — When Defeated: You may give a Shield token to a
# [Vigilance] unit. The Patroller attacks SOR_066 (3/4, Vigilance) and dies to the return
# damage (1 HP). Its When Defeated offers a shield to a Vigilance unit. Two Vigilance units
# qualify — friendly 2-1B (SOR_059) and the enemy SOR_066 — so the choice is explicit; here
# the friendly 2-1B is chosen and gains a Shield. (Guards the Vigilance aspect filter.)

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1SpaceArena: SOR_060:1:0     # Distant Patroller (ready) — attacker, dies, idx 0
WithP1GroundArena: SOR_059:1:0    # 2-1B Surgical Droid (Vigilance) — idx 0, shield recipient
WithP2SpaceArena: SOR_066:1:0     # enemy unit (3/4) that kills the Patroller

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P2SPACEARENAUNIT:0:DAMAGE:2
