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
use BRG\Managers\Actions;
use BRG\Managers\Contracts;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\ExternalWorks;
use BRG\Actions\Gain;

trait RoundEndTrait
{
  function stReturnHome()
  {
    /////////////////////////////////////////////////////////////
    // __        __    _              _____ _
    // \ \      / /_ _| |_ ___ _ __  |  ___| | _____      __
    //  \ \ /\ / / _` | __/ _ \ '__| | |_  | |/ _ \ \ /\ / /
    //   \ V  V / (_| | ||  __/ |    |  _| | | (_) \ V  V /
    //    \_/\_/ \__,_|\__\___|_|    |_|   |_|\___/ \_/\_/
    /////////////////////////////////////////////////////////////
    $this->changePhase('waterFlow');
    $droplets = Meeples::getFilteredQuery(null, null, DROPLET)->get();
    Map::flowDroplets($droplets);

    ///////////////////////////////////////////////////////////////////////////
    //  _     _      ______            _      _                        _
    // / |___| |_   / /___ \ _ __   __| |    / \__      ____ _ _ __ __| |
    // | / __| __| / /  __) | '_ \ / _` |   / _ \ \ /\ / / _` | '__/ _` |
    // | \__ \ |_ / /  / __/| | | | (_| |  / ___ \ V  V / (_| | | | (_| |
    // |_|___/\__/_/  |_____|_| |_|\__,_| /_/   \_\_/\_/ \__,_|_|  \__,_|
    //
    ///////////////////////////////////////////////////////////////////////////
    $this->changePhase('roundScoring');
    // Compute energy of each company
    $cEnergies = [];
    foreach (Companies::getTurnOrder() as $cId) {
      $company = Companies::get($cId);
      $cEnergies[$company->getEnergy()][] = $company;
    }
    krsort($cEnergies);

    // 1st and 2nd award
    $energies = array_keys($cEnergies);
    $firstEnergy = $energies[0];
    if ($firstEnergy > 0) {
      $firstCompanies = $cEnergies[$firstEnergy];
      // Tied for first place => share 8 VP
      if (count($firstCompanies) > 1) {
        $vp = ceil(8 / count($firstCompanies));
        foreach ($firstCompanies as $company) {
          $company->incScore($vp, clienttranslate('(tied 1st place)'));
          Stats::incVpEnergyTrack($company, $vp);
        }
      }
      // Otherwise, 6 VP for first player
      else {
        $company = $firstCompanies[0];
        $company->incScore(6, clienttranslate('(1st place on energy track)'));
        Stats::incVpEnergyTrack($company, 6);

        // Give reward for 2nd place
        $secondEnergy = $energies[1];
        if ($secondEnergy > 0) {
          $secondCompanies = $cEnergies[$secondEnergy];
          $secondTied = count($secondCompanies) > 1;
          foreach ($secondCompanies as $company) {
            $vp = $secondTied ? 1 : 2;
            $company->incScore(
              $vp,
              $secondTied ? clienttranslate('(tied 2nd place)') : clienttranslate('(2nd place on energy track)')
            );
            Stats::incVpEnergyTrack($company, $vp);
          }
        }
      }
    }

    ////////////////////////////////////////////////////////////////
    //  _____               _         _                        _
    // |_   _| __ __ _  ___| | __    / \__      ____ _ _ __ __| |
    //   | || '__/ _` |/ __| |/ /   / _ \ \ /\ / / _` | '__/ _` |
    //   | || | | (_| | (__|   <   / ___ \ V  V / (_| | | | (_| |
    //   |_||_|  \__,_|\___|_|\_\ /_/   \_\_/\_/ \__,_|_|  \__,_|
    ////////////////////////////////////////////////////////////////
    // prettier-ignore
    $creditMap = [29 => 8, 22 => 7, 16 => 6, 11 => 5, 7 => 4, 4 => 3, 2 => 2, 1 => 1, 0 => 3];

    foreach ($cEnergies as $energy => $companies) {
      foreach ($companies as $company) {
        // Compute bonus
        $bonus = null;
        foreach ($creditMap as $v => $c) {
          if ($energy >= $v) {
            $bonus = $c;
            break;
          }
        }

        // Award credit bonus
        $tokenId = 'meeple-' . $company->getEnergyToken()['id'];
        Gain::gainResources($company, [CREDIT => $bonus], $tokenId, clienttranslate('energy track award'));
        // Lose 3VP if 0 energy produced
        if ($energy == 0) {
          $company->incScore(-3, clienttranslate('(no energy produced this round)'));
          Stats::incVpEnergyTrack($company, -3);
        }
      }
    }

    //////////////////////////////////////////////////////
    //  ____                          _____ _ _
    // | __ )  ___  _ __  _   _ ___  |_   _(_) | ___
    // |  _ \ / _ \| '_ \| | | / __|   | | | | |/ _ \
    // | |_) | (_) | | | | |_| \__ \   | | | | |  __/
    // |____/ \___/|_| |_|\__,_|___/   |_| |_|_|\___|
    //////////////////////////////////////////////////////
    $statName = 'setRound' . Globals::getRound() . 'VP';
    foreach ($cEnergies as $energy => $companies) {
      foreach ($companies as $company) {
        $bonus = $this->computeRoundBonus($company);
        $vp = $bonus['vp'];
        if (is_null($vp)) {
          Notifications::message(
            clienttranslate(
              '${company_name} produced less than 6 energy this round and will therefore receive no VP from bonus tile'
            ),
            ['company' => $company]
          );
        } elseif ($vp > 0) {
          $company->incScore($vp, clienttranslate('(bonus tile reward)'));
        }

        Stats::$statName($company, $vp ?? 0);
        Stats::incVpRoundBonuses($company, $vp ?? 0);
      }
    }

    // remove the bonus tile
    Notifications::removeBonusTile(Globals::getRound());

    ///////////////////////////////////////////////////////////
    //  _____                    ___          _
    // |_   _|   _ _ __ _ __    / _ \ _ __ __| | ___ _ __
    //   | || | | | '__| '_ \  | | | | '__/ _` |/ _ \ '__|
    //   | || |_| | |  | | | | | |_| | | | (_| |  __/ |
    //   |_| \__,_|_|  |_| |_|  \___/|_|  \__,_|\___|_|
    //
    ///////////////////////////////////////////////////////////
    if (Globals::getRound() == 5) {
      Globals::incRound();
      $this->gamestate->jumpToState(ST_PRE_END_OF_GAME);
      return;
    }

    // Change turn order
    $order = [];
    $no = Companies::count();
    foreach ($cEnergies as $companies) {
      foreach ($companies as $company) {
        $order[] = $company->getId();
        $company->setNo($no--);
      }
    }

    $order = array_reverse($order);
    Globals::setTurnOrder($order);

    // Notify turn order
    Notifications::updateTurnOrder($order);

    /////////////////////////////////
    //   ____ _
    //  / ___| | ___  __ _ _ ___
    // | |   | |/ _ \/ _` | '_  |
    // | |___| |  __/ (_| | | |_|
    //  \____|_|\___|\__,_|_| |_|
    /////////////////////////////////

    // Return home of engineers
    Companies::returnHome();

    // Reset energy on track
    Companies::resetEnergies();
    $tokens = Meeples::resetEnergyTokens();
    Notifications::resetEnergies($tokens);

    if (!Globals::isBeginner()) {
      TechnologyTiles::newRound();
    }
    if (Globals::isLWP()) {
      ExternalWorks::newRound();
    }

    $this->gamestate->jumpToState(ST_BEFORE_START_OF_ROUND);
  }
}
