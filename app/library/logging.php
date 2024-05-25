<?

    /* -----------------------------------------------
            -- LOG VIEWING --

       PHP API routines called from Logging.fetch.

       -------
       coderd1
       2024-03-06 - initial revision
       ----------------------------------------------- */


    require_once('commons.php');
    require_once('defines.php');

    extract($_POST);
    if (count($_POST) < 2 || !$run) return ' ';

    switch ($run) {
        case 'info':      _file_info($file); break;
        case 'positions': _positioning($file, $direction, $skip); break;
        case 'search':    _searching($file, $direction, $skip,  $search); break;
        case 'block':     _extract_block($file, $size, $direction, $skip, $index, $position);
                           break;
        default: break;
    }//switch


    //////////////////////////////////////////////////
    function _file_info($file) {
    //////////////////////////////////////////////////
        // return obj: filename, filesize and chunksize

        $log_file = existing(LOG_DIR . $file);
        $size = filesize($log_file);

        // flexible chunksize
        $chunk = ($size < MIN_CHUNK) ? MIN_CHUNK :
                        floor(MIN_CHUNK + MIN_CHUNK * (1 + $size/1000000));

        $size = ($size < 1000000) ? number_format($size/1000) . 'KB'
                                  : number_format($size/1000000) . 'MB';

        printf ("<pre>Size:  %s\nChunk: %s</pre>", $size, number_format($chunk));

    } // file_info


    //////////////////////////////////////////////////
    function _table_block($logfile, $direction, $skip, $index, $block, $time0=0) {
    //////////////////////////////////////////////////
        // process and return the table block

        if (!trim($block)) warning(' .... ');

        list($delimeter, $thead)  = extracting($logfile);

        $cell_count = substr_count($thead, '<th');
        $skips      = (preg_match('/yes/i', $skip)) ?
                      '/' . implode('|', array_merge(ODD_LINES, SKIPPED_LINES)) . '/' :
                      '/' . implode('|', ODD_LINES) . '/';

        $record = new Summary();
        $record->t_bytes = strlen($block);

        $rows = [];
        $last = '$%^$%^%^';
        $td   = '<td>';


        foreach(preg_split("/\r\n|\n|\r/", $block) as $row) {

            // ignore repeated line in sequence, odd lines and optionally skipped lines
            if ( !trim($row) || ($last == trim($row)) || preg_match($skips, $row) )
                 continue;

            // clip texts from line
            $row = preg_replace(CLIPPED_TEXTS, '', $row);

            foreach(to_array(MOVE_COLUMNS) as $line) {
                list($search, $replace) = to_array($line, '|||');
                $row = preg_replace($search, $replace, $row);
            }

            $row = str_replace(PREEMPT[0], PREEMPT[1], $row);
              
            $row = $td . preg_replace($delimeter, $td, $row, $cell_count);
            $row = preg_replace('/<td> <td>|<td><td>/',$td, $row);

            $cells = substr_count($row, $td);

            //  fill row to full with empty cells 
            if ($cells < $cell_count) $row .= str_repeat($td, $cell_count - $cells);

            $rows[] = $row;
            $last   = trim($row);

        } // foreach

        $record->d_bytes = strlen(implode('', $rows));
        $record->d_lines = count($rows);
        $record->t_lines = substr_count($block, "\n");

        if (preg_match('/backward/i', $direction)) $rows = array_reverse($rows);

        // Skim the block for the title.
        // Search Result: no need to display title.
        // Display empty log title if only there are only odd or skipped lines.
        $title   = (!preg_match('/search/i', $index)) ?
                   title(substr(implode("\n", $rows), 0, 100)) : '';
        $title   = (!$rows) ? 'Empty (skipped/odd lines)' : $title;

        $elapsed = ($time0) ?
           sprintf("<div class='elapsed'>(elapsed: %6.4f sec)</div>", microtime(true) - $time0)
           : '';

        $summary = implode('<td>',
                  [$record->d_bytes, $record->t_bytes, $record->d_lines, $record->t_lines]);

        $header  = sprintf('%s %s %s', $index, $title, $elapsed);
        $table   = '<tr>' . implode('<tr>', $rows);
        $search  = to_array('__HEADER__ __THEAD__ __ROWS__ __SUMMARY__');
        $replace = [$header, $thead, $table, $summary];

        $result_table = "
          <div id='page-header' class='unselectable'>__HEADER__</div>
            <table class='mytable'>
             <caption> <input class='table-filter' placeholder='Filter ...'> </caption>
             <thead> <tr> __THEAD__ </tr> </thead>
             __ROWS__
            </table>
            <table class='sum-table'>
               <tr> <th colspan=2> bytes <th colspan=2> lines
               <tr> <td> displayed<td>total <td> displayed <td>total
               <tr> <td> __SUMMARY__
            </table>
            <br>
        ";

        return str_replace($search, $replace, $result_table);

    }// table_block


    //////////////////////////////////////////////////
    function _extract_block($logfile, $size, $direction, $skip, $index, $position) {
    //////////////////////////////////////////////////

        $time0 = microtime(true);
        list($filesize, $dummy, $handle) = open_file($logfile);

        if ($size > 0) {
            fseek($handle, $position);
            $block = fread($handle, $size);
            print _table_block($logfile, $direction, $skip, $index . ':', $block, $time0);
        }

        fclose($handle);

    } // extract_block

 
    //////////////////////////////////////////////////
    function _searching($logfile, $direction, $skip, $search) {
    //////////////////////////////////////////////////

        list($filesize, $chunksize, $handle) = open_file($logfile);

        $time0 = microtime(true);
        $extra = '';
        $rows  = [];

        // Special chars complied for regex
        $pattern = to_array('/ % - \+ [ ] ( )');
        $replace = to_array('\/ \% \- \+ \[ \] \( \)');
        $search = str_replace($pattern, $replace, $search);

        // Search backward or forward in file, block by block.
        // There is a view LIMIT, ie: missing result.
        // Line result in backward viewing is not in order.

        if (preg_match('/backward/i', $direction)) {
            // BACKWARD
            for ($position = $filesize - $chunksize; $position >= 0; $position -= $chunksize) {

                if ($position < $chunksize) {
                    $chunksize += $position;
                    $position = 0;
                }

                fseek($handle, $position);
                $block = fread($handle, $chunksize) . $extra;

                if ($position !== 0) {
                    // 'extra': chars before first CR, append to next block.
                    $extra = strstr($block, "\n", true);
                    $block = strstr($block, "\n");
                }

                foreach(preg_split("/\r\n|\n|\r/", $block) as $row)
                    if (preg_match("/$search/i", $row) && count($rows) <= LIMIT) $rows[] = $row;

            }
            
        } else {
            //FORWARD
            for ($position = 0; $position < $filesize; $position += $chunksize) {
  
                if ($position > $filesize - $chunksize) {
                    $position  = $filesize - $chunksize;
                    $chunksize = $filesize - $position; 
                }
  
                fseek($handle, $position);
                $block =  $extra . fread($handle, $chunksize);

                // extra: text after last CR, affix to next block.
                if ($position < $filesize) $extra = strrchr($block, "\n");

                foreach(preg_split("/\r\n|\n|\r/", $block) as $row)
                    if (preg_match("/$search/i", $row) && count($rows) <= LIMIT) $rows[] = $row;
            }
        }

        fclose($handle);
        print _table_block($logfile, $direction, $skip,
                              'Search Result', implode("\n", $rows), $time0);

    }// _searching


    //////////////////////////////////////////////////
    function _positioning($logfile, $direction, $skip) {
    //////////////////////////////////////////////////

        // Forward: view lines top to bottom.
        // Backward: view lines bottom to top.
        //
        // Positioning for forward viewing:
        //  Loop through the file to record position/size/title for pages:
        //  - chunk size: the loop interval, varies by file size.
        //  - extra: text after last new line of a block, likely it is the incomplete line.
        //  - position: current loop index minus the length of the past 'extra'
        //              so it starts with a new line.
        //  - read a chunk size block from this position.
        //  - find the current 'extra'.
        //  - size: block length minus current 'extra' length.
        //  - title for forward view: part of 1st good line of the block.
        //  - title for backward view: part of last good line of the block.
        //
        // Backward view records: reverse sort the forward ones, position as sort index.

        list($filesize, $chunksize, $handle) = open_file($logfile);

        $time0     = microtime(true);
        $backward  = preg_match('/backward/i', $direction);
        $extra     = '';
        $positions = [];
        $skips     = (preg_match('/yes/i', $skip)) ?
                      '/' . implode('|', array_merge(ODD_LINES, SKIPPED_LINES)) . '/' :
                      '/' . implode('|', ODD_LINES) . '/';

        // forward read
        for ($position = 0; $position < $filesize; $position += $chunksize) {
            $record = new Record();
            $position -= strlen($extra);
            // reverse sorting uses the position index
            $record->position = $position;
    
            fseek($handle, $position);
            $block  = fread($handle, $chunksize);
            $cutoff = strrpos($block, "\n") + 1;
            $extra  = substr($block, $cutoff);
            $record->size  = strlen($block) - strlen($extra);

            $line = '&nbsp;&nbsp;---';

            // use block to find the title. If use no title, skip these steps.
            if ($backward) {
                // backward block = forward block without end extra + the lines reversed
                $block = substr($block, 0, $cutoff - 1);
                $rows  = preg_split("/\r\n|\n|\r/", $block);
                $block = implode("\n", array_reverse($rows));
            }
            foreach (preg_split("/\r\n|\n|\r/", $block) as $line) {
                // skim 1st good line for title
                if (preg_match($skips, $line)) continue;
                if ($line) break;
            }

            $record->title = title($line);
            $positions[] = $record;
        }

        // reverse sorting -> backward
        if ($backward) rsort($positions);

        fclose($handle);

        // setup js logging.fetch inline event handling for each slide
        // need onclick as well for autoclick
        $fetch = "\"logging.fetch(`__PARAM__`)\"";
        $TITLE = "<h3 class='page-title' onclick=$fetch onmouseover=$fetch>
                     &nbsp;&nbsp;__INDEX__<br>__TITLE__
                  </h3>";

        $search = to_array('__PARAM__ __INDEX__ __TITLE__');

        $slides = [];
        $count = count($positions);
        
        foreach($positions as $index=>$obj) {

            // index is reversed for backward
            $key = ($backward) ? $count - $index : $index + 1;

            $opt['index']     = $key;
            $opt['run']       = 'block';
            $opt['file']      = $logfile;
            $opt['direction'] = $direction;
            $opt['skip']      = $skip;
            $opt['title']     = sprintf("'%s'", $obj->title);
            $opt['position']  = $obj->position;
            $opt['size']      = $obj->size;

            $param    = http_build_query($opt);
            $param    = urldecode($param); 
            $param    = str_replace('&amp;', '&', $param);
            $replace  = [$param, $key, $obj->title];
            $slides[] = str_replace($search, $replace, $TITLE);
        }

        print implode('|||', $slides);

    } // _positioning
?>
