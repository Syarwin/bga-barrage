<?php
namespace BRG\Actions;
use BRG\Managers\Meeples;
use BRG\Managers\Companies;
use BRG\Managers\Players;
use BRG\Core\Notifications;
use BRG\Core\Stats;
use BRG\Core\Engine;
use BRG\Helpers\Utils;
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
    $company = Companies::getActive();
    return [
      'i18n' => ['modifier'],
      'systems' => Map::getProductionSystems($company, $bonus),
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
    $tDroplets = []; // Avoid issue with two notifs in a row modifying same object
    foreach ($droplets as $droplet) {
      $droplet['location'] = $system['conduitSpaceId'];
      $tDroplets[] = $droplet;
    }
    Notifications::moveDroplets($tDroplets);
    // Move droplets to powerhouses
    foreach ($droplets as &$drop) {
      $drop['location'] = $system['powerhouseSpaceId'];
    }
    Notifications::moveDroplets($droplets);

    // Produce energy
    $company = Companies::getActive();
    Notifications::produce($company, $system['powerhouseSpaceId'], $production, $nDroplets);
    $company->incEnergy($production, true);

    // Let water flows
    foreach ($droplets as $droplet) {
      Map::flow($droplet);
    }

    // Pay X credit to other player if needed
    if ($system['conduitOwnerId'] != $company->getId()) {
      $opponent = Companies::get($system['conduitOwnerId']);
      Engine::insertAsChild([
        'action' => PAY,
        'cId' => $company->getId(),
        'args' => [
          'nb' => $nDroplets,
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
    Engine::insertAsChild(['action' => \FULFILL_CONTRACT, 'args' => [ENERGY => $production]]);
    $this->resolveAction(['droplets' => $droplets]);
  }
}
