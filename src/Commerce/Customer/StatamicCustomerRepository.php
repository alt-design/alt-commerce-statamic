<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Customer;

use AltDesign\AltCommerce\Contracts\Customer;
use AltDesign\AltCommerce\Contracts\CustomerRepository;
use Illuminate\Database\Eloquent\Builder;

class StatamicCustomerRepository implements CustomerRepository
{

    public function find(string $customerId): ?Customer
    {
        return $this->query()->find($customerId);
    }

    public function findGatewayId(string $customerId, string $gatewayName): ?string
    {
        $user = $this->query()->findOrFail($customerId);
        return $user->{"gateway_{$gatewayName}_id"} ?? null;
    }

    public function setGatewayId(string $customerId, string $gatewayName, string $gatewayId): void
    {
        $user = $this->query()->findOrFail($customerId);
        $user->{"gateway_{$gatewayName}_id"} = $gatewayId;
        $user->save();
    }

    protected function query(): Builder
    {
        $userModel = config('auth.providers.users.model');
        if (empty($userModel)) {
            throw new \Exception('No user model defined in config/auth.php.');
        }

        return $userModel::query();
    }
}