<?php

namespace Ess\M2ePro\Model\Amazon\ProductType\Validator;

class NumberValidator implements ValidatorInterface
{
    /** @var string */
    private $fieldTitle = '';
    /** @var bool */
    private $isRequired = false;
    /** @var int */
    private $maximum = PHP_INT_MAX;
    /** @var int  */
    private $minimum = PHP_INT_MIN;
    /** @var array */
    private $errors = [];

    /**
     * @param mixed $value
     */
    public function validate($value): bool
    {
        $this->errors = [];

        if ($value === null || $value === '') {
            $this->errors[] = sprintf(
                'The value of "%s" is missing.',
                $this->fieldTitle
            );

            return false;
        }

        $value = $this->tryConvertToFloat($value);
        if ($value === null) {
            $this->errors[] = sprintf(
                'The value of "%s" is invalid.',
                $this->fieldTitle
            );

            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function isRequiredSpecific(): bool
    {
        return $this->isRequired;
    }

    /**
     * @param string $fieldTitle
     */
    public function setFieldTitle(string $fieldTitle): void
    {
        $this->fieldTitle = $fieldTitle;
    }

    /**
     * @param bool $isRequired
     */
    public function setIsRequired(bool $isRequired): void
    {
        $this->isRequired = $isRequired;
    }

    /**
     * @param int $minimum
     */
    public function setMinimum(int $minimum): void
    {
        $this->minimum = $minimum;
    }

    /**
     * @param int $maximum
     */
    public function setMaximum(int $maximum): void
    {
        $this->maximum = $maximum;
    }

    /**
     * @param mixed $value
     *
     * @return float|null
     */
    private function tryConvertToFloat($value): ?float
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', $value);
        }

        $value = filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);

        if ($value === null) {
            return null;
        }

        return (float)$value;
    }
}
