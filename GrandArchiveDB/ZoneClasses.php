<?php
class Commander {
  public $CardID;
  public $removed;
  function __construct($line) {
    $arr = explode(" ", $line);
    $this->CardID = (count($arr) > 0 ? $arr[0] : "");
  }
  function Serialize() {
    $rv = "";
    $rv .= $this->CardID;
    return $rv;
  }
  function Remove($trigger="") {
    $this->removed = true;
  }
  function Removed() {
    return $this->removed;
  }
  function DragMode() {
    return "Normal";
  }
  static function GetMacros() {
    return [];
  }
}

class ReserveDeck {
  public $CardID;
  public $removed;
  function __construct($line) {
    $arr = explode(" ", $line);
    $this->CardID = (count($arr) > 0 ? $arr[0] : "");
  }
  function Serialize() {
    $rv = "";
    $rv .= $this->CardID;
    return $rv;
  }
  function Remove($trigger="") {
    $this->removed = true;
  }
  function Removed() {
    return $this->removed;
  }
  function DragMode() {
    return "Normal";
  }
  static function GetMacros() {
    return [];
  }
}

class MainDeck {
  public $CardID;
  public $removed;
  function __construct($line) {
    $arr = explode(" ", $line);
    $this->CardID = (count($arr) > 0 ? $arr[0] : "");
  }
  function Serialize() {
    $rv = "";
    $rv .= $this->CardID;
    return $rv;
  }
  function Remove($trigger="") {
    $this->removed = true;
  }
  function Removed() {
    return $this->removed;
  }
  function DragMode() {
    return "Normal";
  }
  static function GetMacros() {
    return [];
  }
}

class CardPane {
  public $Value;
  public $removed;
  function __construct($line) {
    $arr = explode(" ", $line);
    $this->Value = (count($arr) > 0 ? $arr[0] : "");
  }
  function Serialize() {
    $rv = "";
    $rv .= $this->Value;
    return $rv;
  }
  function Remove($trigger="") {
    $this->removed = true;
  }
  function Removed() {
    return $this->removed;
  }
  function DragMode() {
    return "Normal";
  }
  static function GetMacros() {
    return [];
  }
}

class Commanders {
  public $CardID;
  public $removed;
  function __construct($line) {
    $arr = explode(" ", $line);
    $this->CardID = (count($arr) > 0 ? $arr[0] : "");
  }
  function Serialize() {
    $rv = "";
    $rv .= $this->CardID;
    return $rv;
  }
  function Remove($trigger="") {
    $this->removed = true;
  }
  function Removed() {
    return $this->removed;
  }
  function DragMode() {
    return "Normal";
  }
  static function GetMacros() {
    return [];
  }
}

class Reserves {
  public $CardID;
  public $removed;
  function __construct($line) {
    $arr = explode(" ", $line);
    $this->CardID = (count($arr) > 0 ? $arr[0] : "");
  }
  function Serialize() {
    $rv = "";
    $rv .= $this->CardID;
    return $rv;
  }
  function Remove($trigger="") {
    $this->removed = true;
  }
  function Removed() {
    return $this->removed;
  }
  function DragMode() {
    return "Clone";
  }
  static function GetMacros() {
    return [];
  }
}

class Cards {
  public $CardID;
  public $removed;
  function __construct($line) {
    $arr = explode(" ", $line);
    $this->CardID = (count($arr) > 0 ? $arr[0] : "");
  }
  function Serialize() {
    $rv = "";
    $rv .= $this->CardID;
    return $rv;
  }
  function Remove($trigger="") {
    $this->removed = true;
  }
  function Removed() {
    return $this->removed;
  }
  function DragMode() {
    return "Clone";
  }
  static function GetMacros() {
    return [];
  }
}

class NumReserve {
  public $CardID;
  public $removed;
  function __construct($line) {
    $arr = explode(" ", $line);
    $this->CardID = (count($arr) > 0 ? $arr[0] : "");
  }
  function Serialize() {
    $rv = "";
    $rv .= $this->CardID;
    return $rv;
  }
  function Remove($trigger="") {
    $this->removed = true;
  }
  function Removed() {
    return $this->removed;
  }
  function DragMode() {
    return "Normal";
  }
  static function GetMacros() {
    return [];
  }
}

class CountsDisplay {
  public $CardID;
  public $removed;
  function __construct($line) {
    $arr = explode(" ", $line);
    $this->CardID = (count($arr) > 0 ? $arr[0] : "");
  }
  function Serialize() {
    $rv = "";
    $rv .= $this->CardID;
    return $rv;
  }
  function Remove($trigger="") {
    $this->removed = true;
  }
  function Removed() {
    return $this->removed;
  }
  function DragMode() {
    return "Normal";
  }
  static function GetMacros() {
    return [];
  }
}

class Sort {
  public $Value;
  public $removed;
  function __construct($line) {
    $arr = explode(" ", $line);
    $this->Value = (count($arr) > 0 ? $arr[0] : "");
  }
  function Serialize() {
    $rv = "";
    $rv .= $this->Value;
    return $rv;
  }
  function Remove($trigger="") {
    $this->removed = true;
  }
  function Removed() {
    return $this->removed;
  }
  function DragMode() {
    return "Normal";
  }
  static function GetMacros() {
    return [];
  }
}

class CardNotes {
  public $CardID;
  public $Notes;
  public $removed;
  function __construct($line) {
    $arr = explode(" ", $line);
    $this->CardID = (count($arr) > 0 ? $arr[0] : "");
    $this->Notes = (count($arr) > 1 ? $arr[1] : "");
  }
  function Serialize() {
    $rv = "";
    $rv .= $this->CardID;
    $rv .= " ";
    $rv .= $this->Notes;
    return $rv;
  }
  function Remove($trigger="") {
    $this->removed = true;
  }
  function Removed() {
    return $this->removed;
  }
  function DragMode() {
    return "Normal";
  }
  static function GetMacros() {
    return [];
  }
}

class Versions {
  public $Version;
  public $removed;
  function __construct($line) {
    $arr = explode(" ", $line);
    $this->Version = (count($arr) > 0 ? $arr[0] : "");
  }
  function Serialize() {
    $rv = "";
    $rv .= $this->Version;
    return $rv;
  }
  function Remove($trigger="") {
    $this->removed = true;
  }
  function Removed() {
    return $this->removed;
  }
  function DragMode() {
    return "Normal";
  }
  static function GetMacros() {
    return [];
  }
  static function GetSerializedZones() {
    $rv = "";
    $zone = &GetZone("myCommander");
    for($i=0; $i<count($zone); ++$i) {
      if($i > 0) $rv .= "<v1>";
      $rv .= $zone[$i]->Serialize();
    }
    $rv .= "<v0>";
    $zone = &GetZone("myReserveDeck");
    for($i=0; $i<count($zone); ++$i) {
      if($i > 0) $rv .= "<v1>";
      $rv .= $zone[$i]->Serialize();
    }
    $rv .= "<v0>";
    $zone = &GetZone("myMainDeck");
    for($i=0; $i<count($zone); ++$i) {
      if($i > 0) $rv .= "<v1>";
      $rv .= $zone[$i]->Serialize();
    }
    return $rv;
  }
}

?>