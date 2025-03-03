<?php

namespace AltDesign\AltCommerceStatamic\Concerns;

trait EloquentCustomer
{
    public function customerId(): string
    {
        return $this->id;
    }

    public function customerName(): string
    {
        return $this->name;
    }

    public function customerEmail(): string
    {
        return $this->email;
    }

    public function findGatewayId(string $gateway): null|string
    {
        return $this->{"gateway_{$gateway}_id"} ?? null;
    }

    public function setGatewayId(string $gateway, string $gatewayId): void
    {
        $this->{"gateway_{$gateway}_id"} = $gatewayId;
        $this->save();
    }

    public function customerAdditionalData(): array
    {
        if (isset($this->customerData) && is_array($this->customerData)) {
            return $this->only($this->customerData);
        }
        return [];
    }
}