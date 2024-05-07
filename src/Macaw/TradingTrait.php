<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox\Macaw;

trait TradingTrait
{
    public function accept_trade(): array
    {
        $postFields = [];

        return $this->make_rest_call(endpoint: "endpoint",
            postFields: $postFields);
    }

    public function cancel_trade(): array
    {
        $postFields = [];

        return $this->make_rest_call(endpoint: "endpoint",
            postFields: $postFields);
    }

    public function get_player_trades(): array
    {
        $postFields = [];

        return $this->make_rest_call(endpoint: "endpoint",
            postFields: $postFields);
    }

    public function get_trade_status(): array
    {
        $postFields = [];

        return $this->make_rest_call(endpoint: "endpoint",
            postFields: $postFields);
    }

    public function open_trade(): array
    {
        $postFields = [];

        return $this->make_rest_call(endpoint: "endpoint",
            postFields: $postFields);
    }
}