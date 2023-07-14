export function decimalFilter(_number){
    const amount = parseFloat(_number) || '';
    const formatter = new Intl.NumberFormat('en-us', {
        style: 'decimal',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    return formatter.format(amount);
}
