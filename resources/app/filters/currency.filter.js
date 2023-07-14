export function currencyFilter(_number){
    _number = String(_number).replace(/[^\d^\.\-]/g, '');
    const amount = parseFloat(_number) || '';
    const formatter = new Intl.NumberFormat('en-us', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    const formattedCurrency = formatter.format(amount);
    const styledCurrency = formattedCurrency.split('$').join(' $ ');
    return styledCurrency;
}
