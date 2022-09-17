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

function getCodeOfSpace($space, $startAt = 0)
{
  $result = 0;
  $t = $space[0];
  if ($t == 'B') {
    $result = (int) \substr($space, 1);
  } elseif ($t == 'P') {
    $p = explode('_', $space);
    $result = (int) substr($p[0], 1);
  } elseif ($t == 'C') {
    $i = (int) substr($space, 1, -1);
    $side = substr($space, -1);
    $result = 2 * $i + ($side == 'L' ? 0 : 1);
  }

  if ($result < $startAt) {
    $result += 30;
  }
  return $result;
}

trait AutomaPlaceStructureTrait
{
  public function getAutomaStructureEmplacement($company, $structure, $spaceIds)
  {
    // Keep only one space for each powerhouse zone
    if ($structure == POWERHOUSE) {
      $spaceIds = array_uunique($spaceIds, function ($a, $b) {
        return getCodeOfSpace($a) - getCodeOfSpace($b);
      });
    }

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
    $criteria = $this->getAutomaCriteria()[$structure != ELEVATION ? $structure : BASE];
    while (count($spaceIds) > 1 && $i < 3) {
      $spaceIds = array_values($this->applyAutomaCriterion($company, $criteria[$i++], $spaceIds));
    }

    // Use final tie-breaker
    if (count($spaceIds) > 1) {
      $prefixes = [
        BASE => 'B',
        ELEVATION => 'B',
        POWERHOUSE => 'P',
        CONDUIT => 'C',
      ];
      $code = getCodeOfSpace($prefixes[$structure] . $criteria[3]);
      usort($spaceIds, function ($a, $b) use ($code) {
        return getCodeOfSpace($a, $code) - getCodeOfSpace($b, $code);
      });

      $target = getCodeOfSpace($spaceIds[0]);
      Utils::filter($spaceIds, function ($space) use ($target) {
        return getCodeOfSpace($space) == $target;
      });
    }

    // Really final tie-breaker for paying/non-paying slot for Dam
    if (count($spaceIds) > 1 && $structure == BASE) {
      Utils::filter($spaceIds, function ($space) {
        return substr($space, -1) == 'L';
      });
    }

    if (count($spaceIds) > 1) {
      var_dump($spaceIds);
      die('Automa error : several spaces possible for placing structure.');
    }

    if(!isset($spaceIds[0])){
      var_dump($spaceIds);
      die('Automa error : issue with space for placing structure');
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
        list($w, $passingDroplets) = Map::emulateFlowDroplets();
        $basins = aggregate($spaceIds, function ($sId) use ($passingDroplets) {
          return count($passingDroplets[$sId] ?? []);
        });
        $maxDroplets = max(array_keys($basins));
        $potentialLocations = $basins[$maxDroplets];
        if (count($potentialLocations) == 1) {
          return $potentialLocations;
        }

        // Apply HS criterion for tie breaking
        foreach ($this->getAutomaCriteria()[PLACE_DROPLET] as $hs) {
          $headstream = 'H' . $hs;
          $basins = aggregate($potentialLocations, function ($sId) use ($passingDroplets, $headstream) {
            return count(array_keys($passingDroplets[$sId] ?? [], $headstream));
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
          return array_values(array_unique($locations));
        }
        break;

      //////////////////////////////////////////
      // Keep the locations "above" a basin with an automa dam
      case AI_CRITERION_BASE_BASIN:
        $connectedToOwn = [];
        $notConnectedToOther = [];
        $otherIds = Companies::getOpponentIds($company);

        foreach (Map::getZones() as $zoneId => $zone) {
          $possibleBasins = \array_intersect($zone['basins'] ?? [], $spaceIds);
          if (empty($possibleBasins)) {
            continue;
          }

          foreach ($zone['conduits'] ?? [] as $sId => $conduit) {
            $basins = Map::getZones()[$conduit['end']]['basins'];
            if (Map::getBuiltStructure($basins, $company) !== null) {
              $connectedToOwn = array_merge($connectedToOwn, $possibleBasins);
            }
            if (Map::getBuiltStructure($basins, $otherIds) === null) {
              $notConnectedToOther = array_merge($notConnectedToOther, $possibleBasins);
            }
          }
        }

        if (!empty($connectedToOwn)) {
          return $connectedToOwn;
        } elseif (!empty($notConnectedToOther)) {
          return $notConnectedToOther;
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
            return $conduits[$sId]['production'];
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
            return $conduits[$sId]['production'];
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
        $type = BASE;
      case \AI_CRITERION_CONDUIT_POWERHOUSE:
      case \AI_CRITERION_CONDUIT_POWERHOUSE_REVERSE:
        $conduits = [
          'owned' => [],
          'neutral' => [],
          'opponent' => [],
        ];
        foreach ($spaceIds as $sId) {
          $zId = Map::getZoneId($sId);
          $structures = ($type ?? null) == BASE ? Map::getBuiltDamsInZone($zId) : Map::getLinkedPowerhouses($sId);
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
            return array_values(array_unique($conduits[$key]));
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
          $conduitSpaces = Map::getPowerhouses()[$sId]['conduits'];
          foreach (Map::getBuiltStructures($conduitSpaces) as $meeple) {
            $production = Map::getConduits()[$meeple['location']]['production'];
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
          return array_values(array_unique($maxPowerhouses));
        }
        break;

      //////////////////////////////////////
      // Keep only powerhouses linked to an owned dam
      case \AI_CRITERION_POWERHOUSE_BARRAGE:
        $powerhouses = [];
        foreach (Map::getZones() as $zoneId => $zone) {
          // Is there an automa basin here ?
          if (Map::getBuiltStructure($zone['basins'] ?? [], $company) === null) {
            continue;
          }

          foreach ($zone['conduits'] ?? [] as $sId => $conduit) {
            $connectedSpaces = array_intersect(Map::getLinkedPowerhousesSpaces($sId), $spaceIds);
            if (!empty($connectedSpaces)) {
              $powerhouses = array_merge($powerhouses, $connectedSpaces);
            }
          }
        }

        if (!empty($powerhouses)) {
          return array_values(array_unique($powerhouses));
        }

        break;

      //////////////////////////////////////
      // Keep only powerhouses in the plain
      case \AI_CRITERION_POWERHOUSE_PLAIN:
        $locations = Map::getLocationsInArea(PLAIN);
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
        $locations = Map::getLocationsInZone($zoneId);
        $possiblePowerhouses = \array_intersect($locations, $spaceIds);
        if (!empty($possiblePowerhouses)) {
          return array_values($possiblePowerhouses);
        }
        break;

      //////////////////////////////////////
      // Keep only powerhouses that will feed automa's dams/not opponent's dams
      case \AI_CRITERION_POWERHOUSE_BARRAGE_WATER:
      case \AI_CRITERION_POWERHOUSE_BARRAGE_WATER_REVERSE:
        $feedingAutoma = [];
        $feedingOpponent = [];
        $otherIds = Companies::getOpponentIds($company);

        foreach ($spaceIds as $space) {
          $fedLocations = Map::getFedLocations($space);
          $fAutoma = false;
          $fOpponent = false;

          foreach ($fedLocations as $location) {
            if (\array_key_exists($location, Map::getBasins())) {
              if (Map::getBuiltStructure($location, $company)) {
                $fAutoma = true;
              }
              if (Map::getBuiltStructure($location, $otherIds)) {
                $fOpponent = true;
              }
              if ($fAutoma && $fOpponent) {
                break;
              }
            }
          }

          if ($fAutoma) {
            $feedingAutoma[] = $space;
          }
          if ($fOpponent) {
            $feedingOpponent[] = $space;
          }
        }

        // Swap the two variales if it's the reverse criterion
        if (in_array($criterion, AI_REVERSE_CRITERIA)) {
          swapVars($feedingAutoma, $feedingOpponent);
        }
        if (!empty($feedingAutoma)) {
          return $feedingAutoma;
        }
        if (!empty($feedingOpponent)) {
          return $feedingOpponent;
        }

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

function array_uunique($array, $comparator)
{
  $unique_array = [];
  do {
    $element = array_shift($array);
    $unique_array[] = $element;

    $array = array_udiff($array, [$element], $comparator);
  } while (count($array) > 0);

  return $unique_array;
}

function swapVars(&$x, &$y)
{
  $tmp = $x;
  $x = $y;
  $y = $tmp;
}
