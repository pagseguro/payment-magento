<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Gateway\Validator;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;

/**
 * Class General Response Validator - Response flow to define validation codes.
 */
class GeneralResponseValidator extends AbstractValidator
{
    /**
     * Validate.
     *
     * @param array $validationSubject
     *
     * @return ResultInterface
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $response = SubjectReader::readResponse($validationSubject);

        $isValid = $response['RESULT_CODE'];

        $errorCodes = [];

        $errorMessages = [];

        if (!$isValid) {
            if (isset($response['error_messages'])) {
                $errorCodes[] = $response['error_messages'][0]['code'];
                if (isset($response['error_messages'][0]['description'])) {
                    $errorMessages[] = $response['error_messages'][0]['description'];
                }
            }

            if (isset($response['payment_response'])) {
                $errorCodes[] = $response['payment_response']['code'];
                $errorMessages[] = $response['payment_response']['message'];
            }

            if (isset($response['charges'][0]['payment_response'])) {
                $errorCodes[] = $response['charges'][0]['payment_response']['code'];
                $errorMessages[] = $response['charges'][0]['payment_response']['message'];
            }
        }

        return $this->createResult($isValid, $errorMessages, $errorCodes);
    }
}
