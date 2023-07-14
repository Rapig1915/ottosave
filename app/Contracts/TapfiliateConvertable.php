<?php

namespace App\Contracts;

interface TapfiliateConvertable
{
    public function getTapfiliateCustomer();
    public function getConversionAmount();
    public function getCommissionType();
    public function getConversionId();
}
