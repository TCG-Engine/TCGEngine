# Twin Suns Phase 3: Darth Maul (TWI_135) "attacks 2 units" can pick units from DIFFERENT opponents in an
# N-player game. With Overwhelm (TWI_119 → 7/8), each defeated 3/3 leaves 4 excess. The 2-player ruling
# "COMBINED excess to the defending player's base" generalizes PER defending player: P2's unit's 4 excess
# spills to P2's base and P3's unit's 4 to P3's base — NOT 8 combined onto one base. Maul takes 3+3=6
# counter (survives at 6 on 8 HP).

## GIVEN
CommonSetup: rrk/bbw
WithSeatOrder: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: TWI_135:1:0
WithP1GroundArenaUpgrade: 0:TWI_119
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_095:1:0
WithP3Base: SOR_019

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Units
- P1>AnswerDecision:p2GroundArena-0&p3GroundArena-0

## EXPECT
SEATCOUNT:3
P2BASEDMG:4
P3BASEDMG:4
P2GROUNDARENACOUNT:0
P3GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:TWI_135
P1GROUNDARENAUNIT:0:DAMAGE:6
