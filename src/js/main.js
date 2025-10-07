// Import our custom CSS
import '../scss/styles.scss';

// Import only the Bootstrap components we need
import { Popover } from 'bootstrap';
import { Modal } from 'bootstrap';

var modal = Modal;

// Create an example popover
document.querySelectorAll('[data-bs-toggle="popover"]')
  .forEach(popover => {
    new Popover(popover)
  });

require(['./form.js']);

