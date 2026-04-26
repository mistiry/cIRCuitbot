# cIRCuitbot Addon Development Guide

Addons extend the bot without touching core code. There are two types:

- **Modules** — invoked explicitly when a user sends a command (e.g. `!seen mistiry`)
- **Triggers** — invoked passively on every message (or messages matching a keyword), with no command required (e.g. detecting a URL and fetching its title)

Both live in the `cIRCuitbot-addons` repository under `modules/` or `triggers/`, each in their own subdirectory.

---

## Directory Structure

### Module
```
modules/
└── myModule/
    ├── module.conf          # active config (gitignored if contains secrets)
    ├── module.conf.example  # template committed to the repo
    └── module.php           # function definitions
```

### Trigger
```
triggers/
└── myTrigger/
    ├── trigger.conf
    ├── trigger.conf.example
    └── trigger.php
```

The directory name is what you put in the bot's `.conf` file under `modules[]` or `triggers[]`.

---

## module.conf

Maps one or more command keywords to a PHP function using `||` as a separator:

```ini
module[] = "keyword||functionName"
```

Multiple keywords can map to the same function (useful when a command has aliases):

```ini
module[] = "op||opCommandUserMode"
module[] = "deop||opCommandUserMode"
module[] = "voice||opCommandUserMode"
module[] = "devoice||opCommandUserMode"
```

The bot calls `functionName($ircdata)` when a user types `!keyword`.

You can add arbitrary custom config values in the same file and read them in your PHP:

```ini
module[] = "weather||getWeather"
apiKey = "your-api-key-here"
defaultCity = "New York"
```

---

## trigger.conf

Same `||` syntax, but the left side is a **match string** — the bot checks whether each incoming message contains that string (case-insensitive):

```ini
trigger[] = "https://||parseURL"
trigger[] = "http://||parseURL"
```

Use `*` to match every single message (run unconditionally):

```ini
trigger[] = "*||sedSubstitute"
```

The bot calls `functionName($ircdata)` for every message that matches. If multiple `trigger[]` entries share the same keyword, each fires independently.

Custom config values work the same as in module.conf:

```ini
trigger[] = "https://||parseURL"
disallowedExtensions[] = "exe"
disallowedExtensions[] = "sh"
```

---

## The `$ircdata` Array

Every module function and trigger function receives `$ircdata` as its only parameter. It contains the parsed IRC line that triggered the call:

| Key | Description | Example |
|---|---|---|
| `usernickname` | The nick of the user who sent the message | `"mistiry"` |
| `userhostname` | The hostname of the user | `"user/mistiry"` |
| `messagetype` | IRC message type | `"PRIVMSG"`, `"JOIN"`, `"QUIT"` |
| `location` | Channel or the bot's nick (for PMs) | `"#reddit-sysadmin"` |
| `fullmessage` | The complete message text, including the command keyword | `"!seen killdash9"` |
| `commandargs` | Everything after the command keyword | `"killdash9"` |
| `messagearray` | Raw explode of the IRC line on `:` | *(internal, rarely needed)* |
| `command` | The raw IRC prefix field | *(internal)* |
| `isbridgemessage` | `"true"` if the message came through the bridge relay | `"true"` or unset |

For triggers that use `*` (match all), `commandargs` will be empty — use `fullmessage` instead.

---

## Available Globals

Declare with `global` inside any function before use:

| Global | Type | Description |
|---|---|---|
| `$config` | array | Everything from the bot's `.conf` file |
| `$dbconnection` | mysqli | Active database connection |
| `$socket` | resource | The raw IRC socket (use `sendPRIVMSG` instead of writing directly where possible) |
| `$ircdata` | array | The current message being processed |
| `$modules` | array | Map of loaded command keywords → function names |
| `$triggers` | array | Map of loaded trigger strings → function names |
| `$ignoredUsers` | array | Hostnames currently on the temporary ignore list |
| `$timerArray` | array | Pending timer callbacks (see Timers below) |
| `$activeActivityArray` | array | Shared state for ongoing activities (e.g. a trivia game in progress) |
| `$timestamp` | string | Timestamp of the current main loop iteration (`Y-m-d H:i:s T`) |

---

## Helper Functions

These are always available — no import needed.

### `sendPRIVMSG($location, $message)`
Sends a message to a channel or user. Automatically splits messages longer than 375 characters at word boundaries. Strips embedded newlines.

```php
sendPRIVMSG($ircdata['location'], "Hello, {$ircdata['usernickname']}!");
```

To reply via PM instead of the channel:
```php
sendPRIVMSG($ircdata['usernickname'], "This is just for you.");
```

### `logEntry($message, $level = 'INFO')`
Writes to the bot's log file. Valid levels: `DEBUG`, `INFO`, `WARN`, `ERROR`. Respects the `log_level` setting in the bot config — entries below the configured level are silently dropped.

```php
logEntry("Looking up user: {$ircdata['commandargs']}", 'DEBUG');
logEntry("API call failed for query: {$query}", 'ERROR');
```

### `getBotFlags($hostname)`
Returns the permission flag for a given hostname. Use this to gate privileged commands.

```php
$flags = getBotFlags($ircdata['userhostname']);
if ($flags !== 'O' && $flags !== 'A') {
    sendPRIVMSG($ircdata['location'], "You don't have permission to do that.");
    return true;
}
```

| Flag | Meaning |
|---|---|
| `O` | Owner |
| `A` | Admin/Operator |
| `U` | Known user |
| `G` | Guest (unknown hostname) |

