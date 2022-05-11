<?php
namespace BRG\Companies;

class Netherlands extends \BRG\Models\Company
{
  public function __construct($row)
  {
    $this->id = \COMPANY_NETHERLANDS;
    $this->cname = clienttranslate('Netherlands');
    $this->revenueBoard = [
      BASE => [
        2 => [
          'action' => PLACE_DROPLET,
          'args' => [
            'n' => 2,
            'flows' => false,
          ],
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
          'args' => [\CREDIT => 3],
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
          'args' => [\ENERGY => 2],
        ],
        4 => [
          'type' => NODE_XOR,
          'childs' => [
            ['action' => GAIN, 'args' => [\EXCAVATOR => 1, MIXER => 1]],
            ['action' => GAIN, 'args' => [\MIXER => 2]],
            ['action' => GAIN, 'args' => [\EXCAVATOR => 2]],
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
