<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox\Macaw;

trait PlayerItemTrait
{
    public function add_user_virtual_currency(): array
    {
        $postFields = [];

        return $this->make_rest_call(endpoint: "endpoint",
            postFields: $postFields);
    }

    public function confirm_purchase(): array
    {
        $postFields = [];

        return $this->make_rest_call(endpoint: "endpoint",
            postFields: $postFields);
    }

    public function consume_item(): array
    {
        $postFields = [];

        return $this->make_rest_call(endpoint: "endpoint",
            postFields: $postFields);
    }

    public function get_character_inventory(): array
    {
        $postFields = [];

        return $this->make_rest_call(endpoint: "endpoint",
            postFields: $postFields);
    }

    public function get_payment_token(): array
    {
        $postFields = [];

        return $this->make_rest_call(endpoint: "endpoint",
            postFields: $postFields);
    }

    public function get_purchase(): array
    {
        $postFields = [];

        return $this->make_rest_call(endpoint: "endpoint",
            postFields: $postFields);
    }

    public function get_user_inventory(): array
    {
        $postFields = [];

        return $this->make_rest_call(endpoint: "endpoint",
            postFields: $postFields);
    }

    public function pay_for_purchase(): array
    {
        $postFields = [];

        return $this->make_rest_call(endpoint: "endpoint",
            postFields: $postFields);
    }

    public function purchase_item(): array
    {
        $postFields = [];

        return $this->make_rest_call(endpoint: "endpoint",
            postFields: $postFields);
    }

    public function redeem_coupon(): array
    {
        $postFields = [];

        return $this->make_rest_call(endpoint: "endpoint",
            postFields: $postFields);
    }

    public function start_purchase(): array
    {
        $postFields = [];

        return $this->make_rest_call(endpoint: "endpoint",
            postFields: $postFields);
    }

    public function subtract_user_virtual_currency(): array
    {
        $postFields = [];

        return $this->make_rest_call(endpoint: "endpoint",
            postFields: $postFields);
    }

    public function unlock_container_instance(): array
    {
        $postFields = [];

        return $this->make_rest_call(endpoint: "endpoint",
            postFields: $postFields);
    }

    public function unlock_container_item(): array
    {
        $postFields = [];

        return $this->make_rest_call(endpoint: "endpoint",
            postFields: $postFields);
    }
}