/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2024 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

define([
    'jquery',
    'mage/url',
    'Magento_Ui/js/modal/alert',
    'pagBankCardJs',
    'mage/translate',
    'PagBank_PaymentMagento/js/model/credit-card-validation/credit-card-number-validator',
    'PagBank_PaymentMagento/js/validation/custom-credit-card-validation',
    'jquery/validate'
], function ($, url, alert, _pagBankCardJs, $t, creditCardNumberValidator) {
    'use strict';

    return (config, element) => {
        // Card holder validation
        $.validator.addMethod(
            'validate-card-holder',
            (value) => {
                return value.length > 0 && /^[a-zA-Z\s]+$/.test(value);
            },
            $t('Please enter a valid card holder name.')
        );

        $.validator.addMethod(
            'validate-expiration-date',
            (_value, el) => {
                const currentDate = new Date();
                const currentMonth = currentDate.getMonth() + 1;
                const currentYear = currentDate.getFullYear() % 100;
                const $form = $(el).closest('form');
                const selectedMonth = parseInt($form.find('#expiration_month').val(), 10);
                const selectedYear = parseInt($form.find('#expiration_year').val(), 10);

                if (selectedYear < currentYear) {
                    return false;
                }

                if (selectedYear === currentYear && selectedMonth < currentMonth) {
                    return false;
                }

                return true;
            },
            $t('Card has expired. Please use a valid expiration date.')
        );

        $(element).validate({
            rules: {
                'card_holder': {
                    required: true,
                    'validate-card-holder': true
                },
                'card_number': {
                    required: true,
                    'validate-card-number-pagbank': true,
                    'validate-card-type-math-pagbank': '#cc_type'
                },
                'security_code': {
                    required: true,
                    'validate-card-cvv-pagbank': true
                },
                'expiration_month': {
                    required: true,
                    'validate-expiration-date': true
                },
                'expiration_year': {
                    required: true,
                    'validate-expiration-date': true
                }
            },
            errorClass: 'mage-error',
            errorElement: 'div',
            focusInvalid: false
        });

        $('#card_number').on('input', function() {
            let value = $(this).val().replace(/\D/g, '');
            const cardType = $('#cc_type').val();

            if (cardType === 'DN') {
                value = value.replace(/(\d{4})(\d{6})(\d{5})/g, '$1 $2 $3');
            } else {
                value = value.replace(/(\d{4})/g, '$1 ').trim();
            }

            $(this).val(value);
        });

        $('#card_number').on('keyup', function() {
            const number = $(this).val().replace(/\s/g, '');
            const result = creditCardNumberValidator(number);

            if (result.card) {
                $('#cc_type').val(result.card.type);
                $('.credit-card-types li').removeClass('_active');
                $(`.credit-card-types li[data-type="${result.card.type}"]`).addClass('_active');
            }
        });

        function encryptCardData() {
            if (!$(element).valid()) {
                return false;
            }

            const cardData = {
                publicKey: config.publicKey,
                holder: $('#card_holder').val(),
                number: $('#card_number').val().replace(/\s/g, ''),
                expMonth: $('#expiration_month').val(),
                expYear: '20' + $('#expiration_year').val(),
                securityCode: $('#security_code').val()
            };

            try {
                const cardPs = window.PagSeguro.encryptCard(cardData);

                if (cardPs.hasErrors) {
                    alert({
                        title: $t('Error'),
                        content: $t('Unable to complete the card validation.')
                    });
                    return false;
                }

                return cardPs.encryptedCard;
            } catch (e) {
                alert({
                    title: $t('Error'),
                    content: $t('An error occurred while processing the card. Please try again.')
                });
                return false;
            }
        }

        $(element).submit((e) => {
            e.preventDefault();

            const encryptedCard = encryptCardData();

            if (!encryptedCard) {
                return;
            }

            const formData = new FormData();

            formData.append('encrypted_card', encryptedCard);

            $.ajax({
                url: url.build('pagbank/customer/savecard'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                showLoader: true,
                success: (response) => {
                    if (response.success) {
                        alert({
                            title: $t('Success'),
                            content: response.message,
                            actions: {
                                always: () => {
                                    location.reload();
                                }
                            }
                        });
                    } else {
                        alert({
                            title: $t('Error'),
                            content: response.message
                        });
                    }
                },
                error: () => {
                    alert({
                        title: $t('Error'),
                        content: $t('An error occurred while saving the card.')
                    });
                }
            });
        });
    };
});
