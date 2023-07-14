export default {
    methods: {
        formatAsDecimal,
        parseNumber,
        parseDecimal
    }
};

function formatAsDecimal(number, currency, nocommas){
    var numberInStringWithoutCommas = String(number).replace(/[^\d^\.\-]/g, '');

    var options = {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
        useGrouping: true
    };

    if(currency){
        options.style = 'currency';
        options.currency = 'USD';
    }

    if(nocommas){
        options.useGrouping = false;
    }

    var formatter = new Intl.NumberFormat('en-us', options);

    return formatter.format(numberInStringWithoutCommas);
}
function parseNumber(number){
    var numberInStringWithoutCommas = String(number).replace(/[^\d^\.\-]/g, '');
    return (parseFloat(numberInStringWithoutCommas) || 0);
}
function parseDecimal(number = '0'){
    number = parseNumber(number);
    return (parseFloat(Math.round(number * 100) / 100));
}
