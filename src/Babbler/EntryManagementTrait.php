<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox\Babbler;

trait EntryManagementTrait
{
    // add entry
    public function add_entry(
        string $category,
        string $sub_category,
        string $title,
        string $content,
        string $author,
        string $created = null,
        string $published = "",
        bool   $is_draft = false,
        bool   $is_hidden = false
    ): bool
    {
        // prepare vars and verify entry
        $optional = [
            "category" => $category,
            "sub_category" => $sub_category,
            "title" => $title,
            "content" => $content,
            "author" => $author,
        ];
        if (!self::confirm_nonempty_params(params: $optional)) {
            return false;
        }

        $params = [
            "category" => trim($category),
            "sub_category" => trim($sub_category),
            "title" => trim($title),
            "content" => trim($content),
            "created_by" => trim($author),
            "edited_by" => trim($author),
            "created" => date(format: 'Y-m-d H:i:s', timestamp: strtotime(datetime: ($created ?? "now"))),
            "edited" => date(format: 'Y-m-d H:i:s', timestamp: strtotime(datetime: ($created ?? "now"))),
            "published" => ($published) ? date(format: 'Y-m-d H:i:s', timestamp: strtotime(datetime: $published)) : null,
            "is_draft" => $is_draft,
            "is_hidden" => $is_hidden,
        ];

        // create query
        $sql = "INSERT INTO `babbler_entries` (
                    `category`
                    , `sub_category`
                    , `title`
                    , `content`
                    , `created_by`
                    , `edited_by`
                    , `created`
                    , `edited`
                    , `published`
                    , `is_draft`
                    , `is_hidden`
                ) VALUES (
                    :category
                    , :sub_category
                    , :title
                    , :content
                    , :created_by
                    , :edited_by
                    , :created
                    , :edited
                    , :published
                    , :is_draft
                    , :is_hidden
                );";

        // execute
        if ($this->query_execute($sql, $params)) {
            return 1 == $this->row_count();
        }
        return false;
    }

    // edit entry
    public function edit_entry(
        string|int $entry_id,
        string     $editor,
        string     $category = "",
        string     $sub_category = "",
        string     $title = "",
        string     $content = "",
        string     $published = "",
        bool       $is_draft = null,
        bool       $is_hidden = null,
    ): bool
    {
        // verify entry data
        if (0 == intval($entry_id)) {
            $this->err[] = "Missing data for entry: 'Entry ID'";
            return false;
        }

        $params = [
            "entry_id" => $entry_id,
            "edited_by" => $editor
        ];

        if (!empty(trim($category))) $params['category'] = trim($category);
        if (!empty(trim($sub_category))) $params['sub_category'] = trim($sub_category);
        if (!empty(trim($title))) $params['title'] = trim($title);
        if (!empty(trim($content))) $params['content'] = trim($content);
        if (preg_match('/\d[4]\-\d\d\-\d\d.\d\d(\:\d\d(\:\d\d)?)?/', $published, $t)) {
            $params["published"] = date(format: 'Y-m-d H:i:s', timestamp: strtotime(datetime: $published));
        }
        $params["is_draft"] = (isset($is_draft)) ? $is_draft : false;
        $params["is_hidden"] = (isset($is_hidden)) ? $is_hidden : false;

        $cols = [];
        foreach ($params as $column => $value) $cols[] = "`{$column}` = :{$column}";
        $cols = implode(", ", $cols);

        // create query
        $sql = "UPDATE `babbler_entries` SET {$cols} WHERE `entry_id` = :entry_id;";

        // execute
        if ($this->query_execute($sql, $params)) return 1 == $this->row_count();
        return false;
    }

    // delete entry
    public function delete_entry(int $entry_id): bool
    {
        return $this->delete("babbler_entries", ["entry_id"=>$entry_id]);
    }
}