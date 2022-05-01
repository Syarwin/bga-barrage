<?php
namespace BRG\Officers;

class Amir extends \BRG\Models\Officer
{
  public function __construct($company)
  {
    parent::__construct($company);
    $this->id = \XO_AMIR;
    $this->name = clienttranslate('Amir Zahir');
    $this->description = clienttranslate(
      'Whenever you place Water Drops on the Headstreams, you can decide to make them flow immediately (you need to decide for each single Water Drop). When you are supposed to place Water Drops that should flow immediately on the Headstreams, you can place them directly in one of your Dams with Enough Capacity to hold them (you need to decide for each single Water Drop).'
    );
  }

  public function isAvailable()
  {
    return false;
  }
}
