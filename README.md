# Javascript+PHP-based Log View - Backward or Forward Direction
A responsive, dependable, and light-weight web-based structure to view your web or system logs. <br>
I wrote this recently to monitor the activities of my public web hosting and my servers.
Release it to the wild. <br>
Version 1.0.0


### What is Log File?
Text data file that records historical events or messages of the OS, an application...  and usually it has a structured format (ie: separated by delimeters).


### Technology Used
   + A native javascript class for fetching, paging, sliding, searching, sorting, filtering ...  
   + PHP server engine: read log file, chunk position/size recording.
   + CSS5 for animation.
   + SVG for some graphic.


### Application Method and Flows
 Read and display log files (of any size) in chunks, avoiding issues with server or browser constraints.<br>
 Currently it is used to view live log file > 70MB.
   #### Flows
   +  Find positions and sizes of the chunks in forward direction (older lines first). Position starts at a known new line (LF).
   +  Display page slide contains chunk position and size.
   +  Display chapter for group of pages.
   +  Backward direction view: display newer (at bottom of file) lines first.


### Front-end Selections
  + Log file to view.<br>
  + Forward or backward direction view. Default is backward.<br>
  + Navigate chapter (bullet) and slide  page on mouseover, 1st page of the chapter is displayed on new selection.<br>
  + Skip/hide lines that match pre-defined patterns.<br>
  + Search box for current file.<br>
  + Result table: sort column by header and filter.<br>


### Usage
 Download the directory app to your web hosting. The hosting must support PHP.<br>
 Copy your log file(s) to directory LOG_DIR. Symlink is OK.<br>
 Define the delimeter and thead format for each log file (modify constant LINE_DELIMETER__TABLE_HEADER).<br>
 Default values are used if none defined.<br>
 
 ##### Delimeter and thead examples:
 ```
      [ABC-2000]
        delim: /\|/
        thead: 15% Timestamp | 20% Name | 10% No  | 45% What | 10% No

      [access_log]
        delim: / \/|\[|\] |" | "/
        thead: 15% Timestamp | 10% IP | 10% Cmd | 25% App | 10% Code/ Size | 30% Browser

      [syslog]
        delim: /-07:00|-08:00|\]|\[|\]|: |- | CMD /
        thead: 15% Timestamp | 20% Func | 10% Code| 10% type | 35% Message

      [php_error]
        delim: /\] \[|\[|\]|: | variable | array /
        thead: 30% Timestamp | 20% What | 30% Cmd | 40% Message | 20% Message
 ```

###  Main Defines
         - LINE_DELIMETER__TABLE_HEADER: delimeter and table head for each file.

         - ODD_LINES:     Odd lines that must be never displayed (e.g.: debug messages).
         - SKIPPED_LINES: Skipped lines (optional click).
         - CLIPPED_TEXTS: Clipped text strings in line.

         - LOG_DIR:       Directory for all the log files. Default: .data
         - LIMIT:         Line display limit on search result. Default: 500.
         - MIN_CHUNK:     Minimum chunk size for the page. Default: 5000.
                          Chunk size increases for larger log file.
                          e.g.: Log file size:  68MB -> chunk size: 352,221

### Snapshot
![image](https://github.com/CoderD1/Log-View---Backward-Forward-Direction/assets/125702814/b0e83406-83d0-41f5-9018-16bdb97521df)


### Demo
https://d1.great-site.net/log-post/


### Included Sample Log Files
         - ABC-2000 (2000-line log with indexes)
         - access-log
         - syslog-log


### Dependencies
     None

### Caveat
    Likely Issues:
     - Log file read permission.
     - Incorrect delimeter and thead format.

    Please leave a comment if you find any issue.

Enjoy!

