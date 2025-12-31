<?php

enum ContentCreators : string
{
  case InstantSpeed = "0";
  case ManSant = "731";

  public function SessionName(): string
  {
    switch($this->value)
    {
      case "0": return "isInstantSpeedPatron";
      case "731": return "isManSantPatron";
      default: return "";
    }
  }

  public function PatreonLink(): string
  {
    switch($this->value)
    {
      case "0": return "https://www.patreon.com/instantspeedpod";
      case "731": return "https://www.patreon.com/ManSant";
      default: return "";
    }
  }

  public function ChannelLink(): string
  {
    switch($this->value)
    {
      case "0": return "https://www.youtube.com/playlist?list=PLIo1KFShm1L3e91QrlPG6ZdwfmqKk0NIP";
      case "731": return "https://www.youtube.com/@ManSantFaB";
      default: return "";
    }
  }

  public function BannerURL(): string
  {
    switch($this->value)
    {
      case "0": return "./Assets/patreon-php-master/assets/ContentCreatorImages/InstantSpeedBanner.webp";
      default: return "";
    }
  }

  public function HeroOverlayURL($heroID): string
  {
    switch($this->value)
    {
      case "0": //WatchFlake
        if(CardClass($heroID) == "GUARDIAN") return "./Assets/patreon-php-master/assets/ContentCreatorImages/Matt_anathos_overlay.webp";
        return "./Assets/patreon-php-master/assets/ContentCreatorImages/flakeOverlay.webp";
      case "731":
        return "./Assets/patreon-php-master/assets/ContentCreatorImages/ManSantLevia.webp";
      default: return "";
    }
  }

  public function NameColor(): string
  {
    switch($this->value)
    {
      case "0": return "rgb(2,190,253)";
      case "731": return "rgb(255,53,42)";
      default: return "";
    }
  }
}

enum PatreonCampaign : string
{
  case L8Night = "99999999";
  case KTOD = "11987758";
  case OotTheMonk = "12163989";
  case RebelResource = "12716027";
  case StubbsHub = "13088942";
  case StarWarzDad = "12636483";

  public function SessionID(): string
  {
    switch($this->value)
    {
      case "99999999": return "isL8Night";
      case "12163989": return "isPatron";
      case "11987758": return "isKTODPatron";
      case "12716027": return "isRebelResourcePatron";
      case "13088942": return "isStubbsHubPatron";
      case "12636483": return "isStarWarzDadPatron";
      default: return "";
    }
  }

  public function CampaignName(): string
  {
    switch($this->value)
    {
      case "99999999": return "L8 Night Gaming";
      case "12163989": return "OotTheMonk";
      case "11987758": return "KTOD";
      case "12716027": return "Rebel Resource";
      case "13088942": return "Stubbs Hub";
      case "12636483": return "Force Fam";
      default: return "";
    }
  }

  public function IsTeamMember($userName): string
  {
    switch($this->value)
    {
      case "99999999": return ($userName == "LoopeeL8NG");
      case "12163989": return ($userName == "OotTheMonk");
      case "11987758": return ($userName == "Chrono" || $userName == "BobbySapphire" || $userName == "Reflex" || $userName == "allstarz97" || $userName == "wooooo" || $userName == "Brunas" || $userName == "Matty" || $userName == "KTODMATTY");
      case "12716027": return ($userName == "RebelResource");
      case "13088942": return ($userName == "rodneystubbs");
      case "12636483": return ($userName == "StarWarzDad");
      default: return "";
    }
  }

  public function CardBacks(): string
  {
    switch($this->value)
    {
      //case "11987758": return "2";
      default: return "";
    }
  }
}


?>
