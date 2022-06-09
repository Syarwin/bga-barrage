<?php
namespace BRG\States;
use BRG\Map;
use BRG\Core\Globals;
use BRG\Core\Notifications;
use BRG\Core\Engine;
use BRG\Core\Stats;
use BRG\Managers\Players;
use BRG\Managers\Companies;
use BRG\Managers\Meeples;
use BRG\Managers\Scores;
use BRG\Managers\Actions;
use BRG\Managers\Contracts;

trait EndOfGameTrait
{
  function stEndScoring()
  {
    $companies = Companies::getAll();

    //////////////////////////////////////////////////////
    //   ___  _     _           _   _
    //  / _ \| |__ (_) ___  ___| |_(_)_   _____
    // | | | | '_ \| |/ _ \/ __| __| \ \ / / _ \
    // | |_| | |_) | |  __/ (__| |_| |\ V /  __/
    //  \___/|_.__// |\___|\___|\__|_| \_/ \___|
    //           |__/
    //////////////////////////////////////////////////////
    $bonuses = $this->computeObjectiveTileBonuses();
    foreach ($bonuses as $bonus) {
      $vp = $bonus['share'];
      if ($vp == 0) {
        continue;
      }

      // No tie
      if (count($bonus['cIds']) == 1) {
        $company = $companies[$bonus['cIds'][0]];
        $company->incScore($vp);
        $sources = [
          1 => clienttranslate('(objective tile first place)'),
          2 => clienttranslate('(objective tile second place)'),
          3 => clienttranslate('(objective tile third place)'),
        ];
        $pos = $bonus['pos'][0];
        Notifications::score($company, $vp, $sources[$pos]);
      }
      // Tie
      else {
        $pos = implode('', $bonus['pos']);
        $sources = [
          '12' => clienttranslate('(sharing objective tile first and second place)'),
          '23' => clienttranslate('(sharing objective tile second and third place)'),
          '123' => clienttranslate('(sharing objective tile first, second and third place)'),
        ];

        foreach ($bonus['cIds'] as $cId) {
          $company = $companies[$cId];
          $company->incScore($vp);
          Notifications::score($company, $vp, $sources[$pos]);
        }
      }
    }

    //////////////////////////////////////////////////////
    //  ____
    // |  _ \ ___  ___  ___  _   _ _ __ ___ ___  ___
    // | |_) / _ \/ __|/ _ \| | | | '__/ __/ _ \/ __|
    // |  _ <  __/\__ \ (_) | |_| | | | (_|  __/\__ \
    // |_| \_\___||___/\___/ \__,_|_|  \___\___||___/
    //////////////////////////////////////////////////////
    foreach ($companies as $cId => $company) {
      $count = $company->countReserveResource([CREDIT, EXCAVATOR, MIXER, EXCAMIXER]);
      $vp = intdiv($count, 5);
      if ($vp > 0) {
        $company->incScore($vp);
        Notifications::score($company, $vp, clienttranslate('(bundle(s) of 5 resources left)'));
      }
    }

    ///////////////////////////////////////////
    //  ____                  _      _
    // |  _ \ _ __ ___  _ __ | | ___| |_ ___
    // | | | | '__/ _ \| '_ \| |/ _ \ __/ __|
    // | |_| | | | (_) | |_) | |  __/ |_\__ \
    // |____/|_|  \___/| .__/|_|\___|\__|___/
    //                 |_|
    ///////////////////////////////////////////
    foreach ($companies as $cId => $company) {
      $vp = 0;
      foreach ($company->getBuiltStructures(BASE) as $mId => $m) {
        $vp += Map::countDropletsInBasin($m['location']);
      }

      if ($vp > 0) {
        $company->incScore($vp);
        Notifications::score($company, $vp, clienttranslate('(droplet(s) retained by dams)'));
      }
    }

    $this->gamestate->nextState();
  }
}
