<?php

function ValidateDeckAddition($cardID) {
    $deck = &GetMainDeck(1);
    $reserve = &GetReserveDeck(1);
    $commander = &GetCommander(1);
    $numCard = 0;
    $numCores = 0;
    $numMercs = 0;
    $numUnits = 0;
    $nonMercUnits = 0;
    $numReserve = 0;
    $maxCores = 0;
    $maxMercs = 0;
    $numHolidays = 0;
    $hasGrimHoliday = false;
    $isHoliday = false;
    $numBattlefield = 0;
    $numWeapon = 0;
    $numArmor = 0;
    $numFeat = 0;
    $addCardName = CardName($cardID);
    if(str_contains($addCardName, "Holiday")) {
        ++$numHolidays;
        $isHoliday = true;
    }
    foreach($deck as $card) {
        $faction = CardFaction($card->CardID);
        $isMercenary = str_contains($faction, "Mercenary");
        if($card->CardID == $cardID && !$card->Removed()) {
            $numCard++;
        }
        $thisType = CardType($card->CardID);
        if($thisType == "Unit") ++$numUnits;
        else if($thisType == "Core") ++$numCores;
        if($isMercenary) {
            ++$numMercs;
        } else if($thisType == "Unit") {
            ++$nonMercUnits;
        }
        $name = CardName($card->CardID);
        if(str_contains($name, "Holiday")) {
            ++$numHolidays;
        }
        if($card->CardID == "SM-AW-248") {
            $hasGrimHoliday = true;
        }
    }
    foreach($reserve as $card) {
        if($card->CardID == $cardID && !$card->Removed()) {
            $numCard++;
        }
        ++$numReserve;
        $thisSubType = CardSubType($card->CardID);
        if(str_contains($thisSubType, "Battlefield")) ++$numBattlefield;
        else if(str_contains($thisSubType, "Weapon")) ++$numWeapon;
        else if(str_contains($thisSubType, "Armor")) ++$numArmor;
        else if(str_contains($thisSubType, "Feat")) ++$numFeat;
    }
    foreach($commander as $card) {
        if($card->CardID == $cardID && !$card->Removed()) {
            $numCard++;
        }
        $maxCores = CardCoreEnergy($card->CardID);
        $maxMercs = intval(CardMercenaryLimit($card->CardID));
    }
    if($hasGrimHoliday && $numHolidays >= 3) {
        ++$maxMercs;
        ++$nonMercUnits;
    }
    $rarity = CardRarity($cardID);
    if($numCard >= 3 && ($rarity == "Common" || $rarity = "Uncommon" || $rarity == "Rare")) {
        return false;
    } else if($numCard >= 2 && $rarity == "Epic") {
        return false;
    } else if($numCard >= 1 && $rarity == "Legendary") {
        return $numCard < 1;
    }
    $type = CardType($cardID);
    if($type == "Reserve") {
        $thisSubType = CardSubType($cardID);
        if(str_contains($thisSubType, "Battlefield") && $numBattlefield >= 2) return false;
        else if(str_contains($thisSubType, "Weapon") && $numWeapon >= 2) return false;
        else if(str_contains($thisSubType, "Armor") && $numArmor >= 2) return false;
        else if(str_contains($thisSubType, "Feat") && $numFeat >= 2) return false;
    }
    else if($type != "Commander") {
        if($type == "Unit" && count($deck) >= 50 - $maxCores + $numCores) {
            return false;
        } else if($type == "Core" && count($deck) >= 50 - 25 + $numUnits) {
            return false;
        } else if($type != "Core" && $type != "Unit" && count($deck) >= 50 - $maxCores + $numCores - 25 + $numUnits) {
            return false;
        }
        if(count($deck) >= 50) {
            return false;
        }
    }
    $faction = CardFaction($cardID);
    $isMercenary = str_contains($faction, "Mercenary");
    if($isMercenary)
    {
        if($isHoliday && $hasGrimHoliday && $numHolidays >= 3 && $nonMercUnits > 25) {
            return false;
        }
        if($cardID == "SM-AW-248" && $numHolidays >= 3) {
            //Grim is not validated as a merc if you have 3+ holidays
        } else if($numMercs >= $maxMercs) {
            return false;
        } else {
            return true;
        }
    }
    if($type == "Reserve" && $numReserve >= 8) {
        return false;
    } else if($type == "Unit" && $nonMercUnits >= 25) {
        return false;
    } else if($type == "Core" && $numCores >= $maxCores) {
        return false;
    }
    return true;
}

function ValidateCommanderAddition($cardID) {
    global $gameName;
    $commander = &GetCommander(1);
    $commander = [];

    $commanderSets = [
        "SM-SD-01-001" => ["SM-SD-01-001", "SM-SD-01-002", "SM-SD-01-003"],
        "SM-SD-02-001" => ["SM-SD-02-001", "SM-SD-02-002", "SM-SD-02-003"],
        "SM-AW-061" => ["SM-AW-061", "SM-AW-062", "SM-AW-063"],
        "SM-AW-121" => ["SM-AW-121", "SM-AW-122", "SM-AW-123"],
        "SM-AW-181" => ["SM-AW-181", "SM-AW-182", "SM-AW-183"],
        "SM-SA-01-001" => ["SM-SA-01-001"]
    ];

    if (array_key_exists($cardID, $commanderSets)) {
        foreach ($commanderSets[$cardID] as $commanderID) {
            $commander[] = new Commander($commanderID);
        }
        SetAssetKeyIdentifier(1, $gameName, 1, $cardID);
    }

    return false;
}

?>