### `setMode($direction, $mode, $user)`
Sets a channel mode on a user. Only works if the bot has ops. Looks up the user in `known_users` before sending.

```php
setMode('+', 'o', 'mistiry');   // give ops
setMode('-', 'v', 'someone');   // remove voice
```

### `stylizeText($text, $style)`
Wraps text in IRC formatting control characters. Returns the formatted string — does not send anything.

Common styles: `bold`, `underline`, `italic`, `color_red`, `color_green`, `color_yellow`, `color_cyan`, `color_blue`, `color_white`, `color_grey`. Background variants: `bg_red`, `bg_green`, etc.

```php
$label = stylizeText('-- MY MODULE --', 'bold');
$label = stylizeText($label, 'color_green');
sendPRIVMSG($ircdata['location'], $label . " Here is your result.");
```

### `get_string_between($string, $start, $end)`
Extracts the substring between two delimiter strings. Returns empty string if `$start` is not found.

```php
$title = get_string_between($html, '<title>', '</title>');
```

---

## Timers

To schedule a function call after a delay, add an entry to `$timerArray`. The bot checks this array every main loop iteration.

```php
global $timerArray;
$timerArray['myModule_timeout'] = time() + 30;  // fire in 30 seconds
```

The key must be a callable function name. The timer system calls `call_user_func($function, $ircdata)` when the time is reached, then removes the entry. There is no repeat/interval — set it again inside the callback if you need recurrence.

To cancel a pending timer:
```php
unset($timerArray['myModule_timeout']);
```

---

## Reading Your Own Config

Parse your addon's config file inside your function. The path is always predictable:

```php
$conf = parse_ini_file("{$config['addons_dir']}/modules/myModule/module.conf");
$apiKey = $conf['apiKey'];
```

For triggers, swap `modules` for `triggers` and `module.conf` for `trigger.conf`. Since config is re-parsed on each call, changes to the file take effect without restarting the bot.

---

## Bridge Awareness

If bridge support is enabled, messages relayed from Discord will have `$ircdata['isbridgemessage'] == "true"`. The `usernickname` and `userhostname` fields will contain synthetic values derived from the Discord username — they are not real IRC identities. Bridge users will never have a NickServ account.

Most addons don't need to care about this. The bot handles the remapping before your function is called. If your addon does something identity-sensitive (e.g. rate-limiting by hostname), the synthetic hostname is stable per Discord user, so it works correctly without special handling.

---

## Writing a Module — Step by Step

**1.** Create the directory: `modules/myModule/`

**2.** Write `module.conf`:
```ini
module[] = "hello||myModule_hello"
greeting = "Hello there"
```

**3.** Write `module.php`:
```php
<?php
function myModule_hello($ircdata) {
    global $config;

    $conf     = parse_ini_file("{$config['addons_dir']}/modules/myModule/module.conf");
    $greeting = $conf['greeting'] ?? 'Hello';
    $target   = trim($ircdata['commandargs']);

    if (empty($target)) {
        sendPRIVMSG($ircdata['location'], "{$greeting}, {$ircdata['usernickname']}!");
    } else {
        sendPRIVMSG($ircdata['location'], "{$greeting}, {$target}!");
    }
    return true;
}
```

**4.** Add to the bot config:
```ini
modules[] = "myModule"
```

Users can now type `!hello` or `!hello killdash9`.

---

## Writing a Trigger — Step by Step

**1.** Create the directory: `triggers/myTrigger/`

**2.** Write `trigger.conf`:
```ini
trigger[] = "badword||myTrigger_scan"
```

**3.** Write `trigger.php`:
```php
<?php
function myTrigger_scan($ircdata) {
    if (stristr($ircdata['fullmessage'], 'badword')) {
        logEntry("Caught a badword from {$ircdata['usernickname']}", 'INFO');
        sendPRIVMSG($ircdata['location'], "Watch your language, {$ircdata['usernickname']}.");
    }
    return true;
}
```

**4.** Add to the bot config:
```ini
triggers[] = "myTrigger"
```

The function fires on any message containing `badword`. Note: because the bot already matched on `badword` to call your function, the `stristr` check above is redundant — it's shown only to illustrate that you may want to do additional matching inside the function, especially when using `*` to match all messages.

---

## Gotchas and Best Practices

**Don't execute code at include time.** Module and trigger PHP files are `include()`d at startup. Only define functions — don't run any logic at the top level, or it will fire during bot startup before any IRC connection exists.

**Always `return true`** at the end of your functions. It's not strictly required but keeps the calling convention consistent.

**Use `sendPRIVMSG`, not `fputs($socket, ...)`** for normal chat output. `sendPRIVMSG` handles message splitting, strips newlines, and uses the correct `\r\n` line ending. Only reach for the socket directly if you need to send a non-PRIVMSG IRC command.

**Never call `exit()`, `die()`, or `fclose($socket)`** from an addon. These will kill the entire bot process.

**Guard privileged commands with `getBotFlags()`** rather than checking nicknames. Nicknames can be changed or spoofed; hostnames are harder to fake, and the flag system is hostname-based.

**Re-parse your config on each call** (as shown above) rather than caching it in a global. This lets the operator update the config file and have changes take effect without a restart.

**The `location` field is where to reply.** In a channel message it's the channel name. In a PM it's the bot's own nick — so `sendPRIVMSG($ircdata['location'], ...)` always sends the reply to the right place, whether the command came from a channel or a DM.
