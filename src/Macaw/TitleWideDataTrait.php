<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox\Macaw;

trait TitleWideDataTrait
{
    public function get_catalog_items(): array
    {
        $postFields = [];

        return $this->make_rest_call(endpoint: "endpoint",
            postFields: $postFields);
    }

    public function get_publisher_data(): array
    {
        $postFields = [];

        return $this->make_rest_call(endpoint: "endpoint",
            postFields: $postFields);
    }

    public function get_store_items(): array
    {
        $postFields = [];

        return $this->make_rest_call(endpoint: "endpoint",
            postFields: $postFields);
    }

    public function get_time(): array
    {
        $postFields = [];

        return $this->make_rest_call(endpoint: "endpoint",
            postFields: $postFields);
    }

    public function get_title_data(): array
    {
        $postFields = [];

        return $this->make_rest_call(endpoint: "endpoint",
            postFields: $postFields);
    }

    public function get_title_news(): array
    {
        $postFields = [];

        return $this->make_rest_call(endpoint: "endpoint",
            postFields: $postFields);
    }
}