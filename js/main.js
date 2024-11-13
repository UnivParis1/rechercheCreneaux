requirejs.config({
    baseUrl: "js/",
    'paths': {
        'jquery' :'../node_modules/jquery/dist/jquery.min',
        'bootstrap' : '../node_modules/bootstrap/dist/js/bootstrap.bundle.min',
        'nouislider' : '../node_modules/nouislider/dist/nouislider.min',
        'moment' : '../node_modules/moment/min/moment-with-locales.min',
        'step' : 'lib/step/step'
    },
    'shims' : {
        'jquery': {
	    exports: '$'
        },
        'bootstrap': {
            deps: ['jquery'],
            exports: '$'
        },
        'autocompleteUser': {
            exports: '$'
        },
        'nouislider': {
            exports: 'noUiSlider'
        },
        'slider': {
            deps: ['nouislider']
        }
    },
    config: {
        step: {
            steps: [
                ['jquery'],
                ['bootstrap']
            ]
        }
    },
// out: tentative d'optimisation de requirejs
    out: 'rjsopt.js'
}
);

requirejs(['form', 'evento']);

