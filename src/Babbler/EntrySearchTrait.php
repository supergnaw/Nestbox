<?php

declare(strict_types=1);

namespace NestboxPHP\Nestbox\Babbler;

trait EntrySearchTrait
{
    // search entries
    public function search_entries_exact(string $search, string $category = "*"): array
    {
        $where = ["content LIKE" => "%" . trim($search) . "%"];

        if ("*" != $category) $where["category"] = $category;

        return $this->select("babbler_entries", $where);
    }

    public function search_entries_fuzzy(string $search, string $category = "*"): array
    {
        $search = $this->sanitize_search_string($search);

        $search = implode("%", preg_split(pattern: "/\s+/", subject: $search));

        $where = ["content" => "%$search%"];
        if ("*" != $category) $where["category"] = trim($category);

        return $this->select("babbler_entries", $where);
    }

    public function search_entries_threshold(string $words, string $category = "*"): array
    {
        $cases = [];

        foreach (explode(" ", $this->sanitize_search_string($words)) as $word) {
            $word = preg_replace("/[^\w]+/", "", $word);

            $cases[] = "CASE WHEN FIND IN SET('$word', `content`) > 0 THEN 1 ELSE 0 END";
        }

        $cases = implode(" + ", $cases);

        $sql = "SELECT *, SUM($cases) AS 'threshold' FROM `babbler_entries`;";

        if (!$this->query_execute($sql)) return [];

        return $this->fetch_all_results();
    }

    public function search_entries_regex(string $pattern, string $category = "*"): array
    {
        $sql = "SELECT * FROM `babbler_entries` WHERE REGEXP :pattern";

        $params = ["pattern" => $pattern];

        if (!$this->query_execute($sql, $params)) return [];

        return $this->fetch_all_results();
    }

    // find exact title
    public function search_title(string $title): array
    {
        $title = $this->sanitize_search_string($title);

        return $this->select("babbler_entries", ["title LIKE" => "%$title%"]);
    }

//    public function search_url_title(string $title): array
//    {
//        $sql = "SELECT * FROM `babbler_entries` WHERE `title` LIKE :title;";
//        $title = implode(separator: "%", array: preg_split(pattern: "/[^\w]+/", subject: trim($title)));
//        $params = ["title" => $title];
//        return ($this->query_execute($sql, $params)) ? $this->results() : [];
//    }

    public function sanitize_search_string(string $string): string
    {
        return preg_replace(pattern: "/[^\w\s]+/i", replacement: "", subject: $string);
    }
}