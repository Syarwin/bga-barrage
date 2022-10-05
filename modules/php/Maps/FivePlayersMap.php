<?php
namespace BRG\Maps;

class FivePlayersMap extends AbstractMap
{
  public function getId()
  {
    return MAP_5P;
  }

  public function getHeadstreams()
  {
    return ['HA', 'HB', 'HC', 'HD', 'HE'];
  }

  public function getZones()
  {
    return [
      //////////////////////////////////////////////////////
      //  __  __                   _        _
      // |  \/  | ___  _   _ _ __ | |_ __ _(_)_ __  ___
      // | |\/| |/ _ \| | | | '_ \| __/ _` | | '_ \/ __|
      // | |  | | (_) | |_| | | | | || (_| | | | | \__ \
      // |_|  |_|\___/ \__,_|_| |_|\__\__,_|_|_| |_|___/
      //////////////////////////////////////////////////////
      1 => [
        'area' => MOUNTAIN,
        'basins' => ['B1U', 'B1L'],
        'conduits' => [
          'C1L' => [
            'production' => 3,
            'end' => 6,
          ],
          'C1R' => [
            'production' => 5,
            'end' => 11,
          ],
        ],
      ],
      2 => [
        'area' => MOUNTAIN,
        'basins' => ['B2U', 'B2L'],
        'conduits' => [
          'C2L' => [
            'production' => 3,
            'end' => 7,
          ],
          'C2R' => [
            'production' => 5,
            'end' => 12,
          ],
        ],
      ],
      3 => [
        'area' => MOUNTAIN,
        'basins' => ['B3U', 'B3L'],
        'conduits' => [
          'C3L' => [
            'production' => 4,
            'end' => 10,
          ],
          'C3R' => [
            'production' => 2,
            'end' => 8,
          ],
        ],
      ],
      4 => [
        'area' => MOUNTAIN,
        'basins' => ['B4U', 'B4L'],
        'conduits' => [
          'C4L' => [
            'production' => 3,
            'end' => 8,
          ],
          'C4R' => [
            'production' => 4,
            'end' => 9,
          ],
        ],
      ],
      5 => [
        'area' => MOUNTAIN,
        'basins' => ['B5U', 'B5L'],
        'conduits' => [
          'C5L' => [
            'production' => 5,
            'end' => 7,
          ],
          'C5R' => [
            'production' => 4,
            'end' => 13,
          ],
        ],
      ],

      ////////////////////////
      //  _   _ _ _ _
      // | | | (_) | |___
      // | |_| | | | / __|
      // |  _  | | | \__ \
      // |_| |_|_|_|_|___/
      ////////////////////////
      6 => [
        'area' => HILL,
        'basins' => ['B6U', 'B6L'],
        'conduits' => [
          'C6L' => [
            'production' => 3,
            'end' => 10,
          ],
          'C6R' => [
            'production' => 4,
            'end' => 12,
          ],
        ],
        'powerhouses' => [0, 3],
      ],
      7 => [
        'area' => HILL,
        'basins' => ['B7U', 'B7L'],
        'conduits' => [
          'C7L' => [
            'production' => 2,
            'end' => 6,
          ],
          'C7R' => [
            'production' => 5,
            'end' => 14,
          ],
        ],
        'powerhouses' => [0, 3],
      ],
      8 => [
        'area' => HILL,
        'basins' => ['B8U', 'B8L'],
        'conduits' => [
          'C5L' => [
            'production' => 3,
            'end' => 10,
          ],
          'C5R' => [
            'production' => 2,
            'end' => 9,
          ],
        ],
        'powerhouses' => [0, 3],
      ],
      9 => [
        'area' => HILL,
        'basins' => ['B9U', 'B9L'],
        'conduits' => [
          'C5L' => [
            'production' => 3,
            'end' => 12,
          ],
          'C5R' => [
            'production' => 4,
            'end' => 16,
          ],
        ],
        'powerhouses' => [0, 3],
      ],

      /////////////////////////////////
      //  ____  _       _
      // |  _ \| | __ _(_)_ __  ___
      // | |_) | |/ _` | | '_ \/ __|
      // |  __/| | (_| | | | | \__ \
      // |_|   |_|\__,_|_|_| |_|___/
      //
      /////////////////////////////////
      10 => [
        'area' => PLAIN,
        'basins' => ['B10U', 'B10L'],
        'conduits' => [
          'C10L' => [
            'production' => 3,
            'end' => 14,
          ],
          'C10R' => [
            'production' => 2,
            'end' => 11,
          ],
        ],
        'powerhouses' => [0, 0, 3],
      ],
      11 => [
        'area' => PLAIN,
        'basins' => ['B11U', 'B11L'],
        'conduits' => [
          'C11L' => [
            'production' => 1,
            'end' => 14,
          ],
          'C11R' => [
            'production' => 2,
            'end' => 16,
          ],
        ],
        'powerhouses' => [0, 3],
      ],
      12 => [
        'area' => PLAIN,
        'basins' => ['B12U', 'B12L'],
        'conduits' => [
          'C12L' => [
            'production' => 3,
            'end' => 15,
          ],
          'C12R' => [
            'production' => 2,
            'end' => 13,
          ],
        ],
        'powerhouses' => [0, 0, 3],
      ],
      13 => [
        'area' => PLAIN,
        'basins' => ['B13U', 'B13L'],
        'conduits' => [
          'C13L' => [
            'production' => 3,
            'end' => 15,
          ],
          'C13R' => [
            'production' => 1,
            'end' => 16,
          ],
        ],
        'powerhouses' => [0, 3],
      ],

      14 => [
        'area' => PLAIN,
        'powerhouses' => [0, 3, 3],
      ],
      15 => [
        'area' => PLAIN,
        'powerhouses' => [0, 0, 3],
      ],
      16 => [
        'area' => PLAIN,
        'powerhouses' => [0, 0, 3],
      ],
    ];
  }

  public function getExits()
  {
    return ['EXIT_L', 'EXIT_C', 'EXIT_R'];
  }

  public function getRivers()
  {
    return [
      'HA' => 'B1U',
      'HB' => 'B2U',
      'HC' => 'B3U',
      'HD' => 'B4U',
      'HE' => 'B5U',

      'B1U' => 'B1L',
      'B2U' => 'B2L',
      'B3U' => 'B3L',
      'B4U' => 'B4L',
      'B5U' => 'B5L',

      'B1L' => 'P6_0',
      'B2L' => 'P7_0',
      'B3L' => 'P8_0',
      'B4L' => 'P12_0',
      'B5L' => 'P9_0',

      'P6' => 'B6U',
      'P7' => 'B7U',
      'P8' => 'B8U',
      'P9' => 'B9U',

      'B6U' => 'B6L',
      'B7U' => 'B7L',
      'B8U' => 'B8L',
      'B9U' => 'B9L',

      'B6L' => 'P10_0',
      'B7L' => 'P11_0',
      'B8L' => 'P12_0',
      'B9L' => 'P13_0',

      'P10' => 'B10U',
      'P11' => 'B11U',
      'P12' => 'B12U',
      'P13' => 'B13U',

      'B10U' => 'B10L',
      'B11U' => 'B11L',
      'B12U' => 'B12L',
      'B13U' => 'B13L',

      'B10L' => 'P14_0',
      'B11L' => 'P15_0',
      'B12L' => 'P15_0',
      'B13L' => 'P16_0',

      'P14' => 'EXIT_L',
      'P15' => 'EXIT_C',
      'P16' => 'EXIT_R',
    ];
  }
}
