<?php
namespace BRG\States;
use BRG\Core\Globals;
use BRG\Core\Notifications;
use BRG\Managers\Players;
use BRG\Managers\Companies;
use BRG\Managers\Meeples;
use BRG\Managers\Fences;
use BRG\Managers\Actions;
use BRG\Managers\AutomaCards;
use BRG\Managers\TechnologyTiles;
use BRG\Models\PlayerBoard;
use BRG\Helpers\Utils;
use BRG\Actions\Construct;
use BRG\Map;

trait AutomaPlaceStructureTrait
{
  public function getAutomaStructureEmplacement($structure, $spacesIds)
  {
    // Can we complete a production system ?
    if (count($spaceIds) > 1 && $structure != \ELEVATION) {
      $almostComplete = Map::getAlmostCompleteProductionSystems($company, $structure);
      $spaces = $spaceIds;
      Utils::filter($spaces, function ($spaceId) use ($structure, $almostComplete) {
        return $this->canCompleteSystem($structure, $spaceId, $almostComplete);
      });
      if (!empty($spaces)) {
        $spaceIds = $spaces;
      }
    }

    if (count($spaceIds) > 1) {
      var_dump($spaceIds);
      $criteria = $this->getAutomaCriteria()[$structure];

      $spaceIds = $this->applyAutomaCriterion($company, $criteria[0], $spaceIds);
      var_dump($spaceIds);
      die('todo : tiebreaker for construct');
    }

    // Use criteria to reduce the possible choice
    while (count($spaceIds) > 1) {
    }

    // TODO
    if (count($pairs) > 1) {
      die('todo : tiebreaker for construct');
    }
  }

  // Given the list of almost complete systems, check whether a specific space can complete one of these system
  public function canCompleteSystem($structure, $spaceId, $almostCompleteSystems)
  {
    foreach ($almostCompleteSystems as $system) {
      if ($structure == BASE && $spaceId == $system['basin']) {
        return true;
      } elseif ($structure == CONDUIT && $spaceId == $system['conduitSpaceId']) {
        return true;
      } elseif ($structure == POWERHOUSE && startsWith($spaceId, $system['powerhouseSpaceId'])) {
        return true;
      }
    }
    return false;
  }

