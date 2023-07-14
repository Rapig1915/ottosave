import { Line, mixins } from 'vue-chartjs';
import formatAsDecimal from 'vue_root/mixins/formatAsDecimal.mixin.js';

export default {
    extends: Line,
    mixins: [
        mixins.reactiveProp,
        formatAsDecimal
    ],
    props: {
        chartData: {
            type: Object,
            required: true
        },
    },
    data(){
        const vm = this;
        return {
            options: {
                legend: {
                    position: 'bottom',
                    display: true,
                    labels: {
                        fontSize: 16,
                        fontStyle: '600',
                        fontColor: '#0B102A',
                        fontFamily: 'Gibson, sans-serif',
                        usePointStyle: true,
                    },
                    rtl: true
                },
                aspectRatio: 1.3,
                tooltips: {
                    backgroundColor: '#FFF',
                    bodyFontSize: 18,
                    bodyFontStyle: '600',
                    bodyFontColor: '#0B102A',
                    bodyFontFamily: 'Gibson, sans-serif',
                    bodyAlign: 'center',
                    yPadding: 8,
                    xPadding: 15,
                    caretSize: 0,
                    borderWidth: 2,
                    borderColor: '#D6D5D5',
                    shadowOffsetX: 3,
                    shadowOffsetY: 3,
                    shadowBlur: 13,
                    shadowColor: 'rgba(0, 0, 0, 0.13)',
                    displayColors: false,
                    callbacks: {
                        title: function(){},
                        label: function(tooltipItem, data){
                            return vm.formatAsDecimal(tooltipItem.value, true);
                        }
                    }
                },
                scales: {
                    yAxes: [{
                        gridLines: {
                            drawOnChartArea: false,
                            drawTicks: false
                        },
                        ticks: {
                            callback: function(value){
                                return '$' + value;
                            },
                            suggestedMin: 0,
                            suggestedMax: 200,
                            padding: 5,
                            fontFamily: 'Gibson, sans-serif',
                            fontSize: 11,
                            fontColor: '#0B102A'
                        }
                    }],
                    xAxes: [{
                        gridLines: {
                            drawOnChartArea: false,
                            drawTicks: false
                        },
                        ticks: {
                            padding: 5,
                            fontFamily: 'Gibson, sans-serif',
                            fontSize: 11,
                            fontColor: '#0B102A',
                        }
                    }],
                }
            }
        };
    },
    mounted(){
        this.renderChart(this.chartData, this.options);
    }
};
