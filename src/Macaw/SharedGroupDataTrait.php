<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox\Macaw;

trait SharedGroupDataTrait
{
    public function add_shared_group_members(): array
    {
        $postFields = [];

        return $this->make_rest_call(endpoint: "endpoint",
            postFields: $postFields);
    }

    public function create_shared_group(): array
    {
        $postFields = [];

        return $this->make_rest_call(endpoint: "endpoint",
            postFields: $postFields);
    }

    public function get_shared_group_data(): array
    {
        $postFields = [];

        return $this->make_rest_call(endpoint: "endpoint",
            postFields: $postFields);
    }

    public function remove_shared_group_members(): array
    {
        $postFields = [];

        return $this->make_rest_call(endpoint: "endpoint",
            postFields: $postFields);
    }

    public function update_shared_group_data(): array
    {
        $postFields = [];

        return $this->make_rest_call(endpoint: "endpoint",
            postFields: $postFields);
    }
}