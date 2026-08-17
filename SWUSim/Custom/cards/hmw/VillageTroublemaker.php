<?php
// HMW_176
// Cost 1 - Village Troublemaker - [Aggression] - Unit (Ground) 2/2 - Trait: Ewok
// Text: While you control an Endor base, this unit gains Hidden and Saboteur.

// Self-conditional keyword grant with no handler: implemented as a `case 'HMW_176'` in the
// self-conditional switch of HasConditionalKeyword_Hidden and _Saboteur (Custom/KeywordEffects.php),
// gated on _SWUControlsBaseWithTrait($controller, 'Endor'). The base checked is the CONTROLLER's.
