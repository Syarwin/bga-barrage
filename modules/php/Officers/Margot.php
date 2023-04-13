<?php
namespace BRG\Officers;
use BRG\Core\Globals;
use BRG\Core\Engine;
use BRG\Managers\Buildings;

class Margot extends \BRG\Models\Officer
{
  public function __construct($company)
  {
    parent::__construct($company);
    $this->id = \XO_MARGOT;
    $this->name = clienttranslate('Margot Fouche');
    $this->description = clienttranslate(
      "You have a personal action space on this Executive Officer tile. When it's your turn during the Actions Phase, you can place 1 Engineer here to perform the special action of a Private Building that you have activated. You still can perform the same Private Building action using its normal action spaces. Therefore, this Executive Officer allows you to perform the same Private Building special action twice in the same round."
    );
  }

  public function isAvailable()
  {
    return Globals::isLWP();
  }

  public function addActionSpacesUi(&$rows)
  {
    $rows[] = [
      'margot-1',
      [
        'i' => '',
        't' => clienttranslate(
          'You have a personal action space on this Executive Officer tile. When it’s your turn during the Actions Phase, you can place 1 Engineer here to perform the special action of a Private Building that you have activated. You still can perform the same Private Building action using its normal action spaces. Therefore, this Executive Officer allows you to perform the same Private Building special action twice in the same round.'
        ),
      ],
    ];
  }

  public function addActionSpaces(&$spaces)
  {
    $spaces[] = [
      'board' => BOARD_OFFICER,
      'cId' => $this->company->getId(),
      'uid' => BOARD_OFFICER . '-margot-1',
      'cost' => 0,
      'nEngineers' => 1,
      'flow' => [
        'action' => \SPECIAL_EFFECT,
        'args' => ['xoId' => XO_MARGOT, 'method' => 'useBuilding'],
      ],
    ];
  }

  public function isUseBuildingDoable()
  {
    $builtBuildingIds = is_null($this->company) ? [] : $this->company->getBuiltBuildingIds();
    return count($builtBuildingIds) > 0;
  }

  public function getUseBuildingDescription()
  {
    return clienttranslate('Use an activated Private Building (Margot)');
  }

  public function argsUseBuilding()
  {
    $builtBuildingIds = $this->company->getBuiltBuildingIds();
    return [
      'description' => $this->getUseBuildingDescription(),
      'method' => 'useBuilding',
      'power' => Buildings::getMany($builtBuildingIds),
    ];
  }

  public function actUseBuilding($buildingId)
  {
    $args = $this->argsUseBuilding()['power'];
    if (!isset($args[$buildingId])) {
      throw new \feException('You cant use this building. Should not happen');
    }

    $building = Buildings::getSingle($buildingId);
    Engine::insertAsChild($building->getFlow());
    Engine::resolveAction([$buildingId]);
    Engine::proceed();
  }
}
