<?php

namespace AltDesign\AltCommerceStatamic\Http\Controllers\Exports;

use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrder;
use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrderFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;
use Statamic\Facades\CP\Toast;
use Statamic\Facades\Entry;

class OrderItemExportController
{

    protected array $columns = [];

    public function __construct(protected StatamicOrderFactory $orderFactory)
    {

        $this->columns = [
            [
                'title' => 'Date sold',
                'format' => fn($row) => $row['created_at']->format('Y-m-d'),
            ],
            [
                'title' => 'Product',
                'format' => fn($row) => $row['product_name'],
            ],
            [
                'title' => 'Company Name',
                'format' => fn($row) => $row['company_name'],
            ],
            [
                'title' => 'Contact Name',
                'format' => fn($row) => $row['contact_name'],
            ],
            [
                'title' => 'Customer Email',
                'format' => fn($row) => $row['customer_email'],
            ],
            [
                'title' => 'Customer Phone',
                'format' => fn($row) => $row['customer_phone'],
            ],
            [
                'title' => 'Country',
                'format' => fn($row) => $row['country'],
            ],
            [
                'title' => 'Payment Method',
                'format' => fn($row) => $row['payment_method'],
            ],
            [
                'title' => 'Currency',
                'format' => fn($row) => $row['currency'],
            ],
            [
                'title' => 'Product Price',
                'format' => fn($row) => number_format($row['amount'] / 100, 2),
            ],
            [
                'title' => 'Tax Name',
                'format' => fn($row) => $row['tax_name'],
            ],
            [
                'title' => 'Tax Rate',
                'format' => fn($row) => $row['tax_rate'],
            ],
            [
                'title' => 'Tax Total',
                'format' => fn($row) => number_format($row['tax_total'] / 100, 2),
            ],
            [
                'title' => 'Discount Total',
                'format' => fn($row) => number_format($row['discount_total'] / 100, 2)
            ],
            [
                'title' => 'Discount Code',
                'format' => fn($row) => $row['discount_code']
            ],
            [
                'title' => 'Order number',
                'format' => fn($row) => $row['order_number']
            ]
        ];
    }


    public function __invoke()
    {
        request()->validate([
            'date_from' => 'sometimes',
            'date_to' => 'sometimes',
        ]);

        $orders = $this->getOrders();

        if (empty($orders)) {
            Toast::error('No orders found');
            return redirect()->back();
        }

        $handle = fopen('php://temp', 'w+');
        try {

            fputcsv($handle, array_map(fn($column) => $column['title'], $this->columns));

            foreach ($this->extractRows($orders) as $row) {
                fputcsv($handle, array_map(fn($column) => $column['format']($row), $this->columns));
            }
            rewind($handle);

            $content = stream_get_contents($handle);

            return Response::make($content, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="csv-export.csv"',
            ]);

        } finally {
            fclose($handle);
        }
    }


    /**
     * @param array<StatamicOrder> $orders
     * @return array
     */
    protected function extractRows(array $orders): array
    {
        $rows = [];
        foreach ($orders as $order) {

            foreach ($order->lineItems as $lineItem) {
                for ($i = 0; $i < $lineItem->quantity; $i++) {
                    $rows[] = [
                        'created_at' => $order->createdAt,
                        'product_name' => $lineItem->productName,
                        'company_name' => $order->billingAddress->company,
                        'contact_name' => $order->billingAddress->fullName,
                        'customer_email' => $order->customerEmail,
                        'customer_phone' => $order->additional['phone_number'] ?? null,
                        'country' => $order->billingAddress->countryCode,
                        'payment_method' => $order->additional['payment_method'] ?? null,
                        'currency' => $order->currency,
                        'amount' => $lineItem->amount,
                        'discount_total' => $lineItem->discountTotal / $lineItem->quantity,
                        'discount_code' => $order->discountItems[0]->couponCode ?? null,
                        'order_number' => $order->orderNumber,
                        'tax_name' => $lineItem->taxName,
                        'tax_rate' => $lineItem->taxRate,
                        'tax_total' => $lineItem->taxTotal,
                    ];
                }
            }
        }
        return $rows;

    }

    /**
     * @return StatamicOrder[]
     */
    protected function getOrders(): array
    {
        return Entry::query()->where('collection', 'orders')
            ->when(request('date_from'), fn($query) => $query->where('created_at', '>=', Carbon::parse(request('date_from'))->startOfDay()))
            ->when(request('date_to'), fn($query) => $query->where('created_at', '<=', Carbon::parse(request('date_to'))->endOfDay()))
            ->get()
            ->map(fn($entry) => $this->orderFactory->fromEntry($entry))
            ->toArray();
    }
}
