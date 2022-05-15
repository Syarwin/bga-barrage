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

class Product extends \BRG\Models\Action
{
  public function getState()
  {
    return ST_PRODUCT;
  }

  public function argsProduct()
  {
    $args = Engine::getNextUnresolved()->getArgs() ?? [];
    return [
      'capacity' => Map::productionCapacity(Companies::getActive()->getId()),
      'modifier' => isset($args['bonus']) ? '+' . $args['bonus'] : '',
    ];
  }

  public function isDoable($company, $ignoreResources = false)
  {
    return !empty(Map::productionCapacity($company->getId()));
  }

  public function actProduct($conduit, $basin, $droplets)
  {
    // sanity checks
    $args = $this->argsProduct()['capacity'];
    $ctxArgs = Engine::getNextUnresolved()->getArgs();
    $company = Companies::getActive();

    $filter = array_filter($args, function ($c) use ($conduit, $basin, $droplets) {
      return $c['conduitId'] == $conduit && $c['droplets'] >= $droplets && $c['basin'] == $basin;
    });

    if (empty($filter)) {
      throw new \BgaVisibleSystemException('Combinaison not possible. Should not happen');
    }
    $selected = array_pop($filter);
    $oConduit = $selected['conduit'];
    $bonus = $ctxArgs['bonus'] ?? 0;

    // produce energy + bonus + malus
    $energy = $oConduit['production'] * $droplets + $bonus;
    Notifications::produce($company, $energy, $droplets);
    Engine::insertAsChild(['action' => GAIN, 'args' => [ENERGY => $energy]]);

    if ($oConduit['owner'] != $company->getId()) {
      // Pay X credit to other player (insert nodes?)
      Engine::insertAsChild([
        'action' => PAY,
        'cId' => $company->getId(),
        'args' => [
          'nb' => $droplets,
          'costs' => Utils::formatCost([CREDIT => 1]),
          'source' => clienttranslate('use of conduit'),
          'to' => $oConduit['owner'],
        ],
      ]);
      // gain x VP
      Companies::get($oConduit['owner'])->incScore($droplets);
      Notifications::score(Companies::get($oConduit['owner']), $droplets, clienttranslate('for use of conduit'));
    }

    // contract fullfilment?
    Engine::insertAsChild(['action' => \FULFILL_CONTRACT, 'args' => [ENERGY => $energy]]);

    $newBasin = 'P' . $oConduit['end'] . '_0';

    // move droplet to new basin
    for ($i = 0; $i < $droplets; $i++) {
      $drop = Meeples::getFilteredQuery(null, $basin, [DROPLET])
        ->get()
        ->first();
      $original = $drop;
      Meeples::DB()->update(['meeple_location' => $newBasin], $drop['id']);
      $drop['location'] = $newBasin;
      Notifications::moveDroplet($drop, $original);
      Map::flow($drop['id']);
    }
    $this->resolveAction(['droplets' => $droplets]);
  }
}
