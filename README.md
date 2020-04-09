# cmdlineapp.php

Simple class to help implement command-line applications in PHP. Helps with an command-line
application basic tasks like argument parsing, debugging, syntax errors and type checking.

## Features

* Syntax specification of arguments containing type, default values, requirements and help messages

* Much more flexible and powerfull than getopt PHP function call

* Simple to use, just inherit the CmdlineApp class and define the syntax array of arguments

* Msg function to print if verbose

## License

This software is distributed under the MIT license. Please read
LICENSE for information on the software availability and distribution.

## Dependencies

* PHP 5.6

## Instalation

Clone or download this repository and run `sudo make install`.

## Test and Example

Run the test.php program in the src dir.

## Syntax specification

Syntax is specified by declaring a syntax array with all the arguments that can be
supplied to the command-line application. `$this->syntax` is an array of objects
(or hashes), each containing the following variables:

Name | Contents | Required
---- | -------- | --------
long | Long form, used as --arg=value or --arg value | yes
short | Short form (one char) used as -a=val or -a val | no
type | Type (see next table) | yes
required | 1 if the argument is required or 0 for no | no (default is 0, not required)
array | 1 if the argument is an array that accepts multiple entries, otherwise only one is accepted | no (default is 0)
help | Help message shown when the application's syntax is printed to the user | no (default is "")

### Argument Types

The following are the current argument types implemented:

Type | Regex | Example
---- | ----- | -------
string |`^.*$` | duck
int | `^-?\d+$` | 13
float | `^-?\d+(\.\d+)?$` | 0.55
dateymd | `^\d\d\d\d-\d\d-\d\d$` | 1970-01-01
datedmy | `/^\d\d\/\d\d\/\d\d\d\d$/` | 01/02/1970
flag | `^true$\|^false$` | true
url | `/^(?:http(s)?:\/\/)?[\w.-]+(?:\.[\w\.-]+)+[\w\-\._~:/?#[\]@!\$&\(\)\*\+,;=.]+$/` | http://duckduckgo.com

### Syntax example (as shown in the test.php program)

```php
  var $syntax = [
    ["long" => "string", "short" => "s", "type" => "string", "required" => 1, "help" => "A required string"],
    ["long" => "int", "short" => "i", "type" => "integer", "array" => 1, "required" => 0, "help" => "An integer"],
    ["long" => "float", "short" => "f", "type" => "float", "required" => 0, "help" => "A float"],
    ["long" => "date", "short" => "d", "type" => "dateymd", "required" => 0, "default" => "1970-01-01", "help" => "Any date"],
    ["long" => "help", "short" => "h", "type" => "flag", "default" => false, "help" => "Show the command's syntax"],
    ["long" => "verbose", "short" => "v", "type" => "flag", "default" => false, "help" => "Verbose mode, show more output"],
    ["long" => "debug", "type" => "flag", "default" => false, "help" => "Debug mode, show development data"]
  ];
```

## TODO

Improve testing, more examples

## Author

CmdlineApp class was developed by Rodrigo Antunes rorabr@github.com

