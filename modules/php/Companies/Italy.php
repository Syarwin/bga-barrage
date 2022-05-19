<?php
namespace BRG\Companies;

class Italy extends \BRG\Models\Company
{
  public function __construct($row)
  {
    $this->id = \COMPANY_ITALY;
    $this->cname = clienttranslate('Italy');
    $this->revenueBoard = [
      BASE => [
        2 => [
          'action' => GAIN,
          'args' => [\CREDIT => 3],
        ],
        4 => [
          'action' => GAIN,
          'args' => [\VP => 5],
        ],
        5 => [
          'action' => GAIN,
          'args' => [\VP => 7],
        ],
      ],
      \ELEVATION => [
        2 => [
          'action' => ROTATE_WHEEL,
          'args' => [
            'n' => 1,
          ],
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
      CONDUIT => [
        2 => [
          'type' => NODE_XOR,
          'childs' => [['action' => GAIN, 'args' => [\EXCAVATOR => 1]], ['action' => GAIN, 'args' => [\MIXER => 1]]],
        ],
        4 => [
          [
            'action' => GAIN,
            'args' => [\VP => 2, CREDIT => 5],
          ],
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
}
