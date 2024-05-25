    /* -----------------------------------------------
        Native js class for log viewing

         Run flag options to fetch:
             run                                       tag
            ------------------------------------------------
            info:      current file info              #file-info
            positions: positions and size             .page-slides
            search:    show result from search        #result
            block:     click/mouseover to show page   #result
                        (inline event handler)

        coderd1
        2024-03-06 - initial revision
      
       ----------------------------------------------- */

    class Logging {
        'use strict'

        constructor(opt={}) {
             // singleton
            if (Logging._instance) throw new Error('Logging can only be instantiated once!')
            Logging._instance = this

            this.opt = {
                         run:       'positions',
                         file:       '',
                         direction: 'backward',
                         skip:      'no',
                         search:    '',
                       }

            // sync'ed with the passing opt
            Object.assign(this.opt, opt)

            this.tag = {
                         slides: '.page-slides',
                         filter: '.table-filter',
                         bullet: '.chapter-bullet',
                         title:  '.page-title',
                         result: '#result',
                         info:   '#file-info',
                         header: '#page-header',
            }

            this.elem = {
                         chapters:  document.querySelector('.chapters'),
                         slides:    document.querySelector('.page-slides'),
                         items:     document.querySelectorAll('.file-item'),
                         skip:      document.querySelector('#input-skip'),
                         direction: document.querySelector('#input-direction'),
                         note:      document.querySelector('#direction-note'),
                         search:    document.querySelector('#search'),
                         result:    document.querySelector('#result'),
                         crossed:   document.querySelector('#crossed'),
                         checked:   document.querySelector('#checked'),
            }

            // run option with tag
            this.run = {
                         info:      '#file-info',
                         positions: '.page-slides',
                         search:    '#result',
                         block:     '#result',
            }

            // process 1st file on the list
            this.fetch(`run=info&file=${this.opt.file}`)

            // display the page slides
            this.fetch(this.opt)

            this.svg_generating()
            this.elem.crossed.style.display = 'block',
            this.elem.checked.style.display = 'none'

            //document ready
            document.addEventListener('DOMContentLoaded', () => this.listening())

        } // constructor


        /* -----------------------------------------
            scroll: - check element scrollTop value to toggle header's class names.
                    - must use inline event handling.
           ----------------------------------------- */
        scrolling(element) {
            const table_header = document.querySelector(this.tag.header)
            table_header && (table_header.className = element.scrollTop > 25 ?
                                    'dimmer unselectable' : 'unselectable')
        }

    
        /* -----------------------------------------
            Filter the table rows on search input.
             - Filter input box must be the caption of the table.
           ----------------------------------------- */
        filtering(input) {
            // find all rows of the table parent
            const rows = input.parentElement.parentElement.querySelectorAll('tr')
            if (!rows) return
    
            const filter = input.value.trim()
            const regex  = new RegExp(filter, 'i')
        
            // get into each cell (TD) of a row (TR).
            Object.values(rows).forEach((row, index) => {
    
                // keep the headers intact
                if (index == 0) return
    
                // show or hide on match
                const cells = row.querySelectorAll('td')
                row.style.display =
                   Object.values(cells)
                         .some(cell => cell.textContent.match(regex)) ?  '' : 'none'
            })
        } // filtering


        /* -----------------------------------------
            Content for comparing
          ----------------------------------------- */
        content(elem, tag) {
           elem.querySelector(tag).textContent.trim()
        }


        /* -----------------------------------------
            Sort any table on header click.
             No need to specify id or classname.
             See note and call for dynamically created table below.
             Must have <thead> </thead> in table.
             First click may have no effect.
             Sorted by value only.
             Add these css styles to display arrow when click:
               .asc::after  { content: '\25b2' }
               .desc::after { content: '\25bc' }
          ----------------------------------------- */
        sorting(table, header, column) {
            const
                tbody   = table.tBodies[0],
                child   = `td:nth-child(${column + 1})`,
                toggle  = {'asc': 'desc', 'desc': 'asc'},
                current = header.className.split(' ')[0],
                descend = current.match(/desc/) ? 1 : -1
    
            // toggle arrow icon
            table.querySelectorAll('th').forEach(th => th.className = '')
            header.classList.add(toggle[current] || 'asc')
    
            const sorted = Object.values(tbody.querySelectorAll('tr')).sort((a, b) =>
                this.content(a, child) > this.content(b, child) ? descend : descend * -1
            )
    
            // shuffle
            while (tbody.firstChild) tbody.removeChild(tbody.firstChild)
            tbody.append(...sorted)
    
        } // sorting
    
    
        /* -----------------------------------------
            Generic fetch to call js fetch
             Passing own string params (or opt obj) to avoid race condition
              when fetching repeatedly
             url: preset
             tag: derive from run flag of options.body
           ----------------------------------------- */
         fetch(params, url='library/logging.php') {

            const options = {
                   method: 'POST',
                   headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                   body: ''
            }
            // convert obj to string
            options.body = (typeof params).match(/object/) ?
                                Object.keys(params).map(key=>`${key}=${params[key]}`).join('&')
                              : params

            fetch(url, options).then (response => {
                if (response.status !== 200) {
                    console.log(`Error this.fetch -> response = fail ${response.status}`)
                    return
                }
                response.text().then (text => { 

                    const
                        run  = options.body.
                                       match(/run=([A-Za-z]+)/)[1].toLowerCase() || 'positions',
                        tag  = this.run[run],
                        elem = document.querySelector(tag)

                    // populate chapter bullets + page slides or push text to tag
                    tag.match(/slide/) ? this.sliding(text) : elem && (elem.innerHTML = text)

                    // not a search run: clear search value
                    !run.match(/search/) && (this.elem.search.value = '')
                })
            })
            .catch (error => { console.log(`Error fetch -> ${error}`) })
            
        } // fetch

    
        /* -----------------------------------------
             auto clicking the 1st page
           ----------------------------------------- */
        autoclick() {
            const title = document.querySelectorAll(this.tag.title)
            title[0] && title[0].click()
        } // auto click

    
        /* -----------------------------------------
            display chapter bullets and page slides,
             each page slide has  mouseover inline handler for position and size.
           ----------------------------------------- */
        sliding(titles, chunk=10) {
            const
                page  = `<div class='page'>
                            <div class='band'><div class='filled'></div></div>
                            __TITLE__
                         </div>`,
                pages = titles.trim().split('|||').map(title =>page.replace(/__TITLE__/, title))
    
            // populate all chapter bullets, 1st one checked
            this.elem.chapters.innerHTML = [...Array(Math.ceil(pages.length / chunk)).keys()].
                      map(page => `<input type='radio' class='chapter-bullet' name='bullet'>`).
                      join('').replace(/'bullet'/, `'bullet' checked`)
    
            // 1st chapter's slides 
            this.elem.slides.innerHTML = pages.slice(0, chunk).join('')
    
            // other bullets onmouseover event -> display page slides according to chunk slice
            const bullets = document.querySelectorAll(this.tag.bullet)
            bullets && bullets.forEach((bullet, index) => bullet.onmouseover  = () => {
               this.elem.slides.innerHTML = pages.slice(index*chunk, (index + 1)*chunk).join('')
               bullet.checked = true
               this.autoclick()
            })

            this.autoclick()
        }
    
    
        /* -----------------------------------------
            All listenings
              - mouseover focus on search box and filter box
              - keyup for search and filter box
              - click header to sort the table
              - other click events: skip, direction
              - file bullet change
              ...
           ----------------------------------------- */
        listening() {
    
            // skip tag clicked
            const toggle = {'block': 'none', 'none': 'block'}
            this.elem.skip && (this.elem.skip.onclick = event => {
                this.elem.crossed.style.display =
                               toggle[this.elem.crossed.style.display] || 'none'
                this.elem.checked.style.display =
                                toggle[this.elem.checked.style.display] || 'block'

                this.opt.skip = event.target.checked ? 'yes' : 'no'
                this.opt.run  = 'positions'
                this.fetch(this.opt)
            })
    
            // direction checkbox clicked
            this.elem.direction && (this.elem.direction.onclick = event => {
                this.opt.run = 'positions'
                this.opt.direction = !event.target.checked ? 'backward' : 'forward'
                this.fetch(this.opt)
                this.elem.note.innerHTML = this.opt.direction.match(/forward/) ?
                                             '&#8681; forward view' : '&#8679; backward view'
            })
    
            // file list button changed
            Object.values(this.elem.items).forEach(item => {
               item.onclick = () => {
                    event.target.parentElement.firstChild.data &&
                        (this.opt.file = event.target.parentElement.firstChild.data.trim() ||'')

                    // process 1st file on the list
                    this.fetch(`run=info&file=${this.opt.file}`)

                    // display page slides
                    this.opt.run = 'positions'
                    this.fetch(this.opt)
                }
            })
    
            // search box
            this.elem.search && (this.elem.search.onmouseover = () => this.elem.search.focus())
            this.elem.search && (this.elem.search.onkeyup = event => {
                const value = event.target.value.trim()
                value.length == 0 && (this.elem.result.innerHTML = '')

                this.opt.run = 'search'
                this.opt.search = value
                value.length > 2 && this.fetch(this.opt)
            })
    
            // for dynamically created table
            document.querySelector('body').addEventListener('click', event => {
    
                const input = document.querySelector(this.tag.filter)
                input && (input.onkeyup = event => this.filtering(event.target))
                input && (input.onmouseover = () => input.focus())
    
                if (!event.target.parentElement) return
                const table = event.target.parentElement.parentElement.parentElement
                table && table.tBodies && table.querySelectorAll('th').forEach((header, index) => 
                       header.onclick = () => this.sorting(table, header, index)
                )
            })
    
        } // listening

        svg_generating() {
            this.elem.crossed.innerHTML = `
             <svg width='25' height='25'>
              <g class='layer'>
              <path d='m11.41,15.5l-8.6,8.72c-0.31,0.31 -0.66,0.52 -1.04,0.62c0.85,0.25 1.79,0.05 2.46,-0.62l7.95,-7.93c0.17,-0.16 0.44,-0.16 0.59,0l-0.77,-0.79c-0.15,-0.16 -0.42,-0.16 -0.59,0z' fill='#cc3333' id='path1' stroke-width='0.21'/> <path d='m16.29,12.76c-0.17,-0.17 -0.17,-0.42 0,-0.59l7.95,-7.92c0.98,-0.98 0.98,-2.56 0,-3.54c-0.98,-0.98 -2.54,-0.98 -3.52,0l-7.95,7.93c-0.17,0.16 -0.42,0.16 -0.59,0l-7.93,-7.93c-0.98,-0.98 -2.56,-0.98 -3.54,0c-0.08,0.08 -0.15,0.16 -0.21,0.25c0.04,-0.06 0.08,-0.11 0.13,-0.15c0.97,-0.95 2.1,-0.52 3.08,0.46l7.83,8.01c0.16,0.17 0.41,0.17 0.58,0l8.19,-8.16c0.97,-0.97 1.95,-1.06 2.93,-0.08c0.98,0.98 0.81,2 -0.17,2.98l-8.18,8.15c-0.17,0.17 -0.17,0.42 0,0.59c0,0 7.93,7.94 7.91,7.94c0.98,0.98 1.36,2.09 0.38,3.06c-0.98,0.98 -2.21,0.73 -3.19,-0.23l0.71,0.71c0.98,0.98 2.56,0.98 3.54,0c0.98,-0.98 0.98,-2.54 0,-3.51c0,-0.03 -7.95,-7.97 -7.95,-7.97z' fill='#cc3333' id='path2' stroke-width='0.21'/> <path d='m23.18,23.76c0.98,-0.97 0.6,-2.08 -0.38,-3.06c0.02,0 -7.91,-7.94 -7.91,-7.94c-0.17,-0.17 -0.17,-0.42 0,-0.59l8.18,-8.15c0.98,-0.98 1.15,-2 0.17,-2.98c-0.98,-0.98 -1.96,-0.89 -2.93,0.08l-8.19,8.16c-0.17,0.17 -0.42,0.17 -0.58,0l-7.83,-8.01c-0.98,-0.98 -2.11,-1.41 -3.08,-0.46c-0.05,0.04 -0.09,0.09 -0.13,0.15c-0.77,0.98 -0.69,2.37 0.21,3.26l7.93,7.95c0.17,0.17 0.17,0.44 0,0.59l-7.93,7.92c-0.98,0.98 -0.98,2.56 0,3.54c0.31,0.31 0.67,0.52 1.04,0.62c0.38,-0.12 0.75,-0.33 1.04,-0.62l8.6,-8.72c0.17,-0.16 0.44,-0.16 0.59,0l0.77,0.79l7.22,7.24c1,0.96 2.23,1.21 3.21,0.23z' fill='#f44336' id='path3' stroke-width='0.21'/> <g fill='#ffffff' id='g4' transform='matrix(0.208247 0 0 0.208073 3.14722 3.14791)'> <path d='m35.79,37.18c-1.1,-1.6 -32.3,-33.1 -32.3,-33.1c0,0 -2.3,-2.6 -4.7,-0.8c-2.2,1.7 -1.1,4.3 0.1,5.6c1.2,1.3 29,29.7 30.4,30.9c1.3,1.2 3.9,1.4 5.5,0.6c1.6,-0.8 1.8,-2.1 1,-3.2z' id='path4' opacity='0.2'/> <circle cx='-7.01' cy='-0.22' id='circle4' opacity='0.2' r='3.3'/> </g> <path d='m14.16,16.17c0.23,0.33 6.83,6.78 6.83,6.78c0,0 0.48,0.54 0.98,0.15c0.46,-0.36 0.21,-0.9 -0.04,-1.17c-0.25,-0.27 -6.12,-6.09 -6.41,-6.34c-0.28,-0.25 -0.82,-0.27 -1.15,-0.11c-0.33,0.17 -0.37,0.46 -0.21,0.69z' fill='#ffffff' id='path5' opacity='0.2' stroke-width='0.21'/>
              </g>
             </svg>
            `
            this.elem.checked.innerHTML = `
             <svg width='30' height='35'>
              <g class='layer'>
              <path d='m28.54,3.14l-1.92,-2.13c-0.58,-0.64 -1.51,-0.64 -2.09,0l-14.57,16.18c-0.22,0.25 -0.59,0.25 -0.81,0l-4.08,-4.53c-0.58,-0.64 -1.51,-0.64 -2.08,0l-1.92,2.13c-0.58,0.64 -0.58,1.68 0,2.31l7.47,8.29c0.58,0.64 1.51,0.64 2.09,0l17.91,-19.93c0.58,-0.64 0.58,-1.67 0,-2.31z' fill='#1fcfc1' id='path1' stroke-width='0.22'/> <g id='g4' transform='matrix(0.056851 0 0 0.0625845 35.2065 104.665)'> <path d='m-444.56,-1244.75c-9.55,0 -18.54,-3.72 -25.3,-10.48l-129.58,-129.59c-13.95,-13.94 -13.95,-36.64 0,-50.6l33.26,-33.26c13.95,-13.95 36.65,-13.95 50.6,0l70.67,70.67l252.67,-252.68c13.95,-13.94 36.65,-13.94 50.6,0l33.28,33.28l0,0c13.94,13.94 13.95,36.63 0.03,50.58l-310.9,311.57c-6.76,6.77 -15.74,10.5 -25.31,10.51c0,0 -0.01,0 -0.02,0zm-96.32,-214c-3.94,0 -7.87,1.5 -10.87,4.5l-33.26,33.26c-6,6 -6,15.76 0,21.75l129.58,129.58c2.9,2.91 6.77,4.51 10.87,4.51c0.01,0 0.01,0 0.01,0c4.11,0 7.98,-1.61 10.88,-4.52l310.9,-311.57c5.98,-6 5.98,-15.75 -0.01,-21.74l0,0l-33.28,-33.28c-6,-6 -15.76,-6 -21.75,0l-252.87,252.87c-7.85,7.85 -20.62,7.84 -28.47,0l-70.85,-70.86c-3,-3 -6.94,-4.5 -10.88,-4.5z' fill='#4d4d4d' id='path2'/> <path d='m-332.63,-1390.62c-2.61,0 -5.22,-1 -7.21,-2.99c-3.99,-3.98 -3.99,-10.44 0,-14.42l3.19,-3.19c3.98,-3.99 10.44,-3.99 14.42,0c3.99,3.98 3.99,10.44 0,14.42l-3.19,3.19c-1.99,2 -4.6,2.99 -7.21,2.99z' fill='#4d4d4d' id='path3'/> <path d='m-441.15,-1282.1c-2.61,0 -5.22,-0.99 -7.21,-2.99c-3.98,-3.98 -3.98,-10.44 0,-14.42l81.92,-81.93c3.98,-3.98 10.44,-3.98 14.43,0c3.98,3.99 3.98,10.45 0,14.43l-81.93,81.92c-1.99,2 -4.6,2.99 -7.21,2.99z' fill='#4d4d4d' id='path4'/></g>
              </g>
             </svg>
            `
        } // svg_generating

    }// class Logging
