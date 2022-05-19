<?php
namespace BRG\Companies;

class France extends \BRG\Models\Company
{
  public function __construct($row)
  {
    $this->id = \COMPANY_FRANCE;
    $this->cname = clienttranslate('France');
    $this->revenueBoard = [
      BASE => [
        2 => [
          'type' => NODE_XOR,
          'childs' => [['action' => GAIN, 'args' => [\EXCAVATOR => 1]], ['action' => GAIN, 'args' => [\MIXER => 1]]],
        ],
        4 => [
          'action' => ROTATE_WHEEL,
          'args' => [
            'n' => 2,
          ],
        ],
        5 => [
          'action' => GAIN,
          'args' => [\VP => 7],
        ],
      ],
      \ELEVATION => [
        2 => [
          'action' => GAIN,
          'args' => [\VP => 3],
        ],
        4 => [
          'type' => NODE_XOR,
          'childs' => [
            ['action' => GAIN, 'args' => [VP => 2, \EXCAVATOR => 1]],
            ['action' => GAIN, 'args' => [VP => 2, \MIXER => 1]],
          ],
        ],
        5 => [
          'action' => GAIN,
          'args' => [\VP => 7],
        ],
      ],
      CONDUIT => [
        2 => [
          'action' => GAIN,
          'args' => [\CREDIT => 3],
        ],
        4 => [
          'action' => GAIN,
          'args' => [\ENERGY => 4],
        ],
        5 => [
          'action' => GAIN,
          'args' => [\VP => 7],
        ],
      ],
      \POWERHOUSE => [2 => ['bonus' => 1], 3 => ['bonus' => 'specialpower'], 4 => ['bonus' => 2]],
    ];
    parent::__construct($row);
  }

  public function getContractReduction()
  {
    return 3;
  }
}
