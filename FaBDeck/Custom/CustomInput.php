<?php
function CustomWidgetInput($playerID, $actionCard, $action) {
    $parts=explode('-',$actionCard); $zone=$parts[0]??''; $card=GetZoneObject($actionCard); if($card===null)return;
    if($zone==='myCards') {
        if($action==='>') MZAddZone($playerID,'myMainDeck',$card->CardID);
        elseif($action==='>>>') for($i=0;$i<3;++$i) MZAddZone($playerID,'myMainDeck',$card->CardID);
        elseif($action==='V') MZAddZone($playerID,'myInventory',$card->CardID);
        return;
    }
    if(!in_array($zone,['myMainDeck','myInventory'],true))return;
    if($action==='<')$card->Remove();
    elseif($action==='<<<'){ $id=$card->CardID; for($i=0;$i<3;++$i){$match=SearchZoneForCard($actionCard,$id,1);if($match)$match->Remove();} }
    elseif($action==='+')MZAddZone($playerID,$zone,$card->CardID);
    elseif($action==='V'&&$zone==='myMainDeck'){ $id=$card->CardID;$card->Remove();MZAddZone($playerID,'myInventory',$id); }
    elseif($action==='^'&&$zone==='myInventory'){ $id=$card->CardID;$card->Remove();MZAddZone($playerID,'myMainDeck',$id); }
}
?>
