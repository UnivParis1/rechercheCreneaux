import '../scss/styles.scss';

// babel s'occupe de transformer requirejs en commonJS, les librairies suivantes sont bien intégrés au code

import * as bootstrap from 'bootstrap'
import moment from 'moment';
import onChange from 'on-change';

require(['./form.js', './calext.js', './evento.js']);

