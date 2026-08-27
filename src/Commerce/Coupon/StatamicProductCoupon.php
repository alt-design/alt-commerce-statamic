<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Coupon;



use AltDesign\AltCommerce\Contracts\ProductCoupon;

class StatamicProductCoupon implements ProductCoupon
{

    /**
     * @param array<string> $eligibleProducts
     * @param array<string> $excludedProducts
     */
    public function __construct(
        protected string $id,
        protected string $name,
        protected string $code,
        protected string $currency,
        protected \DateTimeImmutable|null $startDate,
        protected \DateTimeImmutable|null $endDate,
        protected int $discountAmount,
        protected bool $isPercentage,
        protected array $eligibleProducts,
        public int $redemptionLimit,
        public int $customerRedemptionLimit,
        protected array $excludedProducts = [],
        protected int $minimumSpend = 0,
    )
    {

    }

    public function name(): string
    {
        return $this->name;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function startDate(): \DateTimeImmutable|null
    {
        return $this->startDate;
    }

    public function endDate(): \DateTimeImmutable|null
    {
        return $this->endDate;
    }

    public function discountAmount(): int
    {
        return $this->discountAmount;
    }

    public function isPercentage(): bool
    {
        return $this->isPercentage;
    }

    public function minimumSpend(): int
    {
        return $this->minimumSpend;
    }

    public function isProductEligible(string $productId): bool
    {
        if (in_array($productId, $this->excludedProducts)) {
            return false;
        }

        return empty($this->eligibleProducts) || in_array($productId, $this->eligibleProducts);
    }
}
