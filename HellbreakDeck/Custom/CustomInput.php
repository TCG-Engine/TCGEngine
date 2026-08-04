<?php

function CustomWidgetInput($playerID, $actionCard, $action) {
    $parts = explode('-', $actionCard);
    $zone = $parts[0] ?? '';
    $card = GetZoneObject($actionCard);
    if ($card === null) return;

    if ($zone === 'myCards') {
        if ($action === '>') MZAddZone($playerID, 'myMainDeck', $card->CardID);
        elseif ($action === '>>>') {
            for ($i = 0; $i < 4; ++$i) MZAddZone($playerID, 'myMainDeck', $card->CardID);
        } elseif ($action === 'V') MZAddZone($playerID, 'mySideboard', $card->CardID);
        return;
    }

    if ($zone !== 'myMainDeck' && $zone !== 'mySideboard') return;
    if ($action === '<') $card->Remove();
    elseif ($action === '<<<') {
        $cardID = $card->CardID;
        for ($i = 0; $i < 4; ++$i) {
            $match = SearchZoneForCard($actionCard, $cardID, 1);
            if ($match !== null) $match->Remove();
        }
    } elseif ($action === '+') MZAddZone($playerID, $zone, $card->CardID);
    elseif ($action === 'V' && $zone === 'myMainDeck') {
        $card->Remove();
        MZAddZone($playerID, 'mySideboard', $card->CardID);
    } elseif ($action === '^' && $zone === 'mySideboard') {
        $card->Remove();
        MZAddZone($playerID, 'myMainDeck', $card->CardID);
    }
}

?>
