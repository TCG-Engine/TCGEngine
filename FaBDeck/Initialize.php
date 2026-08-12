<?php
include_once './FaBSim/GeneratedCode/GeneratedCardDictionaries.php';
$p1Heroes=[]; $p1WeaponsCatalog=[]; $p1EquipmentCatalog=[]; $p1Cards=[];
foreach (GetAllCardIds() as $cardID) {
  $types = CardTypes($cardID); if (!is_array($types)) $types=[];
  if (in_array('Hero',$types,true)) $p1Heroes[] = new Heroes($cardID);
  elseif (in_array('Weapon',$types,true)) $p1WeaponsCatalog[] = new WeaponsCatalog($cardID);
  elseif (in_array('Equipment',$types,true) && !in_array('Action',$types,true) && !in_array('Instant',$types,true)) $p1EquipmentCatalog[] = new EquipmentCatalog($cardID);
  elseif (!in_array('Token',$types,true)) $p1Cards[] = new Cards($cardID);
}
WriteGamestate('./FaBDeck/');
?>
