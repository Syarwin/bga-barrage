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
    return [
      'capacity' => Map::productionCapacity(Companies::getActive()->getId()),
      'modifier' => '+' . Engine::getNextUnresolved()->getArgs()['bonus'] ?? '',
    ];
  }

  public function stProduct()
  {
    // throw new \feException(print_r(Engine::getNextUnresolved()->getArgs()));
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
    $company->incEnergy($energy);

    if ($oConduit != $company->getId()) {
      // TODO
      // Pay X credit to other player (insert nodes?)
      // gain x VP
    }

    // contract fullfilment?
    // TODO: insert child node

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
  }
}
