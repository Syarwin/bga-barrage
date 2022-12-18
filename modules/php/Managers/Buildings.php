<?php
namespace BRG\Managers;
use BRG\Core\Globals;
use BRG\Core\Stats;
use BRG\Helpers\Utils;
use BRG\Managers\Players;

/*
 * Buildings manager
 */
class Buildings extends \BRG\Helpers\Pieces
{
  protected static $table = 'buildings';
  protected static $prefix = 'building_';
  protected static $autoIncrement = true;
  protected static $autoremovePrefix = false;
  protected static $customFields = ['type'];

  protected static function cast($row)
  {
    return self::getInstance($row['type'], $row);
  }

  public static function getAll()
  {
    return Globals::isLWP() ? self::getSelectQuery()->get() : new Collection([]);
  }

  public static function getUiData()
  {
    $slots = self::getConstructSlots();
    return self::getAll()->map(function ($elem) use ($slots) {
      $data = $elem->jsonSerialize();
      $data['slots'] = $slots[$elem->getId()];
      return $data;
    });
  }

  public static function getConstructSlots()
  {
    $nPlayers = Players::count();
    $slots = [];
    $i = 0;
    foreach (self::getAll() as $id => $building) {
      $slots[$id] = [];
      for ($j = 0; $j < 4; $j++) {
        // Disable second spot depending on number of player
        if ($j == 1 && (($nPlayers < 3 && $i < 2) || $nPlayers < 4)) {
          continue;
        }

        $slots[$id][] = [
          'id' => 'buildingslot-' . $id . '-' . $j,
          'cost' => $j < 2 ? 0 : 3,
        ];
      }
      $i++;
    }

    return $slots;
  }

  static $classes = [
    'Cofferdam',
    'ControlStation',
    'CustomerOffice',
    'DeveloppmentOffice',
    'EnergyRelayField',
    'FinancialDivision',
    'LoanAgency',
    'ResearchLab',
    'RobotFactory',
    'WindFarm',
  ];

  public function getInstance($bId, $data = null)
  {
    $className = '\BRG\Buildings\\' . $bId;
    return new $className($data);
  }

  protected function getAvailable()
  {
    $buildingIds = [];
    foreach (static::$classes as $bId) {
      $building = self::getInstance($bId);
      if ($building->isAvailable()) {
        $buildingIds[] = $bId;
      }
    }

    return $buildingIds;
  }

  public function setupNewGame()
  {
    $buildingIds = self::getAvailable();
    $ids = Utils::rand($buildingIds, 5);
    $values = [];
    foreach ($ids as $id) {
      $values[] = [
        'location' => 'board',
        'type' => $id,
      ];
    }
    self::create($values);
  }
}
