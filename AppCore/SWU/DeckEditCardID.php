<?php
// Deck edit endpoints accept either SET_NNN card IDs or the numeric FFG UIDs
// emitted by LoadDeck's default JSON format. Deck files may still contain
// either representation, so normalize both sides before comparing.
require_once __DIR__ . '/../../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';
require_once __DIR__ . '/Overrides.php';
require_once __DIR__ . '/../../SWUDeck/Custom/DeckValidation.php'; // SWUDeckMaxCopies

function SWUDeckEditCardID($cardID): string
{
    $cardID = (string)$cardID;
    $setID = ctype_digit($cardID) ? CardIDLookup($cardID) : null;
    return CardIDOverride($setID !== null && $setID !== '' ? $setID : $cardID);
}

function SWUDeckEditCopyCount(array $mainDeck, array $sideboard, string $cardID): int
{
    $count = 0;
    foreach ([$mainDeck, $sideboard] as $zone) {
        foreach ($zone as $card) {
            $storedID = isset($card->CardID) ? $card->CardID : trim($card->Serialize());
            if (SWUDeckEditCardID($storedID) === $cardID) $count++;
        }
    }
    return $count;
}
