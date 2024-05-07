<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox\Babbler;

use Supergnaw\Nestbox\Nestbox;

class Babbler extends Nestbox
{
    final protected const string PACKAGE_NAME = 'babbler';

    public int $babblerAuthorSize = 32;
    public int $babblerCategorySize = 64;
    public int $babblerSubCategorySize = 64;
    public int $babblerTitleSize = 255;

    use ClassTablesTrait;

    use EntryManagementTrait;

    use EntrySearchTrait;


    // get table entries
    public function fetch_entry_table(string $orderBy = "", string $sort = "", int $limit = 50, int $start = 0): array
    {
        $orderBy = (!empty($orderBy) && $this->valid_schema('babbler_entries', $orderBy)) ? $orderBy : 'created';
        $sort = (in_array(strtoupper($sort), array('ASC', 'DESC'))) ? strtoupper($sort) : 'ASC';
        $sql = "SELECT * FROM `babbler_entries` ORDER BY `{$orderBy}` {$sort};";

        return ($this->query_execute($sql)) ? $this->results() : [];
    }

    // get entry by ID
    public function fetch_entry(int $entry_id): array
    {
        $sql = "SELECT * FROM `babbler_entries` WHERE `entry_id` = :entryID;";
        $this->query_execute($sql, array('entryID' => $entry_id));
        return $this->results()[0] ?? [];
    }

    // get available categories
    public function fetch_categories(): array
    {
        $output = [];
        $sql = "SELECT `category`, COUNT(*) as `count` FROM `babbler_entries` GROUP BY `category`;";

        if (!$this->query_execute($sql)) return $output;

        foreach ($this->results() as $result) $output[$result['category']] = $result['count'];

        return $output;
    }

    // get available subcategories
    public function fetch_sub_categories(string $category = ''): array
    {
        $where = (!empty($category)) ? "WHERE `category` = :category" : "";
        $sql = "SELECT `sub_category`, COUNT(*) as `count` FROM `babbler_entries` {$where} GROUP BY `sub_category`;";
        $params = (!empty($category)) ? ["category" => $category] : [];
        return ($this->query_execute(query: $sql, params: $params)) ? $this->results() : [];
    }

    // get all entries of a certain category
    public function fetch_entries_by_category(string $category, string $sub_category = '', string $order_by = 'created', string $sort = '', int $start = 0, int $limit = 10): array
    {
        $where = "`category` = :category" . (!(empty($sub_category)) ? " AND `sub_category` = :sub_category" : '');
//        $where .= " AND `published` IS NOT NULL AND `is_draft` = 0 AND `is_hidden` = 0"; // hidden for testing purposes
        $order_by = ($this->valid_schema(table: 'babbler_entries', column: $order_by)) ? $order_by : 'created';
        $sort = (in_array(needle: strtoupper($sort), haystack: ['ASC', 'DESC'])) ? strtoupper($sort) : 'ASC';

        $sql = "SELECT * FROM `babbler_entries` WHERE {$where} ORDER BY {$order_by} {$sort}";

        if (0 !== $limit) {
            $sql .= ($start < 0) ? " LIMIT {$limit};" : " LIMIT {$start}, {$limit};";
        } else {
            $sql .= ";";
        }

        $params = ['category' => $category];
        if (!empty($sub_category)) $params['sub_category'] = $sub_category;
        return ($this->query_execute($sql, $params)) ? $this->results() : [];
    }

    // get entry by category and title
    public function fetch_entry_by_category_and_title(string $category, string $title, string $sub_category = ''): array
    {
        $where = "WHERE `category` = :category" . ((!empty($sub_category)) ? " AND `sub_category` = :sub_category" : '');
        $sql = "SELECT * FROM `babbler_entries` {$where} AND `title` LIKE :title;";
        $params = ['category' => $category, 'title' => $title];
        if (!empty($sub_category)) $params['sub_category'] = $sub_category;
        return ($this->query_execute($sql, $params)) ? $this->results(true) : [];
    }
}
