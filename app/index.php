 <!DOCTYPE html>
  <html>
  <head>
  <title>&nbsp;&nbsp;&nbsp;Log</title>
  <meta charset='utf-8'>
  <meta name='viewport' content='width=device-width,
              height=device-height,
              initial-scale=1.0,
              maximum-scale=1.0,
              shrink-to-fit=no,
              viewport-fit=cover'>
     <link rel='stylesheet' href='resources/css/style.min.css'>
  </head>
  <body translate='no'>
   <div id='debug'> </div>

   <input type='checkbox' id='input-skip'>
   <label class='label-skip unselectable' for='input-skip'>
       <div id='crossed'></div>
       <div id='checked'></div>
       <div class='skip-title'>Skip lines</div>
   </label>

   <div class='left-column unselectable'>
      <input type='checkbox' id='input-direction'>
      <label class='label-direction' for='input-direction'>
          <div id='arrow'></div>
          <div id='moon'></div>
      </label>
      <div id='file-list'> </div>
      <div id='file-info'> </div>
   </div>

   <div class='chapters unselectable'></div>
   <div class='page-slides unselectable'></div>

   <div class='container' align='center'>
      <input id='search' placeholder='Search in file (3+ chars)..'>
      <div id='direction-note' class='unselectable'>&#8679; backward view</div>
      <div id='result' onscroll='logging.scrolling(this)'></div>
   </div>
   <br>
   <br>

<?
    require_once('library/logging.php');
    $file_list_html = get_files();
?>

    <script src='resources/js/logging.min.js'></script>
    <script>
        const file_list = document.querySelector('#file-list')
        file_list.innerHTML = "<?php print $file_list_html; ?>"

        const opt = {
                         run:       'positions',
                         file:      file_list.childNodes[0].innerText.split("\n")[0],
                         direction: 'backward',
                         skip:      'no',
                     }

        const logging = new Logging(opt)

        document.title = `Log: ${window.location.hostname.split('.')[0]}`
    </script>
  </body>
</html>
