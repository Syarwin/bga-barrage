<?php
namespace BRG\Maps;

class BaseMap extends AbstractMap
{
  public function getId()
  {
    return MAP_BASE;
  }

  public function getHeadstreams()
  {
    return ['HA', 'HB', 'HC', 'HD'];
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
            'production' => 5,
            'end' => 8,
          ],
          'C1R' => [
            'production' => 4,
            'end' => 5,
          ],
        ],
      ],
      2 => [
        'area' => MOUNTAIN,
        'basins' => ['B2U', 'B2L'],
        'conduits' => [
          'C2L' => [
            'production' => 3,
            'end' => 6,
          ],
          'C2R' => [
            'production' => 5,
            'end' => 10,
          ],
        ],
      ],
      3 => [
        'area' => MOUNTAIN,
        'basins' => ['B3U', 'B3L'],
        'conduits' => [
          'C3L' => [
            'production' => 4,
            'end' => 5,
          ],
          'C3R' => [
            'production' => 3,
            'end' => 6,
          ],
        ],
      ],
      4 => [
        'area' => MOUNTAIN,
        'basins' => ['B4U', 'B4L'],
        'conduits' => [
          'C4L' => [
            'production' => 3,
            'end' => 7,
          ],
          'C4R' => [
            'production' => 5,
            'end' => 12,
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
      5 => [
        'area' => HILL,
        'basins' => ['B5U', 'B5L'],
        'conduits' => [
          'C5L' => [
            'production' => 3,
            'end' => 8,
          ],
          'C5R' => [
            'production' => 4,
            'end' => 10,
          ],
        ],
        'powerhouses' => [0, 3],
      ],
      6 => [
        'area' => HILL,
        'basins' => ['B6U', 'B6L'],
        'conduits' => [
          'C6L' => [
            'production' => 4,
            'end' => 9,
          ],
          'C6R' => [
            'production' => 2,
            'end' => 7,
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
            'end' => 10,
          ],
          'C7R' => [
            'production' => 3,
            'end' => 12,
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
      8 => [
        'area' => PLAIN,
        'basins' => ['B8U', 'B8L'],
        'conduits' => [
          'C8L' => [
            'production' => 3,
            'end' => 11,
          ],
          'C8R' => [
            'production' => 2,
            'end' => 9,
          ],
        ],
        'powerhouses' => [0, 0, 3],
      ],
      9 => [
        'area' => PLAIN,
        'basins' => ['B9U', 'B9L'],
        'conduits' => [
          'C9L' => [
            'production' => 1,
            'end' => 11,
          ],
          'C9R' => [
            'production' => 3,
            'end' => 12,
          ],
        ],
        'powerhouses' => [0, 0, 3],
      ],
      10 => [
        'area' => PLAIN,
        'basins' => ['B10U', 'B10L'],
        'conduits' => [
          'C10L' => [
            'production' => 2,
            'end' => 11,
          ],
          'C10R' => [
            'production' => 1,
            'end' => 12,
          ],
        ],
        'powerhouses' => [0, 3, 3],
      ],

      11 => [
        'area' => PLAIN,
        'powerhouses' => [0, 0, 3, 3],
      ],
      12 => [
        'area' => PLAIN,
        'powerhouses' => [0, 3, 3, 3],
      ],
    ];
  }

  public function getRivers()
  {
    return [
      ['HA', 'B1U'],
      ['HB', 'B2U'],
      ['HC', 'B3U'],
      ['HD', 'B4U'],

      ['B1L', 'P8', 'B8U'],
      ['B2L', 'P5', 'B5U'],
      ['B3L', 'P6', 'B6U'],
      ['B4L', 'P7', 'B7U'],

      ['B5L', 'P9', 'B9U'],
      ['B6L', 'P10', 'B10U'],
      ['B7L', 'P10', 'B10U'],

      ['B8L', 'P11', 'EXIT'],
      ['B9L', 'P11', 'EXIT'],
      ['B10L', 'P12', 'EXIT'],
    ];
  }
}
