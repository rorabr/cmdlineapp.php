<?php

/** Simple class to help implement command-line applications in PHP - RORA - Montreal
 * created by Rodrigo Antunes (rorabr@github) https://rora.com.br - 2018-08-30
 * https://github.com/rorabr/cmdlineapp.php */

/** Class to be extended by the app */
class CmdlineApp {
  private $typeRegExp = array("string" => '^.*$', "integer" => '^-?\d+$', "float" => '^-?\d+(\.\d+)?$', "dateymd" => '^\d\d\d\d-\d\d-\d\d$', "datedmy" => '/^\d\d\/\d\d\/\d\d\d\d$/', "flag" => "^true$|^false$", "url" => '/^(?:http(s)?:\/\/)?[\w.-]+(?:\.[\w\.-]+)+[\w\-\._~:/?#[\]@!\$&\'\(\)\*\+,;=.]+$/');
  protected $syntax = [];
  protected $opt = [];
  protected $log;
  protected $configFilename;
  private $scriptFilename;
  private $scriptPath = "";
  private $configFilepath = [];
  private $optmeta = [];

  /** Constructor: save application path and name */
  function __construct() {
    global $argv;
    $this->scriptPath = dirname($argv[0]);
    $this->scriptFilename = basename($argv[0]);
  }

  /** Substitute function for the lame internal getopt function available in PHP... */
  function parseArgv() {
    global $argv;
    $short = [];
    $long = [];
    $count = [];
    $missing = [];
    /* get somethings ready */
    for ($i = 0; $i < count($this->syntax); $i++) {
      $long[$this->syntax[$i]["long"]] = $i;
      if ($this->syntax[$i]["short"] != "") $short[$this->syntax[$i]["short"]] = $i;
      if (isset($this->optmeta[$this->syntax[$i]["long"]]["from"])) {
        $count[$i] = 0;
        $missing[$i] = false;
      } else {
        $count[$i] = 0;
        $missing[$i] = $this->syntax[$i]["required"] ? true : false;
        if (! $this->syntax[$i]["array"]) {
          $this->opt[$this->syntax[$i]["long"]] = isset($this->syntax[$i]["default"]) ? $this->syntax[$i]["default"] : null;
        } else {
          $this->opt[$this->syntax[$i]["long"]] = isset($this->syntax[$i]["default"]) ? [$this->syntax[$i]["default"]] : [];
        }
      }
    }
    /* check all arguments */
    $cur = 1;
    while ($cur < count($argv)) {
      $len = 1;
      if ($argv[$cur] == "--") {
        array_splice($argv, $cur, 1);
        $cur = count($argv); /* end of argument processing */
      } elseif (preg_match('/^-(-?)([^\-=]+)(=(.*))?$/s', $argv[$cur], $m)) {
        /* find argument index and value */
        $n = $m[1] != "" ? $long[$m[2]] : $short[$m[2]];
        if (! isset($n)) $this->syntax("Error: invalid argument \"-" . $m[1] . $m[2] . "\"", 1);
        $type = $this->syntax[$n]["type"];
        $count[$n] ++;
        if (! $this->syntax[$n]["array"] && $count[$n] > 1) {
          $this->syntax("Error: argument \"-" . $m[1] . $m[2] . "\" specified more than once", 1);
        }
        if ($type != "flag") {
          if ($cur + (isset($m[4]) ? 0 : 1) >= count($argv)) {
            $this->syntax("Error: argument \"-" . $m[1] . $m[2] . "\" is missing a value", 1);
          }
          $value = isset($m[4]) ? $m[4] : $argv[$cur + 1];
          $len = isset($m[4]) ? 1 : 2;
        } else {
          $value = true;
        }
        /* check type */
        if (substr($type, 0, 1) != "/") { /** tipo definido */
          if (! isset($this->typeRegExp["$type"])) {
            $this->syntax("Error: type of argument \"" . $m[1] . $m[2] . "\" ($type) unknown", 1);
          }
          if (($type == "flag" && ! is_bool($value)) || ($type != "flag" && ! preg_match('/' . $this->typeRegExp["$type"] . '/s', $value))) {
            $this->syntax("Error: argument \"" . $m[1] . $m[2] . "\" (" . json_encode($value) . ") is of incorrect type ($type)", 1);
          }
        } else { /** tipo regexp */
          if (! preg_match($type, $value)) {
            $this->syntax("Error: argument \"" . $m[1] . $m[2] . "\" ('$value') is of incorrect type", 1);
          }
        }
        /* set present and insert into opt */
        $missing[$n] = false;
        if (! $this->syntax[$n]["array"]) {
          $this->opt[$this->syntax[$n]["long"]] =  $value;
        } else {
          if (! isset($this->opt[$this->syntax[$n]["long"]])) {
            $this->opt[$this->syntax[$n]["long"]] = [$value];
          } else {
            array_push($this->opt[$this->syntax[$n]["long"]], $value);
          }
        }
        array_splice($argv, $cur, $len);
      } else {
        $cur ++;
      }
    }
    /* check if all required are set */
    for ($i = 0; $i < count($missing); $i ++) {
      if ($missing[$i]) {
        $this->syntax("Error: missing required argument \"" . $this->syntax[$i]["long"] . "\"", 1);
      }
    }
  }

