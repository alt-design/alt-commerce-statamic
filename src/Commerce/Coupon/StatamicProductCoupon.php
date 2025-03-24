<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Coupon;



use AltDesign\AltCommerce\Contracts\ProductCoupon;

class StatamicProductCoupon implements ProductCoupon
{

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

    public function isProductEligible(string $productId): string
    {
        return in_array($productId, $this->eligibleProducts);
    }
}
