<?php
namespace BRG\Actions;
use BRG\Managers\Meeples;
use BRG\Managers\Companies;
use BRG\Managers\Players;
use BRG\Core\Notifications;
use BRG\Core\Stats;
use BRG\Core\Engine;
use BRG\Helpers\Utils;
use BRG\Helpers\Collection;
use BRG\Map;

class Produce extends \BRG\Models\Action
{
  public function getState()
  {
    return ST_PRODUCE;
  }

  public function isDoable($company, $ignoreResources = false)
  {
    $bonus = $this->getCtxArgs()['bonus'] ?? 0;
    return !empty(Map::getProductionSystems($company, $bonus));
  }

  public function argsProduce()
  {
    $args = $this->getCtxArgs();
    $bonus = $args['bonus'] ?? 0;
    $germanPower = $args['germanPower'] ?? false;
    $company = Companies::getActive();
    $bonus += $company->getProductionBonus();

    if ($germanPower) {
      $bonus = 0;
    }

    return [
      'i18n' => ['modifier'],
      'systems' => Map::getProductionSystems($company, $bonus, $args['constraints'] ?? null),
      'modifier' =>
        $bonus == 0
          ? ''
          : [
            'log' => $bonus > 0 ? clienttranslate('(${n} bonus)') : clienttranslate('(${n} malus)'),
            'args' => [
              'n' => $bonus > 0 ? '+' . $bonus : $bonus,
            ],
          ],
    ];
  }

  public function actProduce($systemId, $nDroplets)
  {
    // Sanity checks
    self::checkAction('actProduce');
    $systems = $this->argsProduce()['systems'];
    $system = $systems[$systemId] ?? null;
    $args = $this->getCtxArgs();

    if (is_null($system)) {
      throw new \BgaVisibleSystemException('Combinaison not possible. Should not happen');
    }
    $production = $system['productions'][$nDroplets] ?? null;
    if (is_null($production)) {
      throw new \BgaVisibleSystemException('Invalid production. Should not happen');
    }
    $droplets = Map::getDropletsInBasin($system['basin'])->limit($nDroplets);
    if ($droplets->count() < $nDroplets) {
      throw new \BgaVisibleSystemException('Invalid number of droplets. Should not happen');
    }

    // Move droplets to conduit
    $tDroplets = new Collection([]); // Avoid issue with two notifs in a row modifying same object
    foreach ($droplets as $droplet) {
      $droplet['location'] = $system['conduitSpaceId'];
      $droplet['path'] = [$system['conduitSpaceId']];
      $tDroplets[] = $droplet;
    }
    Notifications::moveDroplets($tDroplets);
    // Move droplets to powerhouses
    foreach ($droplets as &$drop) {
      $drop['location'] = $system['powerhouseSpaceId'];
      $drop['path'] = [$system['powerhouseSpaceId']];
    }
    Notifications::moveDroplets($droplets);

    // Produce energy
    $company = Companies::getActive();
    Notifications::produce($company, $system['powerhouseSpaceId'], $production, $nDroplets);
    $company->incEnergy($production, true);

    // Let water flows
    Map::flowDroplets($droplets);

    // Pay X credit to other player if needed
    if ($system['conduitOwnerId'] != $company->getId()) {
      $opponent = Companies::get($system['conduitOwnerId']);
      Engine::insertAsChild([
        'action' => PAY,
        'cId' => $company->getId(),
        'args' => [
          'nb' => 1,
          'costs' => Utils::formatCost([CREDIT => $nDroplets]),
          'source' => clienttranslate('use of conduit'),
          'to' => $opponent->getId(),
        ],
      ]);
      // gain x VP
      $opponent->incScore($nDroplets);
      Notifications::score($opponent, $nDroplets, clienttranslate('for use of conduit'));
    }

    // Contract fullfilment?
    Engine::insertAsChild([
      'action' => \FULFILL_CONTRACT,
      'optional' => true,
      'args' => [ENERGY => $production],
    ]);

    // Germany power
    if ($company->getId() == \COMPANY_GERMANY && $company->productionPowerEnabled() && !isset($args['germanPower'])) {
      Engine::insertAsChild([
        'action' => \PRODUCE,
        'optional' => true,
        'args' => ['germanPower' => true, 'constraints' => $system['powerhouseSpaceId']],
      ]);
    }
    // Italy power
    elseif ($company->getId() == \COMPANY_ITALY && $company->productionPowerEnabled()) {
      Gain::gainResources($company, [ENERGY => 3], null, clienttranslate('nation\'s power'));
    }

    $this->resolveAction(['droplets' => $droplets]);
  }
}