  /** Check command line syntax */
  function checksyntax() {
    /* check the argument list and set undefined (but allowed) values */
    for ($i = 0; $i < count($this->syntax); $i++) {
      if (! isset($this->syntax[$i]["type"])) throw new Exception("argument \"" . $this->syntax[$i]["long"] . "\" does not have a type");
      if (substr($this->syntax[$i]["type"], 0, 1) != "/" && ! isset($this->typeRegExp[$this->syntax[$i]["type"]])) throw new Exception("argument \"" . $this->syntax[$i]["long"] . "\" does not have a valid type");
      if (! isset($this->syntax[$i]["long"])) throw new Exception("all arguments must have a long name");
      if (! isset($this->syntax[$i]["array"])) $this->syntax[$i]["array"] = 0;
      if (preg_match('/[^a-zA-Z0-9_\-\?\:]/', $this->syntax[$i]["long"])) throw new Exception("invalid long argument name \"" . $this->syntax[$i]["long"] . "\"");
      if (isset($this->syntax[$i]["short"]) && (preg_match('/[^a-zA-Z0-9_\-\?\:]/', $this->syntax[$i]["short"]) || strlen($this->syntax[$i]["short"]) > 1)) throw new Exception("invalid short argument name \"" . $this->syntax[$i]["short"] . "\"");
      if (! isset($this->syntax[$i]["short"])) $this->syntax[$i]["short"] = "";
      if (! isset($this->syntax[$i]["required"])) $this->syntax[$i]["required"] = 0;
      if (! isset($this->syntax[$i]["help"])) $this->syntax[$i]["help"] = "";
    }
    try {
      /* read config JSON files, if any */
      if (isset($this->configFilename)) {
        if (is_array($this->configFilename)) {
          foreach ($this->configFilename as $f) {
            $this->loadconfig($f);
          }
        } else {
          $this->loadconfig($this->configFilename);
        }
      }
      $this->parseArgv();
      if (! isset($this->opt)) {
        $this->syntax("Syntax error", 1);
      }
      if (isset($this->opt["debug"]) && $this->opt["debug"]) {
        $this->debug($this->opt, "argv");
      }
    } catch (Exception $e) {
      $this->syntax("Syntax check error: " . $e->getMessage(), 1);
    }
    if ($this->opt["help"]) {
      $this->syntax("", 0);
      exit(0);
    }
  }

  /** Check and load a config file, if exists */
  function loadconfig($f) {
    if (! empty($f)) {
      $path = (substr($f, 0, 1) != "/" && substr($f, 0, 2) != "./") ? $_SERVER["HOME"] . "/" . $f : $f;
      if (file_exists($path)) {
        array_push($this->configFilepath, $path);
        if (! ($json = json_decode(file_get_contents($path), true))) {
          throw new Exception("config file $path is not a valid JSON");
        } else {
          foreach ($json as $k => $v) {
            $r = 0;
            foreach ($this->syntax as $p) {
              if ($p["long"] == $k) {
                $this->opt[$k] = $v;
                $this->optmeta[$k] = ["from" => $path];
                $r = 1;
              } else if ($p["short"] == $k) {
                $this->opt[$p["long"]] = $v;
                $this->optmeta[$p["long"]] = ["from" => $path];
                $r = 1;
              }
            }
            if ($r == 0) new Exception("config file $path contains invalid argument \"$k\"");
          }
        }
      }
    }
  }

