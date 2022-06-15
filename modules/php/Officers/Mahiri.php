<?php
namespace BRG\Officers;

use BRG\Managers\Companies;
use BRG\Core\Engine;
use BRG\Core\Globals;
use BRG\Core\Notifications;
use BRG\Managers\Officers;

class Mahiri extends \BRG\Models\Officer
{
  public function __construct($company)
  {
    parent::__construct($company);
    $this->id = \XO_MAHIRI;
    $this->name = clienttranslate('Mahiri Sekiso');
    $this->description = clienttranslate(
      'You have a personal special ability that you can activate placing 1 Engineer on the action space of this tile. If you use it a second time during the same roun, you must also pay 3 Credits. When you activate it, you can copy another Executive Officer\'s special ability.'
    );
  }

  public function argsCopyPower()
  {
    $copy = [];
    foreach (Companies::getAll() as $cId => $company) {
      if ($company->isXO(\XO_MAHIRI)) {
        continue;
      }
      // throw new \feException(print_r($company->getOfficer()->getId()));
      $copy[$company->getOfficer()->getId()] = [
        'id' => $company->getOfficer()->getId(),
        'officer' => $company->getOfficer(),
      ];
    }
    return ['description' => $this->getCopyPowerDescription(), 'method' => 'copyPower', 'power' => $copy];
  }

  public function getCopyPowerDescription()
  {
    return clienttranslate('Copy another executive officer power');
  }

  public function actCopyPower($powerId)
  {
    $args = $this->argsCopyPower()['power'];
    // $ids = array_map(function ($p) {
    //   return $p['id'];
    // }, $args);
    // throw new \feException($powerId);
    // if (!in_array($powerId, $ids)) {
    if (!isset($args[$powerId])) {
      throw new \feException('This XO is not available to be copied (Mahiri). Should not happen');
    }

    Globals::setMahiriPower($powerId);
    Notifications::message(clienttranslate('${company_name} copys power of ${XO} with Mahiri\'s power'), [
      'company' => Companies::getActive(),
      'XO' => $args[$powerId]['officer']->getName(),
      'i18n' => ['XO'],
    ]);

    Engine::insertAsChild([
      'action' => PLACE_ENGINEER,
      'cId' => Companies::getActive()->getId(),
    ]);

    Engine::resolveAction([$powerId]);
    Engine::proceed();
  }

  public function getCostModifier($slot, $machine, $n)
  {
    if (Globals::getMahiriPower() != '') {
      return Officers::getInstance(Globals::getMahiriPower())->getCostModifier($slot, $machine, $n);
    }
    return parent::getCostModifier($slot, $machine, $n);
  }

  public function getUnitsModifier($slot, $machine, $n)
  {
    if (Globals::getMahiriPower() != '') {
      return Officers::getInstance(Globals::getMahiriPower())->getUnitsModifier($slot, $machine, $n);
    }
    return parent::getUnitsModifier($slot, $machine, $n);
  }

  public function getStartCredit()
  {
    return 9;
  }
}
