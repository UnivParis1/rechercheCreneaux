import $ from 'jquery';
import noUiSlider from 'nouislider';
import {target} from 'nouislider';

// création du slider pour la séléction des plages horaires
$(function() {    
    let slider = (<target>document.getElementById('slider'));

    let selectorPlagesHoraires = "input:hidden[name='plagesHoraires[]']";
    let p1a = (<HTMLInputElement>$(selectorPlagesHoraires)[0]).value.split('-');
    let p2a = (<HTMLInputElement>$(selectorPlagesHoraires)[1]).value.split('-');

    let formatter = function(valueString:string) {
                        if (valueString.search('H30') != -1) {
                            return Number(valueString.replace('H30', '.5'));
                        }
                        if (valueString.search('H00') != -1){
                            return Number(valueString.replace('H00', ''));
                        }
                        if (valueString.search('H') != -1){
                            return Number(valueString.replace('H', ''));
                        }
                        return Number(valueString);
                    };

    let plagesStrings = p1a.concat(p2a);

    let arrayStart = Array();
    for (let plage of plagesStrings) {
        arrayStart.push(formatter(plage));
    }

    let sliderts = noUiSlider.create(slider, {
        start: arrayStart,
        step: 0.5,
        connect: [false, true, false, true, false],
        tooltips: {
            to: function(value) {
                if (value % 1 != 0) {
                    let valueEntier = value - 0.5;
                    return valueEntier + "H30";
                }
                else {
                    return value + "H00";
                }
            },
            from: formatter
        },
        range: {
            'min': [7],
            'max': [21]
        }
    });

    sliderts.on('update', function (arrayValues) {

        if (arrayValues[0] == "NaN")
            return;

        let inputFirst = $(selectorPlagesHoraires).first();
        let inputSecond = $(selectorPlagesHoraires).last();

        let valueStrNew:string;
        let valueComplete:string = "";
        let idx:number = 0;
        let input:JQuery<HTMLElement>;
        for (let value of arrayValues) {
            if (Number(value) % 1 != 0)
                valueStrNew = value.toString().replace('.50', 'H30');
            else
                valueStrNew = value.toString().replace('.00', 'H00');

            if (idx < 2)
                input = inputFirst;
            else
                input = inputSecond;
            if (idx % 2 == 0)
                valueComplete = valueStrNew + "-";
            else
                input.val(valueComplete.concat(valueStrNew));
            idx++;
        }
    });

    let sliderVals = sliderts.get();

    if (Array.isArray(sliderVals)) {
        if (sliderVals.indexOf(2) === sliderVals[3]) {
            $('.noUi-origin:nth-last-child(1),.noUi-origin:nth-last-child(2)').addClass('d-none');
        }
    }

    $("select#duree").on('change', function(event) {
        let valTest = sliderts.get();

        if (Array.isArray(valTest)) {
            // lien avec la sélection de la durée si les plages sont > 4h pour n'avoir qu'une "tranche" de séléction
            const testVal = Number(event.target.nodeValue);
            if (testVal > 240) {

                sliderVals = ((Number(valTest.indexOf(1)) - Number(valTest.indexOf(0))) * 60 < 240) ? valTest : sliderVals;

                let intervalVal = Number(valTest.indexOf(0)) + (testVal / 60);
                sliderts.set([valTest[0], intervalVal, '23.00', '23.00']);

                $('.noUi-origin:nth-last-child(1),.noUi-origin:nth-last-child(2)').addClass('d-none');

            }
            else if(testVal <= 240) {
                $('.noUi-origin:nth-last-child(1),.noUi-origin:nth-last-child(2)').removeClass('d-none');

                if (Array.isArray(sliderVals)) {
                    sliderts.set((new Set(sliderVals).size === sliderVals.length) ? sliderVals: [sliderVals[0],12,14,17]);
                }
            }
        }
    });

});
