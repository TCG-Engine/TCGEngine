<?php
// IBH_010
// Cost 6 - Han Solo - Scruffy-Looking Nerf Herder - [Cunning,Heroism] - Power 4 - HP 6
// Text: Raid 2 (This unit gets +2/+0 while attacking.) / On Attack:The defender gets -2/-0 for this attack.

// IBH_010 / IBH_042 Han Solo — On Attack: defender -2/-0 (SWU_DEF_DEBUFF_2 applied synchronously in ExecuteSWUAttack).
$onAttackAbilities["IBH_010:0"] =
$onAttackAbilities["IBH_042:0"] = function($player, $mzID) { /* effect applied synchronously in ExecuteSWUAttack */ };
