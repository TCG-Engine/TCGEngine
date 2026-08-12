<?php

function FaBDeckTypes($cardID) { $types=CardTypes($cardID); return is_array($types)?$types:[]; }
function ValidateHeroAddition($cardID) {
    if (!in_array('Hero', FaBDeckTypes($cardID), true)) return false;
    global $gameName; SetAssetKeyIdentifier(1, $gameName, 1, $cardID); return true;
}
function ValidateWeaponAddition($cardID) { return in_array('Weapon', FaBDeckTypes($cardID), true); }
function ValidateEquipmentAddition($cardID) { return in_array('Equipment', FaBDeckTypes($cardID), true); }
function ValidateMainDeckAddition($cardID) {
    $types=FaBDeckTypes($cardID);
    return !in_array('Hero',$types,true) && !in_array('Weapon',$types,true) && !in_array('Equipment',$types,true) && !in_array('Token',$types,true);
}

?>
