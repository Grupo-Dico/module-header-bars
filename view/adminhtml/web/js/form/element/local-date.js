define([
    'moment',
    'Magento_Ui/js/form/element/date'
], function (moment, DateElement) {
    'use strict';

    return DateElement.extend({
        defaults: {
            localOutputDateTimeFormat: 'YYYY-MM-DD HH:mm:ss'
        },

        /**
         * @param {String} value
         */
        onValueChange: function (value) {
            var shiftedValue = '',
                momentValue;

            if (value) {
                momentValue = this.parseLocalDate(value);
                shiftedValue = momentValue.isValid()
                    ? momentValue.format(this.pickerDateTimeFormat)
                    : String(value);
            }

            if (shiftedValue !== this.shiftedValue()) {
                this.shiftedValue(shiftedValue);
            }
        },

        /**
         * @param {String} shiftedValue
         */
        onShiftedValueChange: function (shiftedValue) {
            var value = '',
                momentValue;

            if (shiftedValue) {
                momentValue = moment(String(shiftedValue), this.pickerDateTimeFormat, true);
                value = momentValue.isValid()
                    ? momentValue.format(this.localOutputDateTimeFormat)
                    : String(shiftedValue);
            }

            if (value !== this.value()) {
                this.value(value);
            }
        },

        /**
         * @param {String} value
         * @returns {moment}
         */
        parseLocalDate: function (value) {
            value = String(value)
                .replace('T', ' ')
                .replace(/\.\d+Z?$/, '')
                .replace(/Z$/, '');

            return moment(value, [
                this.pickerDateTimeFormat,
                this.localOutputDateTimeFormat,
                'YYYY-MM-DD HH:mm:ss'
            ], true);
        }
    });
});
