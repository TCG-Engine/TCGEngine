<?php
// GameLayout.php — Container divs for all BindTo zones in GrandArchiveSim.
// Included from InitialLayout.php after the main split-screen structure.
// Edit this file to change the visual layout of zones. Regenerating the schema
// does NOT overwrite this file.
//
// Naming convention:
//   Player-scoped zones: my{BindTo} and their{BindTo}
//   Global zones:        {BindTo}  (no prefix)
?>
<style>
  .ga-zone { position: fixed; z-index: 30; pointer-events: auto; }
</style>

<!-- =================== MY ZONES (bottom half) =================== -->

<!-- myDeckSlot: bottom-right corner -->
<div id="myDeckSlot" class="ga-zone"
     style="bottom:120px; right:10px;">
</div>

<!-- myBanishSlot: above deck -->
<div id="myBanishSlot" class="ga-zone"
     style="bottom:230px; right:10px;">
</div>

<!-- myGraveyardSlot: bottom-right -->
<div id="myGraveyardSlot" class="ga-zone"
     style="bottom:10px; right:10px;">
</div>

<!-- myHandSlot: bottom-center -->
<div id="myHandSlot" class="ga-zone"
     style="bottom:10px; left:28%;">
</div>

<!-- myFieldSlot: upper-right of bottom half -->
<div id="myFieldSlot" class="ga-zone"
     style="top:calc(50% + 110px); right:120px; overflow-y:visible;">
</div>

<!-- myIntentSlot: bottom-left stack area -->
<div id="myIntentSlot" class="ga-zone"
     style="bottom:230px; left:10px;">
</div>

<!-- myMemorySlot: bottom-left corner -->
<div id="myMemorySlot" class="ga-zone"
     style="bottom:10px; left:10px;">
</div>

<!-- myMaterialSlot: upper-left of bottom half -->
<div id="myMaterialSlot" class="ga-zone"
     style="top:calc(50% + 120px); left:10px;">
</div>

<!-- myHealthSlot: top-right of bottom half -->
<div id="myHealthSlot" class="ga-zone"
     style="top:calc(50% + 10px); right:130px;">
</div>

<!-- myMasterySlot: upper-left of bottom half (offset from material) -->
<div id="myMasterySlot" class="ga-zone"
     style="top:calc(50% + 120px); left:120px;">
</div>

<!-- =================== THEIR ZONES (top half) =================== -->

<!-- theirDeckSlot: mirrors my deck position in the top half -->
<div id="theirDeckSlot" class="ga-zone"
     style="top:120px; right:10px;">
</div>

<!-- theirBanishSlot: mirrors my banish position in the top half -->
<div id="theirBanishSlot" class="ga-zone"
     style="top:230px; right:10px;">
</div>

<!-- theirGraveyardSlot: mirrors my graveyard in the top half -->
<div id="theirGraveyardSlot" class="ga-zone"
     style="top:10px; right:10px;">
</div>

<!-- theirHandSlot: mirrors my hand in the top half -->
<div id="theirHandSlot" class="ga-zone"
     style="top:10px; left:28%;">
</div>

<!-- theirFieldSlot: mirrors my field in the top half -->
<div id="theirFieldSlot" class="ga-zone"
     style="top:110px; right:120px; overflow-y:visible;">
</div>

<!-- theirIntentSlot: mirrors my intent in the top half -->
<div id="theirIntentSlot" class="ga-zone"
     style="top:230px; left:10px;">
</div>

<!-- theirMemorySlot: mirrors my memory in the top half -->
<div id="theirMemorySlot" class="ga-zone"
     style="top:10px; left:10px;">
</div>

<!-- theirMaterialSlot: mirrors my material in the top half -->
<div id="theirMaterialSlot" class="ga-zone"
     style="top:120px; left:10px;">
</div>

<!-- theirHealthSlot: mirrors my health in the top half -->
<div id="theirHealthSlot" class="ga-zone"
     style="top:10px; right:130px;">
</div>

<!-- theirMasterySlot: mirrors my mastery in the top half -->
<div id="theirMasterySlot" class="ga-zone"
     style="top:120px; left:120px;">
</div>

<!-- =================== GLOBAL ZONES =================== -->

<!-- EffectStackSlot: center of screen (effect queue display) -->
<div id="EffectStackSlot" class="ga-zone"
     style="top:45%; left:130px;">
</div>
