<?php
namespace BRG\Core;
use agricola;

/*
 * Game: a wrapper over table object to allow more generic modules
 */
class Game
{
  public static function get()
  {
    return agricola::get();
  }
}
