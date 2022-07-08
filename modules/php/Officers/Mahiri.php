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
    $this->name = clienttranslate('Mahiri Sekibo');
    $this->description = clienttranslate(
      'You have a personal special ability that you can activate placing 1 Engineer on the action space of this tile. If you use it a second time during the same round, you must also pay 3 Credits. When you activate it, you can copy another Executive Officer\'s special ability. Mahiri starts with 3 extra credits.'
    );
  }

  public function getStartingResources()
  {
    return [
      ENGINEER => 12,
      CREDIT => 9,
      EXCAVATOR => 6,
      MIXER => 4,
    ];
  }

  public function addActionSpacesUi(&$rows)
  {
    $rows[] = [
      'mahiri-1',
      [
        'i' => '<MAHIRI>',
        't' => clienttranslate(
          'You have a personal special ability that you can activate placing 1 Engineer on the action space of this tile. If you use it a second time during the same round, you must also pay 3 Credits. When you activate it, you can copy another Executive Officer\'s special ability.'
        ),
      ],
      'mahiri-2',
    ];
  }

  public function addActionSpaces(&$spaces)
  {
    $spaces[] = [
      'board' => BOARD_OFFICER,
      'cId' => $this->company->getId(),
      'uid' => BOARD_OFFICER . '-mahiri-1',
      'cost' => 0,
      'nEngineers' => 1,
      'flow' => [
        'action' => \SPECIAL_EFFECT,
        'args' => ['xoId' => \XO_MAHIRI, 'method' => 'copyPower'],
      ],
    ];
    $spaces[] = [
      'board' => BOARD_OFFICER,
      'cId' => $this->company->getId(),
      'uid' => BOARD_OFFICER . '-mahiri-2',
      'cost' => 3,
      'nEngineers' => 1,
      'flow' => [
        'action' => \SPECIAL_EFFECT,
        'args' => ['xoId' => \XO_MAHIRI, 'method' => 'copyPower'],
      ],
    ];
  }

  public function argsCopyPower()
  {
    $copy = [];
    foreach (Companies::getAll() as $cId => $company) {
      if ($company->isXO(\XO_MAHIRI)) {
        continue;
      }
      $copy[$company->getOfficer()->getId()] = [
        'id' => $company->getOfficer()->getId(),
        'officer' => $company->getOfficer(),
      ];
    }

    foreach (Globals::getMahiriAddXO() as $xId) {
      $copy[$xId] = [
        'id' => $xId,
        'officer' => Officers::getInstance($xId),
      ];
    }
    return ['description' => $this->getCopyPowerDescription(), 'method' => 'copyPower', 'power' => $copy];
  }

  public function isCopyPowerDoable()
  {
    return is_null($this->getCopiedOfficer());
  }

  public function getCopyPowerDescription()
  {
    return clienttranslate('Copy another executive officer power');
  }

  public function actCopyPower($powerId)
  {
    $args = $this->argsCopyPower()['power'];
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

  protected function getCopiedOfficer()
  {
    return Globals::getMahiriPower() == '' ? null : Officers::getInstance(Globals::getMahiriPower());
  }

  public function applyConstructCostModifier(&$costs, $slot)
  {
    $officer = $this->getCopiedOfficer();
    if (!is_null($officer)) {
      $officer->applyConstructCostModifier($costs, $slot);
    }
  }
}
