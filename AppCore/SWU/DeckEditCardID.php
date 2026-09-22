<?php
// Deck edit endpoints accept either SET_NNN card IDs or the numeric FFG UIDs
// emitted by LoadDeck's default JSON format. Deck files may still contain
// either representation, so normalize both sides before comparing.
require_once __DIR__ . '/../../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';
require_once __DIR__ . '/Overrides.php';

function SWUDeckEditCardID($cardID): string
{
    $cardID = (string)$cardID;
    $setID = CardIDLookup($cardID);
    return CardIDOverride($setID !== null && $setID !== '' ? $setID : $cardID);
}
