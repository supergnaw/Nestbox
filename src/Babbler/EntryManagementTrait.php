<?php

declare(strict_types=1);

namespace NestboxPHP\Nestbox\Babbler;

use NestboxPHP\Nestbox\Exception\NestboxException;

trait EntryManagementTrait
{
    /**
     * Adds a new babbler entry
     *
     * @param string $category
     * @param string $sub_category
     * @param string $title
     * @param string $content
     * @param string $author
     * @param string|null $created
     * @param string|null $published
     * @param bool $is_draft
     * @param bool $is_hidden
     * @return bool
     */
    public function add_entry(
        string $category,
        string $sub_category,
        string $title,
        string $content,
        string $author,
        string $created = null,
        string $published = null,
        bool   $is_draft = false,
        bool   $is_hidden = false
    ): bool
    {
        // prepare vars and verify entry
        $emptyStrings = [];
        if ($this->is_empty_string($category)) $emptyStrings[] = "'category'";
        if ($this->is_empty_string($sub_category)) $emptyStrings[] = "'sub_category'";
        if ($this->is_empty_string($title)) $emptyStrings[] = "'title'";
        if ($this->is_empty_string($content)) $emptyStrings[] = "'content'";
        if ($this->is_empty_string($author)) $emptyStrings[] = "'author'";

        if ($emptyStrings) {
            throw new BabblerException("Empty strings provided for: " . join(", ", $emptyStrings));
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
            return 1 == $this->get_row_count();
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
        if ($this->query_execute($sql, $params)) return 1 == $this->get_row_count();
        return false;
    }

    // delete entry
    public function delete_entry(int $entry_id): bool
    {
        return $this->delete("babbler_entries", ["entry_id"=>$entry_id]);
    }

    public function is_empty_string(string $input): bool
    {
        return 0 == strlen(trim($input));
    }
}