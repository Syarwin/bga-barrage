<?php
namespace BRG\ActionBoards;

use BRG\Managers\Meeples;
use BRG\Managers\Companies;
use BRG\Core\Notifications;
use BRG\Core\Engine;
use BRG\Core\Globals;

/*
 * Machinery Shop action space board
 */

class MachineryShopActionBoard extends AbstractActionBoard
{
  protected static $id = \BOARD_MACHINERY_SHOP;
  public static function getName()
  {
    return clienttranslate('Machinery Shop');
  }

  public function getUiStructure()
  {
    $rows = [];

    $rows[] = [
      'e',
      ['i' => '<CREDIT:2><ARROW><EXCAVATOR_ICON:1>', 't' => clienttranslate('Pay two credits for one excavator')],
      'ec',
    ];

    if (Companies::count() >= 4) {
      $rows[] = [
        'a',
        ['i' => '<CREDIT:4><ARROW><ANY>', 't' => clienttranslate('Pay four credits for one excavator or one mixer')],
        'abis',
      ];
    }

    $rows[] = [
      'b',
      ['i' => '<CREDIT:5><ARROW><EXCAVATOR_ICON:1><MIXER_ICON:1>', 't' => clienttranslate('Pay five credits for one excavator and one mixer')],
      'bc',
    ];

    return $rows;
  }

  public function getAvailableSpaces()
  {
    $spaces = [];

    $spaces[] = [
      'board' => self::$id,
      'uid' => self::$id . '-e',
      'nEngineers' => 1,
      'flow' => static::payGainNode([CREDIT => 2], [\EXCAVATOR => 1]),
    ];

    $spaces[] = [
      'board' => self::$id,
      'uid' => self::$id . '-b',
      'nEngineers' => 2,
      'flow' => static::payGainNode([CREDIT => 5], [\EXCAVATOR => 1, MIXER => 1]),
    ];

    // Add the costy action space
    foreach ($spaces as $space) {
      $space['uid'] .= 'c';
      $space['nEngineers'] = $space['nEngineers'] == 1 ? 1 : 3;
      $space['cost'] = 3;
      $spaces[] = $space;
    }

    if (Companies::count() >= 4) {
      $flow = [
        'type' => NODE_SEQ,
        'childs' => [
          static::payNode([CREDIT => 4]),
          [
            'type' => NODE_XOR,
            'childs' => [static::gainNode([\EXCAVATOR => 1]), static::gainNode([\MIXER => 1])],
          ],
        ],
      ];

      $spaces[] = [
        'board' => self::$id,
        'uid' => self::$id . '-a',
        'nEngineers' => 1,
        'flow' => $flow,
      ];

      $spaces[] = [
        'board' => self::$id,
        'uid' => self::$id . '-abis',
        'nEngineers' => 2,
        'flow' => $flow,
      ];
    }

    return $spaces;
  }
}
