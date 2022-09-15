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
  public function getAutomaStructureEmplacement($company, $structure, $spaceIds)
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

    // Use criteria to reduce the possible choice
    $i = 0;
    $criteria = $this->getAutomaCriteria()[$structure != ELEVATION? $structure : BASE];
    while (count($spaceIds) > 1 && $i < 3) {
      $spaceIds = $this->applyAutomaCriterion($company, $criteria[$i++], $spaceIds);
    }

    // Use final tie-breaker
    if (count($spaceIds) > 1) {
      return $spaceIds[0]; // TODO
      die('todo : tiebreaker for construct');
    }

    // Really final tie-breaker for paying/non-paying slot for Dam
    if(count($spaceIds) > 1 && $structure == BASE){
      die("todo : keep the non-paying one");
    }

    return $spaceIds[0];
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

            $production = $conduit['production'];
            $owned = $meeple['cId'] == $company->getId();
            if ($production > $maxProd || ($production == $maxProd && $owned && !$isOwn)) {
              $maxProd = $production;
              $maxBasins = $possibleBasins;
              $isOwn = $owned;
            } elseif ($production == $maxProd && $isOwn == $owned) {
              $maxBasins = array_merge($maxBasins, $possibleBasins);
            }
          }
        }
        if (!empty($maxBasins)) {
          return $maxBasins;
        }
        break;

      //////////////////////////////////////////
      // Keep only one linked to an automa powerhouse
      case AI_CRITERION_BASE_POWERHOUSE:
        $basins = [];
        foreach (Map::getZones() as $zoneId => $zone) {
          $possibleBasins = \array_intersect($zone['basins'] ?? [], $spaceIds);
          if (empty($possibleBasins)) {
            continue;
          }

          foreach ($zone['conduits'] ?? [] as $sId => $conduit) {
            $powerhouse = Map::getLinkedPowerhouse($sId, $company);
            if (!is_null($powerhouse)) {
              $basins = array_merge($basins, $possibleBasins);
            }
          }
        }

        if (!empty($basins)) {
          return $basins;
        }

        break;

      //////////////////////////////////////////
      // Keep the dam that would get the most droplets
      case AI_CRITERION_BASE_HOLD_WATER:
        list($w, $passingDroplets) = self::emulateFlowDroplets();
        $basins = aggregate($spaceIds, function ($sId) use ($passingDroplets) {
          return count($passingDroplets[$sId]);
        });
        $maxDroplets = max(array_keys($basins));
        $potentialLocations = $basins[$maxDroplets];
        if (count($potentialLocations) == 1) {
          return $potentialLocations;
        }

        // Apply HS criterion for tie breaking
        foreach ($this->getAutomaCriteria()[PLACE_DROPLET] as $hs) {
          $headstream = 'HS' . $hs;
          $basins = aggregate($potentialLocations, function ($sId) use ($passingDroplets, $headstream) {
            return count(array_keys($passingDroplets[$sId], $headstream));
          });
          $maxDroplets = max(array_keys($basins));
          $potentialLocation = $basins[$maxDroplets];
          if (count($potentialLocations) == 1) {
            return $potentialLocations;
          }
        }

        return $potentialLocations;
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

      //////////////////////////////////////////
      // Keep the locations "below" a powerhouse
      case AI_CRITERION_BASE_POWERHOUSE_WATER:
        $locations = [];
        foreach (Map::getZones() as $zoneId => $zone) {
          foreach (Map::getBuiltPowerhousesInZone($zoneId) as $powerhouse) {
            $locations = array_merge($locations, Map::getFedLocations($powerhouse['location']));
          }
        }
        $locations = \array_intersect($spaceIds, $locations);
        if (!empty($locations)) {
          return array_values($locations);
        }
        break;

      //////////////////////////////////////////
      // Keep the locations "above" a basin with an automa dam
      case AI_CRITERION_BASE_BASIN:
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

      //////////////////////////////////////////
      // Keep only the powerhouses linked to the most powerful (built) conduit possible
      case AI_CRITERION_POWERHOUSE_CONDUIT:
        $maxProd = 0;
        $maxPowerhouses = [];
        $isOwn = false;
        foreach ($spaceIds as $sId) {
          $conduitSpaces = self::getPowerhouses()[$sId]['conduits'];
          foreach (self::getBuiltStructures($conduitSpaces) as $meeple) {
            $production = self::getConduits()[$meeple['location']]['production'];
            $owned = $meeple['cId'] == $company->getId();
            if ($production > $maxProd || ($production == $maxProd && $owned && !$isOwn)) {
              $maxProd = $production;
              $maxPowerhouses = [$sId];
              $isOwn = $owned;
            } elseif ($production == $maxProd && $isOwn == $owned) {
              $maxPowerhouses[] = $sId;
            }
          }
        }
        if (!empty($maxPowerhouses)) {
          return array_unique($maxPowerhouses);
        }
        break;

      //////////////////////////////////////
      // Keep only powerhouses linked to an owned dam
      case \AI_CRITERION_POWERHOUSE_BARRAGE:
        break;

      //////////////////////////////////////
      // Keep only powerhouses in the hills
      case AI_CRITERION_POWERHOUSE_HILL:
        $locations = self::getLocationsInArea(HILL);
        $possiblePowerhouses = \array_intersect($locations, $spaceIds);
        if (!empty($possiblePowerhouses)) {
          return array_values($possiblePowerhouses);
        }
        break;

      ///////////////////////////////////////
      // Keep only powerhouses in that section
      case AI_CRITERION_POWERHOUSE_HILL_5:
      case AI_CRITERION_POWERHOUSE_HILL_6:
      case AI_CRITERION_POWERHOUSE_HILL_7:
        $zoneId = (int) substr($criterion, -1); // Get the last digit of the criterion to get the zoneId
        $locations = self::getLocationsInZone($zoneId);
        $possiblePowerhouses = \array_intersect($locations, $spaceIds);
        if (!empty($possiblePowerhouses)) {
          return array_values($possiblePowerhouses);
        }
        break;

      //////////////////////////////////////
      // Keep only powerhouses that will feed automa's dams/not opponent's dams
      case \AI_CRITERION_POWERHOUSE_BARRAGE_WATER:
      case \AI_CRITERION_POWERHOUSE_BARRAGE_WATER_REVERSE:
        break;
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
