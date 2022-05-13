<?php
namespace BRG\Companies;

class USA extends \BRG\Models\Company
{
  public function __construct($row)
  {
    $this->id = \COMPANY_USA;
    $this->cname = clienttranslate('USA');
    $this->revenueBoard = [
      BASE => [
        2 => [
          'action' => ROTATE_WHEEL,
          'args' => [
            'n' => 1,
          ],
        ],
        4 => [
          'action' => GAIN,
          'args' => [\CREDIT => 6],
        ],
        5 => [
          'action' => GAIN,
          'args' => [\VP => 7],
        ],
      ],
      \ELEVATION => [
        2 =>
          //  [
          //   'type' => NODE_XOR,
          //   'childs' => [['action' => GAIN, 'args' => [\EXCAVATOR => 1]], ['action' => GAIN, 'args' => [\MIXER => 1]]],
          // ],
          [
            'action' => GAIN,
            'args' => [\VP => 3, ENERGY => 4],
          ],
        4 => [
          'type' => NODE_SEQ,
          'childs' => [
            [
              'action' => GAIN,
              'args' => [\VP => 2],
            ],
            [
              'action' => PLACE_DROPLET,
              'args' => [
                'n' => 2,
                'flows' => true,
              ],
            ],
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
          'args' => [\VP => 3],
        ],
        4 => [
          'type' => NODE_SEQ,
          'childs' => [
            [
              'action' => ROTATE_WHEEL,
              'args' => [
                'n' => 1,
              ],
            ],
            [
              'action' => GAIN,
              'args' => [\VP => 2],
            ],
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
