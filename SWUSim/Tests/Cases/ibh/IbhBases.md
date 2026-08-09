# ForwardCommandPost_NineteenDamageIsSurvivable
#// IBH_054 Forward Command Post is a 20-HP base, not the usual 30. There is no direct base-HP assertion
#// in the harness, so the threshold is proven as a BOUNDARY PAIR: at 19 damage the base is still
#// standing and the game is not over. Paired with the 20-damage section below — on a 30-HP base neither
#// value would be lethal, so the pair is what pins the number at exactly 20.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2;myBase:IBH_002;theirBase:IBH_054;theirBaseDamage:17}
P1OnlyActions: true
WithP1Hand: IBH_059

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:19

---

# ForwardCommandPost_TwentyDamageIsLETHAL
#// IBH_054 Forward Command Post — the other half of the pair: 18 damage + this event's 2 reaches exactly
#// 20, the base is defeated and P1 wins. Against a 30-HP base this would simply sit at 20 damage.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2;myBase:IBH_002;theirBase:IBH_054;theirBaseDamage:18}
P1OnlyActions: true
WithP1Hand: IBH_059

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:20
P1WIN

---

# EchoCaverns_TwentyDamageIsLETHAL
#// IBH_002 Echo Caverns — the OTHER IBH base, confirmed at 20 HP the same way from the opposite side:
#// P2 pushes P1's Echo Caverns from 18 to exactly 20 and wins. Both IBH bases are 20, which is what
#// makes this set's games shorter than a standard 30-HP matchup.

## GIVEN
CommonSetup: rrk/rrk/{myBase:IBH_002;myBaseDamage:18;theirBase:IBH_054;theirResources:2}
WithActivePlayer: 2
WithP2Hand: IBH_059

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirBase-0

## EXPECT
P1BASEDMG:20
P2WIN
