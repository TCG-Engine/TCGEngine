# Darting Strike cancel — v37

1206 main/bonus/opposing-plan situations; 6231 legal keep-or-replace outcomes resolved through the engine. Every legal own bonus and all 201 opposing main-plus-optional-bonus plans have equal weight. Both start at 40 life with intact hands. Only Darting’s owner has a token (Yellow); it is already consumed when choosing. No reactions or carried effects.

Keeping the new 6/0 Darting has mean damage edge -0.69. The tactical cancel chooser produces 1.47 (gain 2.15). It replaces Darting in 818/1206 situations and improves immediate damage edge in 814. The chooser also values objectives and charges permanent card loss, so it does not always maximize damage alone.

Pure best-damage selection, ignoring resource/route costs, gains 2.51 on average. These are one-round coverage figures, not match win probabilities or the total value of a Yellow token. Opponents can react after a replacement during actual play. The full-game batch includes those reactions, existing burns, held advantages, and later turns.

| Selected main | Situations |
|---|---:|
| Sidestep | 75 |
| Darting Strike | 388 |
| Bait and Slip | 118 |
| Outside Step | 231 |
| Glancing Step | 130 |
| Driving Kick | 264 |

Replacement choices preserve the committed bonus and carried effects, burn Darting only if replaced, cannot activate a replacement’s advantage spend, and use the replacement’s normal abilities/objective/destinations. Simultaneous hidden choices, burn locks, and exhausted-hand cases are covered in `cancel-tests.php`.
