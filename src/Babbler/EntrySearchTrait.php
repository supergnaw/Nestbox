<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox\Babbler;

trait EntrySearchTrait
{
    // search entries
    public function search_entries_exact(string $search, string $category = "*"): array
    {
        $where = ["content" => trim($search)];
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

//        $words = explode(" ", $words);
//        $pattern = ".{0,$buffer}";
//        for ($i = 0; $i < count($words); $i++) {
//            $pattern .= (!empty($words[$i + 1]) ?? '') ? "$words[$i].*?(?!={$words[$i+1]})" : $words[$i];
//        }
//        $pattern .= ".{0,$buffer}";
//        $highlight = '/(' . implode(separator: '|', array: $words) . ')/i';
//
//        $sql = "SELECT * FROM `babbler_entries`
//                WHERE `content` RLIKE \"{$pattern}\";";
//        $this->query_execute($sql, $params);
//        $results = $this->results();
//        foreach ($results as $key => $result) {
//            preg_match(pattern: "/$pattern/i", subject: $result['content'], matches: $matches);
//            $results[$key]["matches"] = "..." . preg_replace(pattern: $highlight, replacement: '<strong>$1</strong>', subject: $matches[0]) . "...";
//        }
//        return $results;
    }

    public function search_entries_threshold(string $words, string $category = "*", int $threshold = 100): array
    {
        return [];
    }

    // find exact title
    public function search_title(string $title): array
    {
        $sql = "SELECT * FROM `babbler_entries` WHERE `title` = :title;";
        $params = ["title" => $title];
        return ($this->query_execute($sql, $params)) ? $this->results() : [];
    }

    public function search_url_title(string $title): array
    {
        $sql = "SELECT * FROM `babbler_entries` WHERE `title` LIKE :title;";
        $title = implode(separator: "%", array: preg_split(pattern: "/[^\w]+/", subject: trim($title)));
        $params = ["title" => $title];
        return ($this->query_execute($sql, $params)) ? $this->results() : [];
    }

    public function sanitize_search_string(string $string): string
    {
        return preg_replace(pattern: "/[^\w\s]+/i", replacement: "", subject: $string);
    }
}