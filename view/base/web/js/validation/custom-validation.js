/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

define(['jquery'], function ($) {
    'use strict';

    return function () {

        /**
         * Invalidate Common CNPJ
         * @param {String} value
         * @return {boolean}
         */
        function getInvalidateCommonCNPJ(value) {
            let common = {
                '00000000000000': true,
                '11111111111111': true,
                '22222222222222': true,
                '33333333333333': true,
                '44444444444444': true,
                '55555555555555': true,
                '66666666666666': true,
                '77777777777777': true,
                '88888888888888': true,
                '99999999999999': true
            };

            return common.hasOwnProperty(value);
        }

        /**
         * Invalidate Common CPF
         * @param {String} value
         * @return {boolean}
         */
        function getInvalidateCommonCPF(value) {
            let common = {
                '00000000000': true,
                '11111111111': true,
                '22222222222': true,
                '33333333333': true,
                '44444444444': true,
                '55555555555': true,
                '66666666666': true,
                '77777777777': true,
                '88888888888': true,
                '99999999999': true
            };

            return common.hasOwnProperty(value);
        }

        /**
         * Validate CPF
         *
         * @param {String} cpf - CPF number
         * @return {Boolean}
         */
        function validateCPF(cpf) {

            if (cpf.length !== 11) {
                return false;
            }

            if (getInvalidateCommonCPF(cpf)) {
                return false;
            }

            let add = 0,
                i,
                j,
                rev;

            for (i = 0; i < 9; i++) {
                add += parseInt(cpf.charAt(i), 10) * (10 - i);
            }

            rev = 11 - add % 11;
            if (rev === 10 || rev === 11) {
                rev = 0;
            }
            if (rev !== parseInt(cpf.charAt(9), 10)) {
                return false;
            }

            add = 0;
            for (j = 0; j < 10; j++) {
                add += parseInt(cpf.charAt(j), 10) * (11 - j);
            }

            rev = 11 - add % 11;

            if (rev === 10 || rev === 11) {
                rev = 0;
            }

            if (rev !== parseInt(cpf.charAt(10), 10)) {
                return false;
            }

            return true;
        }

        /**
         * Validate CNPJ
         *
         * @param {String} cnpj - CNPJ number
         * @return {Boolean}
         */
        function validateCNPJ(cnpj) {
            var tamanho = cnpj.length - 2,
                numeros = cnpj.substring(0, tamanho),
                digitos = cnpj.substring(tamanho),
                soma = 0,
                pos = tamanho - 7;

            if (cnpj.length !== 14) {
                return false;
            }

            if (getInvalidateCommonCNPJ(cnpj)) {
                return false;
            }

            let i,
                j,
                resultado;

            for (i = tamanho; i >= 1; i--) {
                soma += numeros.charAt(tamanho - i) * pos--;
                if (pos < 2) {
                    pos = 9;
                }
            }
            resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;

            if (resultado !== parseInt(digitos.charAt(0), 10)) {
                return false;
            }

            tamanho += 1;
            numeros = cnpj.substring(0, tamanho);
            soma = 0;
            pos = tamanho - 7;
            for (j = tamanho; j >= 1; j--) {
                soma += numeros.charAt(tamanho - j) * pos--;
                if (pos < 2) {
                    pos = 9;
                }
            }
            resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;

            if (resultado !== parseInt(digitos.charAt(1), 10)) {
                return false;
            }

            return true;
        }

        /**
         * Add Validation Tax Id
         */
        $.validator.addMethod(
            'pagbank-validate-tax-id',

                /**
                 * Validate Tax Id.
                 *
                 * @param {String} value - Tax Id number
                 * @return {Boolean}
                 */
                function (value) {
                    var documment = value.replace(/[^\d]+/g, '');

                    if (documment.length === 14) {
                        return validateCNPJ(documment);
                    }

                    if (documment.length === 11) {
                        return validateCPF(documment);
                    }

                    return false;
                },
            $.mage.__('Please provide a valid CPF/CNPJ.')
        );
    };
});
