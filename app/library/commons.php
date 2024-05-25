<?
    /* -----------------------------------------------
            -- LOG VIEWING --
       PHP common classes and functions

       coderd1
       2024-03-06 - initial revision
       ----------------------------------------------- */


    //regular record
    //////////////////////////////////////////////////
    class Record {
    //////////////////////////////////////////////////
        private $data = [];
        public function __set($name, $value) { $this->data[$name] = $value; }
        public function __get($name) { return $this->data[$name]; }
    }// class Record


    // numeric record
    //////////////////////////////////////////////////
    class Summary {
    //////////////////////////////////////////////////
        // number record with added comma to thousands

        private $data = [];
        public function __set($name, $value) { $this->data[$name] = $value; }
        public function __get($name) {
            // add comma to thousands if needed
            if (array_key_exists($name, $this->data))
                return (is_numeric($this->data[$name])) ?
                     number_format($this->data[$name]) : $this->data[$name];
        }
    }// class Summary


    //////////////////////////////////////////////////
    function existing($file, $message='') {
    //////////////////////////////////////////////////
        $uplevel = '../';
        if (file_exists($file)) return $file;
        elseif (file_exists($uplevel . $file)) return $uplevel . $file;
        elseif (file_exists($uplevel . $uplevel . $file)) return $uplevel . $uplevel . $file;
        else die($message);
    };//existing


    //////////////////////////////////////////////////
    function to_array($text, $sep=' ') {
    //////////////////////////////////////////////////
       $text = trim($text);
       // CR detected?
       if (preg_match("/\r?\n/", $text)) $sep = "\n";
       else $text = preg_replace('/\s+/', ' ', $text);
       return array_values(array_filter(array_map('trim', explode($sep, $text))));

    }//to_array


    //////////////////////////////////////////////////
    function error($message) {
    //////////////////////////////////////////////////
        die("<div class='centered'><span class='error'>ERROR:  $message </span></div>");
    }


    //////////////////////////////////////////////////
    function warning($message) {
    //////////////////////////////////////////////////
        die("<div class='centered'><span class='warning'> $message </span></div>");
    }


    //////////////////////////////////////////////////
    function get_files($print=true) {
    //////////////////////////////////////////////////

        $log_dir = existing(LOG_DIR);
        $files = [];
        foreach (new DirectoryIterator($log_dir) as $file) {
            if ($file->isDot() || preg_match('/\.html$|^\./', $file)) continue;
            $files[] = basename($file->getFilename());
        }
        sort($files);
        if (!$print) return $files;

        $html = "<table><tr><td>";

        $LABEL = "<label class='file-item'>__NAME__ \
                    <input type='radio' __CHECKED__ name='radio'>\
                         <span class='file-bullet'></span> </label>";
        $search = to_array('__NAME__ __CHECKED__');
        foreach($files as $key=>$name) {
            $checked = $key == 0 ? 'checked' : ' ';
            $replace = [$name, $checked];
            $html .= str_replace($search, $replace, $LABEL);
        }
        return $html . '</table>';

    } // get_files


    //////////////////////////////////////////////////
    function open_file($file) {
    //////////////////////////////////////////////////
        // return filesize, handle and chunksize

        $log_file = existing(LOG_DIR . $file);
        $filesize = filesize($log_file);

        // flexible chunksize
        $chunksize = ($filesize < MIN_CHUNK) ? MIN_CHUNK :
                              floor(MIN_CHUNK + MIN_CHUNK * (1 + $filesize/1000000));

        if (!($handle = @fopen($log_file, 'r'))) error("Not able to open file $log_file!");

        return [$filesize, $chunksize, $handle];
       
    }// open_file


    //////////////////////////////////////////////////
    function theading($thead) {
    //////////////////////////////////////////////////
        // parse to html thead from line <th width=10%>Name</th>

        if (!$thead) return ' ';

        $temp = [];
        foreach(explode('|', trim($thead)) as $item) {
            if (preg_match('/([\d]+%) (.*)$/', $item, $match)) {
                list($dummy, $percent, $title) = $match;
                $temp[] = sprintf('<th width=%s>%s</th>', $percent, trim($title));
            }
        }
        return implode('', $temp);

    } // theading


    //////////////////////////////////////////////////
    function extracting($filename) {
    //////////////////////////////////////////////////
        // special parse ini routine to extract the file's delimeter and thead
        //  return delimeter and table-thead if name matches a section
        //  otherwise return the default values
        // see LINE_DELIMETER__TABLE_HEADER for ini file syntax

        $lines = to_array(LINE_DELIMETER__TABLE_HEADER);

        $section = $delim = $thead = '';
        $defaults = ['/\|/', '<th >---<th>---<th>'];

        foreach($lines as $line) {
            if (preg_match('/^\[(.*)\]$/', $line, $match)) {
                $section = trim($match[1]);
                // reset
                $delim = $thead = '';
            }

            if ($section) {

                // first keys of the section only, delim must be in regex mode
                if (!$delim && preg_match('/^delim:.*?(\/.*\/)$/', $line, $match))
                    $delim = trim($match[1]);
                if (!$thead && preg_match('/^thead:(.*)$/', $line, $match))
                    $thead = trim($match[1]);

                if ($delim && $thead) {
                    // filename matches section
                    if (preg_match('/'. trim($section) . '/i', $filename))
                        return [$delim, theading($thead), $filename];

                    // save defaults from ini file
                    if (preg_match('/default/i', $section))
                        $defaults = [$delim, theading($thead), $section, $filename];

                    // reset
                    $section = '';
                }
            }
        }
        return $defaults;

    } // extracting


    //////////////////////////////////////////////////
    function title($block) {
    //////////////////////////////////////////////////
        // Title for the table caption (_table_block) or for the page slide (positioning).

        $block = preg_replace('/\[|\]/', '', $block);

        foreach(to_array(MOVE_COLUMNS) as $line) {
            list($search, $replace) = to_array($line, '|||');
            $block = preg_replace($search, $replace, $block);
        }

        if (!$block || strlen($block) < 5) return ' &nbsp; &nbsp; Empty ';
        list($word1, $word2, $word3) = preg_split('/ /', $block. ' &nbsp; &nbsp; ');
        $word2 = preg_replace('/^-.*/', '', $word2);
        if (strlen($word1 . $word2) > 10) $word3 = '';
        return substr("$word1 $word2 $word3", 0, 25);

    } // title


    //////////////////////////////////////////////////
    function set_debug($flag) {
    //////////////////////////////////////////////////

        if ($flag) {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        } else {
            error_reporting(0);
        }
    } // set_debug


    //////////////////////////////////////////////////
    function idebug($message) {
    //////////////////////////////////////////////////
        // Show debug messages as html comments
        //  Firefox/Chrome/Edge: Inspect menu -> Inspector/Elements
        //  Can't tell which html tag these messages are under sometimes.
        
        printf("<!-- >>>  %s  <<< -->", print_r($message, true));

    } // idebug


    //////////////////////////////////////////////////
    function cdebug($message) {
    //////////////////////////////////////////////////

        // Show debug messages as html comments under  D E B U G
        //  Firefox/Chrome/Edge: Inspect menu -> Inspector/Elements
        //  Can't tell which html tag these messages are under sometimes.

        $html = "<div id='ending'> </div>";
        $message = sprintf("
                    <div class='DEBUG'> D E B U G <!-- >>>  %s  <<< -->",
                      print_r($message, true));

        $document= new DOMDocument();
        $document->loadHTML($message);
        $xpath = new DOMXPath($document);
        $body  = $xpath->query('/html/body');
        print $document->saveXml($body->item(0));

    }//cdebug

?>
