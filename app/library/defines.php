<?

    /* -----------------------------------------------
            -- LOG VIEWING --
                 Defines
              
       -------
       coderd1
       2024-03-06 - initial revision

       Note:
           This is the only file that needs changes.
           Single quotes are used for string literal.

       ----------------------------------------------- */

    define('DEBUG', 1);
    set_debug(DEBUG);

    define('LIMIT',     500);
    define('MIN_CHUNK', 5000);

    define('LOG_DIR',  '.data/');

     /*
      Delimeter and header for file
      Format:
        [filename]
            delim: [regex delimeter]
            thead: [th-width text | th-width text | ...]

       indenting is not necessary.
     */
    define('LINE_DELIMETER__TABLE_HEADER', '

      [ABC-2000]
        delim: /\|/
        thead: 15% Timestamp | 20% Name | 10% #  | 45% What | 10% #

      [xyz-2000]
        delim: /\|/
        thead: 15% Timestamp | 20% Name | 10% #  | 45% What | 10% #

      [access_log]
        delim: / \/|\[|\] |" | "/
        thead: 15% Timestamp | 10% IP   | 10% Cmd | 25% App | 10% Code/ Size | 30% Browser

      [syslog]
        delim: /-07:00|-08:00|\]|\[|\]|: |- | CMD /
        thead: 15% Timestamp | 20% Func | 10% Code| 10% type | 35% Message

      [php_error]
        delim: /\] \[|\[|\]|: | variable | array /
        thead: 30% Timestamp | 20% What | 30% Cmd | 40% Message | 20% Message

      [error_log]
        delim: /\] \[|\[|\]|: |\(|\)|, referer/
        thead: 25% Timestamp | 12% Cmd | 12% Pid | 15% Client | 20% Error | 20% Refer | 20% Message | 20% url

      [ssl_request]
        delim: /\] \[|\[|\]| "|" | TLSv/
        thead: 18% Timestamp | 15% IP | 25% TLS | 30% CMD | 10% Size

      [default]
         delim: /\|| \/|\[|\]|"| "-"|: | \+\+ /
         thead: 15% Timestamp | 18% IP | 10% Cmd | 20% What | 10% Code | 15% Msg1 | 20% Msg2 | 10% Msg3

      [KP5]
        delim: /\|/
        thead: 18% Timestamp | 20% IP | 10% Who | 15% What | 30% Log | 15% Status

      [ws20k]
        delim: /\|/
        thead:  23% Timestamp | 10% No | 20% IP | 20% Cmd | 13% Code | 30% Site | 10% Bog | 20% Browser

     ');


    // ODD_LINES and SKIPPED_LINES are array so they can be merged.
    // odd lines: Must ignore lines with odd texts like these:
    //   Stack trace:
    //   #0
    //   thrown in

    $odd_lines = '
                     - me
                     logging.php
                     #[\d]
                     thrown in
                     Stack trace
                     missing terminating
                     not found
                 ';

    // Array
    define('ODD_LINES', to_array($odd_lines));

    // optional skip (use skip button)
    $skipped_lines = '
                     SELECT
                     Found block rdd
                     is not a duplicate
                     UP\/b
                     ';
    // Array
    define('SKIPPED_LINES', to_array($skipped_lines));

    // Regex string: clip these unwanted texts from line
    $clipped_texts = '
                     ^\[
                     \[FBAN.*\/80\]
                     \[47.*\/80\]
                     ,\'.2y.10..*\',
                     HTTP\/1\.1
                     -0400
                     -0700
                     -0800
                     \+0000
                     - -
                     -" "
                     "$
                     url.*=
                     \.NET CLR.*
                     ';
    define('CLIPPED_TEXTS', '/' . implode('|', to_array($clipped_texts)) . '/');

    // special moves:
    //  - in some cases, push ip4/ipv6 address to 2nd column and move date-time column to 1st.
    define('MOVE_COLUMNS', '
       /^([\d\.]+){1,4} (.*?) "/                      ||| $2 $1 "
       /^([\da-fA-F:]+){1,7} (.*?) "/                 ||| $2 $1 "
       ');


    // other replaces to preempt some delimeter values (e.g.: )
    // 
    define('PREEMPT', [ to_array(':notice :warn'), to_array('-notice -warn') ]);

?>
