<?php

namespace AltDesign\AltCommerceStatamic\Fieldtypes;

/**
 * Renders the order status as a coloured badge on listing indexes (via the
 * order_status-fieldtype-index component). On publish forms it behaves as a
 * plain text field — orders set it via actions, blueprints keep it read only.
 */
class OrderStatus extends BaseFieldType
{
    protected $keywords = ['order', 'status'];

    protected $component = 'text';
}