  /** Show syntax (and error) */
  function syntax($err = "", $exit = -1) {
    $line = "syntax: " . $this->scriptFilename;
    foreach ($this->syntax as $p) {
      $line .= " " . ($p["required"] ? "{" : "[") . ($p["short"] != "" ? "-" . $p["short"] . "|" : "") . "--" . $p["long"] . ($p["type"] != "flag" ? "=" . $p["type"] : "") . ($p["required"] ? "}" : "]");
    }
    print "$line\n";
    if (count($this->configFilepath) > 0) {
      printf("config read from: %s\n", join(", ", $this->configFilepath));
    }
    printf("    %-18s %-7s %s\n", "Argument----------", "Type----", "Comment, requirement, default, etc----------");
    foreach ($this->syntax as $p) {
      $fromconfig = (isset($this->optmeta[$p["long"]]) ? ", config:\"" . $this->optmeta[$p["long"]]["from"] . "\"" : "");
      printf("    %s  %-14s %-7s  %s (%s)\n", $p["short"] != "" ? "-" . $p["short"] : "  ", $p["long"] != "" ? "--" . $p["long"] : "", substr($p["type"], 0, 1) == "/" ? "regexp" : $p["type"], $p["help"], ($p["required"] ? "required$fromconfig" : "optional$fromconfig") . (isset($p["default"]) ? ", default:" . json_encode($p["default"]) : ""));
    }
    if ($err != "") {
      print "$err\n";
    }
    if ($exit != -1) {
      exit($exit);
    }
  }

  /** Alternative for $this->opt[$var] */
  function opt($var) {
    return(isset($this->opt[$var]) ? $this->opt[$var] : null);
  }

  /** HTTP GET something (could be a local file) */
  function webget($url, $headers = "", $limit = 65536) {
    /* use key 'http' even if you send the request to https://... */
    $options = array(
      'http' => array(
        'header'  => $headers,
        'method'  => 'GET'
      )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context, 0, $limit);
    return($result);
  }

  /** Print something out if either verbose or debug is set */
  function msg($str) {
    if ($this->opt["verbose"] || $this->opt["debug"]) {
      print "$str\n";
    }
    if (isset($this->log)) {
      $this->log->log($str);
    }
  }

  /** Report an error */
  function error($str) {
    print "$str\n";
    if (isset($this->log)) {
      $this->log->log($str);
    }
  }

  /** Prints a debug message */
  function debug($x, $prefix = "") {
    print "debug" . ($prefix != "" ? ":$prefix> " : "> ") . (is_array($x) ? json_encode($x) : $x) . "\n";
  }

  /** Prints an array of debug messages */
  function debugArray($x, $prefix = "") {
    foreach ($x as $line) {
      $this->debug($line, $prefix);
    }
  }

  /** Starts a timer (stores the current microtime to calculate later) */
  function startTimer() {
    list($this->microtime_sec, $this->microtime_usec) = explode(" ",microtime());
  }

  /** Return the elapsed time */
  function stopTimer() {
    list($sec, $usec) = explode(" ",microtime());
    return(($sec - $this->microtime_sec) + ($usec - $this->microtime_usec));
  }

  /** Runs an external sub-process and returns all the output in an array */
  function runSubProcess($cmd, $limit = 8192) {
    $handle = popen($cmd, "r");
    $out = [];
    $n = 0;
    while ($n < $limit && $line = fgets($handle, 8192)) {
      $line = preg_replace_callback('/([\0-\x1f\x7e-\xff])/', function($m) {
        return(ord($m[1]) < 32 ? sprintf("^%c", ord($m[1]) + 64) : sprintf("(%02x)", ord($m[1])));
      }, $line);
      $out[$n ++] = $line;
    }
    pclose($handle);
    return($out);
  }
}

# vim: expandtab:ai:ts=2:sw=2
?>
