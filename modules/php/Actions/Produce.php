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
    $germanPower = $args['germanPower'] ?? false;
    if ($germanPower) {
      $bonus = 0;
    }
    return !empty(Map::getProductionSystems($company, $bonus, $args['contraints'] ?? null, false, $germanPower));
  }

  public function argsProduce()
  {
    $args = $this->getCtxArgs();
    $bonus = $args['bonus'] ?? 0;
    $germanPower = $args['germanPower'] ?? false;
    $company = Companies::getActive();
    $companyBonus = $company->getProductionBonus();
    $displayedBonus = $bonus + $companyBonus;

    if ($germanPower) {
      $bonus = 0;
      $displayedBonus = 0;
    }
    return [
      'descSuffix' => $germanPower ? 'germany' : '',
      'systems' => Map::getProductionSystems($company, $bonus, $args['constraints'] ?? null, false, $germanPower),
      'i18n' => ['modifier'],
      'modifier' => $germanPower
        ? ''
        : [
          'log' =>
            $companyBonus == 0
              ? clienttranslate('(${n} from action board)')
              : clienttranslate('(${n} from action board + ${m} bonus from company board = ${p})'),
          'args' => [
            'i18n' => ['n', 'p'],
            'n' => [
              'log' => $bonus > 0 ? clienttranslate('${n} bonus') : clienttranslate('${n} malus'),
              'args' => ['n' => $bonus],
            ],
            'm' => $companyBonus,
            'p' => [
              'log' =>
                $displayedBonus > 0
                  ? clienttranslate('${n} bonus')
                  : ($displayedBonus == 0
                    ? clienttranslate('no bonus/malus')
                    : '${n} malus'),
              'args' => ['n' => $displayedBonus],
            ],
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
    $this->produce($system, $nDroplets, $args['germanPower'] ?? false);
    $this->resolveAction(['droplets' => $nDroplets]);
  }

  public function produce($system, $nDroplets, $germanPower = false)
  {
    $production = $system['productions'][$nDroplets] ?? null;
    if (is_null($production)) {
      throw new \BgaVisibleSystemException('Invalid production. Should not happen');
    }

    $droplets = Map::removeDropletsInBasin($system['basin'], $nDroplets);
    if (count($droplets) < $nDroplets) {
      throw new \BgaVisibleSystemException('Invalid number of droplets. Should not happen');
    }

    // Move droplets to conduit then powerhouse
    $tDroplets = new Collection([]); // Avoid issue with two notifs in a row modifying same object
    foreach ($droplets as $droplet) {
      $droplet['path'] = [$droplet['location'], $system['conduitSpaceId'], $system['powerhouseSpaceId']];
      $droplet['location'] = $system['powerhouseSpaceId'];
      $tDroplets[] = $droplet;
    }
    Notifications::moveDroplets($tDroplets);

    // Produce energy
    $company = Companies::getActive();
    $isAI = $company->isAI();
    Notifications::produce(
      $company,
      $system['basin'],
      $system['powerhouseSpaceId'],
      $production,
      $nDroplets,
      $germanPower
    );
    $company->incEnergy($production, true);

    // Let water flows
    foreach ($droplets as &$droplet) {
      $droplet['location'] = $system['powerhouseSpaceId'];
    }
    Map::flowDroplets($droplets);

    // Pay X credit to other player if needed
    if ($system['conduitOwnerId'] != $company->getId()) {
      $opponent = Companies::get($system['conduitOwnerId']);
      Engine::insertAsChild(
        [
          'action' => PAY,
          'cId' => $company->getId(),
          'args' => [
            'nb' => 1,
            'costs' => Utils::formatCost([CREDIT => $nDroplets]),
            'source' => clienttranslate('use of conduit'),
            'to' => $opponent->getId(),
          ],
        ],
        $isAI
      );
      // gain x VP
      $opponent->incScore($nDroplets, clienttranslate('for use of conduit'));
      Stats::incVpConduit($opponent, $nDroplets);
    }

    // Contract fullfilment?
    if (!$isAI) {
      Engine::insertAsChild([
        'action' => \FULFILL_CONTRACT,
        'optional' => true,
        'args' => [ENERGY => $production],
      ]);

      // Germany power
      if ($company->getId() == \COMPANY_GERMANY && $company->productionPowerEnabled() && !$germanPower) {
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
    }
  }
}
