#!/usr/bin/env php
<?php
/** Test program for the cmdlineapp.php PHP source */
require("cmdlineapp.php");

class TestApp extends CmdlineApp {
  var $syntax = [
    ["long" => "string", "short" => "s", "type" => "string", "required" => 1, "help" => "A required string"],
    ["long" => "int", "short" => "i", "type" => "integer", "array" => 1, "required" => 0, "help" => "An integer"],
    ["long" => "float", "short" => "f", "type" => "float", "required" => 0, "help" => "A float"],
    ["long" => "date", "short" => "d", "type" => "dateymd", "required" => 0, "default" => "1970-01-01", "help" => "Any date"],
    ["long" => "help", "short" => "h", "type" => "flag", "default" => false, "help" => "Show the command's syntax"],
    ["long" => "verbose", "short" => "v", "type" => "flag", "default" => false, "help" => "Verbose mode, show more output"],
    ["long" => "debug", "type" => "flag", "default" => false, "help" => "Debug mode, show development data"]
  ];

  /** The main function */
  function main() {
    $this->checksyntax();
    var_dump($this->opt);
  }
}

$app = new TestApp();
$app->main();
# vim: expandtab:ai:ts=2:sw=2
?>
