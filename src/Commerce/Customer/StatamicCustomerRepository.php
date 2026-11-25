<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Customer;

use AltDesign\AltCommerce\Contracts\Customer;
use AltDesign\AltCommerce\Contracts\CustomerRepository;
use Illuminate\Database\Eloquent\Builder;
use Statamic\Facades\User;

class StatamicCustomerRepository implements CustomerRepository
{

    /**
     * Customers are Statamic users when the site has no Eloquent user model.
     */
    public function find(string $customerId): ?Customer
    {
        if (! empty(config('auth.providers.users.model'))) {
            return $this->query()->find($customerId);
        }

        $user = User::find($customerId);

        return $user ? new StatamicCustomer($user) : null;
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