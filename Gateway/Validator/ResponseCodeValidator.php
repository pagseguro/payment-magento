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

use InvalidArgumentException;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;

/**
 * Class ResponseCodeValidator - Response flow to define validation codes.
 */
class ResponseCodeValidator extends AbstractValidator
{
    /**
     * @var string
     */
    public const RESULT_CODE = 'RESULT_CODE';

    /**
     * Validation.
     *
     * @param array $validationSubject
     *
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        if (!isset($validationSubject['response']) || !is_array($validationSubject['response'])) {
            throw new InvalidArgumentException('Response does not exist');
        }

        $response = $validationSubject['response'];

        if ($this->isSuccessfulTransaction($response)) {
            return $this->createResult(
                true,
                []
            );
        }

        return $this->createResult(
            false,
            [__('Gateway rejected the transaction.')]
        );
    }

    /**
     * Is Successful Transaction.
     *
     * @param array $response
     *
     * @return bool
     */
    private function isSuccessfulTransaction(array $response)
    {
        return $response[self::RESULT_CODE];
    }
}
