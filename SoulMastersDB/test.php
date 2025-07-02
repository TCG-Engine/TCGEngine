<?php
  include_once './GamestateParser.php';
  include_once './ZoneAccessors.php';
  include_once './ZoneClasses.php';
  include_once '../Core/CoreZoneModifiers.php';
  include_once './GeneratedCode/GeneratedCardDictionaries.php';

  InitializeGamestate();

  //Load the deck
  $decklist = "UmFwdW56ZWxfR2lmdGVkIHdpdGggSGVhbGluZyQ0fEFuZCBUaGVuIEFsb25nIENhbWUgWmV1cyQzfE1yLiBTbWVlX0J1bWJsaW5nIE1hdGUkM3xBbGFuLWEtRGFsZV9Sb2NraW4nIFJvb3N0ZXIkMnxQZXRlX0dhbWVzIFJlZmVyZWUkMnxQcmluY2UgTmF2ZWVuX1VrdWxlbGUgUGxheWVyJDJ8QSBXaG9sZSBOZXcgV29ybGQkNHxUaGUgQmFyZSBOZWNlc3NpdGllcyQyfEFyaWVsX1NwZWN0YWN1bGFyIFNpbmdlciQ0fFBlcmRpdGFfRGV2b3RlZCBNb3RoZXIkMnxEYWlzeSBEdWNrX0RvbmFsZCdzIERhdGUkM3xSb2JpbiBIb29kX0JlbG92ZWQgT3V0bGF3JDR8R3JhYiBZb3VyIFN3b3JkJDF8SSBGaW5kICdFbSwgSSBGbGF0dGVuICdFbSQxfENpbmRlcmVsbGFfQmFsbHJvb20gU2Vuc2F0aW9uJDR8U3RyZW5ndGggb2YgYSBSYWdpbmcgRmlyZSQ0fFJvYmluIEhvb2RfQ2hhbXBpb24gb2YgU2hlcndvb2QkNHxXb3JsZCdzIEdyZWF0ZXN0IENyaW1pbmFsIE1pbmQkMnxMYXdyZW5jZV9KZWFsb3VzIE1hbnNlcnZhbnQkMnxMZXQgdGhlIFN0b3JtIFJhZ2UgT24kM3xVcnN1bGFfVmFuZXNzYSQyfENpbmRlcmVsbGFfU3RvdXRoZWFydGVkJDJ8";

  $yourJsonObject = array(
    'decklistAsPbString' => $decklist
  );

  $formData = json_encode($yourJsonObject);

  $options = array(
    'http' => array(
      'header'  => "Content-type: application/json\r\n",
      'method'  => 'POST',
      'content' => $formData,
      'FormData' => $formData,
    ),
  );

  $context  = stream_context_create($options);
  $result = file_get_contents('https://20lore.pro/api/getDecklistFromPbString.php', false, $context);

  if ($result === FALSE) {
    // Handle error
  } else {
    echo($result);
    // Process the result
    // $result contains the response from the API
  }

  //array_push($p1Cards, new Cards());
  //array_push($p2Cards, new Cards());
  for ($i = 1; $i <= 30; $i++) {
    $cardId = sprintf("SM-SD-01-%03d", $i);
    echo(CardName($cardId) . " " . CardType($cardId) . "<BR>");
    if(CardType($cardId) == "C") {
      array_push($p1Commanders, new Commanders($cardId));
    } else {
      array_push($p1Cards, new Cards($cardId));
    }
  }
  //Shuffle($p1MainDeck);
  //Draw(1, amount:7);

  /*
  for ($i = 1; $i <= 30; $i++) {
    $cardId = sprintf("SM-SD-01-%03d", $i);
    array_push($p2Deck, new Deck($cardId));
  }
  Shuffle($p2Deck);
  Draw(2, amount:7);
*/
  
  $gameName = 1;

  WriteGamestate();

?>