  // Apply a given criterion to a pool of space
  public function applyAutomaCriterion($company, $criterion, $spaceIds)
  {
    switch ($criterion) {
      /////////////////////////////////
      //  ____    _    ____  _____
      // | __ )  / \  / ___|| ____|
      // |  _ \ / _ \ \___ \|  _|
      // | |_) / ___ \ ___) | |___
      // |____/_/   \_\____/|_____|
      //
      /////////////////////////////////

      //////////////////////////////////////////
      // Keep only the basin linked to the most powerful conduit possible
      case AI_CRITERION_BASE_MAX_CONDUIT:
        $maxProd = 0;
        $maxBasins = [];
        $isOwn = false;
        foreach (Map::getZones() as $zoneId => $zone) {
          $possibleBasins = \array_intersect($zone['basins'] ?? [], $spaceIds);
          if (empty($possibleBasins)) {
            continue;
          }

          foreach ($zone['conduits'] ?? [] as $sId => $conduit) {
            // Is this conduit built by someone ?
            $meeple = Meeples::getOnSpace($sId, CONDUIT)->first();
            if (is_null($meeple)) {
              continue;
            }

            $owned = $meeple['cId'] == $company->getId();
            if ($conduit['production'] > $maxProd || ($conduit['production'] == $maxProd && $owned && !$isOwn)) {
              $maxProd = $conduit['production'];
              $maxBasins = $possibleBasins;
              $isOwn = $owned;
            } elseif ($conduit['production'] == $maxProd && $isOwn == $owned) {
              $maxBasins = array_merge($maxBasins, $possibleBasins);
            }
          }
        }
        if (!empty($maxBasins)) {
          return $maxBasins;
        }
        break;

      //////////////////////////////////////////
      // Keep only the paying slot
      case AI_CRITERION_BASE_PAYING_SLOT:
        $spaces = $spaceIds;
        Utils::filter($spaces, function ($spaceId) {
          return endsWith($spaceId, 'U'); // Upper basins are the costly ones
        });
        if (!empty($spaces)) {
          return $spaces;
        }
        break;

      //////////////////////////////////////////////
      //   ____ ___  _   _ ____  _   _ ___ _____
      //  / ___/ _ \| \ | |  _ \| | | |_ _|_   _|
      // | |  | | | |  \| | | | | | | || |  | |
      // | |__| |_| | |\  | |_| | |_| || |  | |
      //  \____\___/|_| \_|____/ \___/|___| |_|
      //
      //////////////////////////////////////////////

      //////////////////////////////////////////
      // Keep only the highest capacity conduits
      case \AI_CRITERION_CONDUIT_HIGHEST:
        $conduits = Map::getConduits();
        $conduitProductions = aggregate(
          $spaceIds,
          function ($sId) use ($conduits) {
            return $conduits[$sId]['productions'];
          },
          $spaceIds
        );
        $maxProduction = max(array_keys($conduitProductions));
        return $conduitProductions[$maxProduction];
        break;

      //////////////////////////////////////////
      // Keep only the second highest capacity conduits
      case \AI_CRITERION_CONDUIT_SECOND_HIGHEST:
        $conduits = Map::getConduits();
        $conduitProductions = aggregate(
          $spaceIds,
          function ($sId) use ($conduits) {
            return $conduits[$sId]['productions'];
          },
          $spaceIds
        );
        $productions = array_keys($conduitProductions);
        if (count($productions) > 1) {
          rsort($productions);
          $secondHighest = $productions[1];
          return $conduitProductions[$secondHighest];
        } else {
          $maxProduction = max($productions);
          return $conduitProductions[$maxProduction];
        }
        break;

      //////////////////////////////////////////
      // Keep only conduit connected to a built barrage/powerhouse
      case \AI_CRITERION_CONDUIT_BARRAGE:
      case \AI_CRITERION_CONDUIT_BARRAGE_REVERSE:
        $type = BARRAGE;
      case \AI_CRITERION_CONDUIT_POWERHOUSE:
      case \AI_CRITERION_CONDUIT_POWERHOUSE_REVERSE:
        $conduits = [
          'owned' => [],
          'neutral' => [],
          'opponent' => [],
        ];
        foreach ($spaceIds as $sId) {
          $zId = self::getZoneId($sId);
          $structures = ($type ?? null) == BARRAGE ? self::getBuiltDamsInZone($zId) : self::getLinkedPowerhouses($sId);
          foreach ($structures as $structure) {
            if ($structure['cId'] == $company->getId()) {
              $key = 'owned';
            } elseif ($structure['cId'] == COMPANY_NEUTRAL) {
              $key = 'neutral';
            } else {
              $key = 'opponent';
            }
            $conduits[$key][] = $sId;
          }
        }

        $order = ['owned', 'neutral', 'opponent'];
        if (in_array($criterion, AI_REVERSE_CRITERIA)) {
          $order = \array_reverse($order);
        }
        foreach ($order as $key) {
          if (!empty($conduits[$key])) {
            return $conduits[$key];
          }
        }
        break;

      ///////////////////////////////////////////////////////////////////////
      //  ____   _____        _______ ____  _   _  ___  _   _ ____  _____
      // |  _ \ / _ \ \      / / ____|  _ \| | | |/ _ \| | | / ___|| ____|
      // | |_) | | | \ \ /\ / /|  _| | |_) | |_| | | | | | | \___ \|  _|
      // |  __/| |_| |\ V  V / | |___|  _ <|  _  | |_| | |_| |___) | |___
      // |_|    \___/  \_/\_/  |_____|_| \_\_| |_|\___/ \___/|____/|_____|
      //
      ///////////////////////////////////////////////////////////////////////
    }

    return $spaceIds;
  }
}

function aggregate($arr, $func)
{
  $result = [];
  foreach ($arr as $val) {
    $v = $func($val);
    $result[$v][] = $val;
  }
  return $result;
}

function startsWith($haystack, $needle)
{
  $length = strlen($needle);
  return substr($haystack, 0, $length) === $needle;
}

function endsWith($haystack, $needle)
{
  $length = strlen($needle);
  return $length > 0 ? substr($haystack, -$length) === $needle : true;
}
