import '../scss/styles.scss';

// babel s'occupe de transformer requirejs en commonJS, les librairies suivantes sont bien intégrés au code

import * as bootstrap from 'bootstrap'
import moment from 'moment';
import validator from 'validator';

require(['./form.js', './agendasDistants.js', './evento.js']);

