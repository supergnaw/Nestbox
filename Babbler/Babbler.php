<?php

declare(strict_types=1);

namespace app\Nestbox\Babbler;

use app\Nestbox\Nestbox;

class Babbler extends Nestbox
{
    // vars
    private $cols = ['entry_id', 'created', 'edited', 'created_by', 'edited_by', 'category', 'title', 'content'];

    protected int $author_size = 32;
    protected int $category_size = 64;
    protected int $sub_category_size = 64;
    protected int $title_size = 255;

    // constructor
    public function __construct()
    {
        // database functions
        parent::__construct();
        // create class tables
        $this->create_entry_table();
        $this->create_history_table();
        $this->create_levenshtein_function();
    }

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
        if (!self::validate_nonempty_params(params: $optional)) {
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

        var_dump("edit_entry", __LINE__, $params);

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
        $sql = "DELETE FROM `babbler_entries` WHERE `entry_id` = :entry_id";
        $params = ["entry_id" => $entry_id];
        if ($this->query_execute($sql, $params)) return 1 == $this->results();
        return false;
    }

    // search entries
    public function search_entries(string $words, string $category = "*", bool $strict = true, int $buffer = 100): array
    {
        $search = ($strict)
            ? preg_replace(pattern: "/[\s]+/", replacement: "%", subject: "%$words%")
            : preg_replace(pattern: "/[\s]+/", replacement: "%", subject: "%$words%");

        $params = ['search' => $search];

        $words = explode(" ", $words);
        $pattern = ".{0,$buffer}";
        for ($i = 0; $i < count($words); $i++) {
            $pattern .= (!empty($words[$i + 1]) ?? '') ? "$words[$i].*?(?!={$words[$i+1]})" : $words[$i];
        }
        $pattern .= ".{0,$buffer}";
        $highlight = '/(' . implode(separator: '|', array: $words) . ')/i';

        $sql = "SELECT * FROM `babbler_entries`
                WHERE `content` RLIKE \"{$pattern}\";";
        $this->query_execute($sql, $params);
        $results = $this->results();
        foreach ($results as $key => $result) {
            preg_match(pattern: "/$pattern/i", subject: $result['content'], matches: $matches);
            $results[$key]["matches"] = "..." . preg_replace(pattern: $highlight, replacement: '<strong>$1</strong>', subject: $matches[0]) . "...";
        }
        return $results;
    }

    // find fuzzy title
    public function search_fuzzy_title(string $title, int $distance = 2): array
    {
        // search for entry
        $sql = "SELECT
                    *
                FROM `babbler_entries`
                WHERE levenshtein(`title`, :title) <= :distance
                -- ORDER BY `l_distance` ASC;";
        return ($this->query_execute($sql, ['title' => $title, 'distance' => $distance])) ? $this->results : [];
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

    // create entry table
    public function create_entry_table(): bool
    {
        // check if entry table exists
        if ($this->valid_schema('babbler_entries')) return true;

        $sql = "CREATE TABLE IF NOT EXISTS `babbler_entries` (
                    `entry_id` INT NOT NULL AUTO_INCREMENT ,
                    `created` DATETIME NOT NULL ,
                    `edited` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
                    `published` DATETIME NULL ,
                    `is_draft` NOT NULL DEFAULT 0 ,
                    `is_hidden` NOT NULL DEFAULT 0 ,
                    `created_by` VARCHAR( {$this->author_size} ) NOT NULL ,
                    `edited_by` VARCHAR( {$this->author_size} ) NOT NULL ,
                    `category` VARCHAR( {$this->category_size} ) NOT NULL ,
                    `sub_category` VARCHAR( {$this->sub_category_size} ) NOT NULL ,
                    `title` VARCHAR( {$this->title_size} ) NOT NULL ,
                    `content` MEDIUMTEXT NOT NULL ,
                    PRIMARY KEY ( `entry_id` )
                ) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        return $this->query_execute($sql);
    }

    // create history table and update trigger
    public function create_history_table(): bool
    {
        // check if history table exists
        if ($this->valid_schema('babbler_history')) return true;

        // create the history table
        $sql = "CREATE TABLE IF NOT EXISTS `babbler_history` (
                    `history_id` INT NOT NULL AUTO_INCREMENT ,
                    `entry_id` INT NOT NULL ,
                    `created` DATETIME NOT NULL ,
                    `edited` TIMESTAMP ,
                    `published` DATETIME NULL ,
                    `is_draft` NOT NULL DEFAULT 0 ,
                    `is_hidden` NOT NULL DEFAULT 0 ,
                    `created_by` VARCHAR( {$this->author_size} ) NOT NULL ,
                    `edited_by` VARCHAR( {$this->author_size} ) NOT NULL ,
                    `category` VARCHAR( {$this->category_size} ) NOT NULL ,
                    `sub_category` VARCHAR( {$this->category_size} ) NOT NULL ,
                    `title` VARCHAR( {$this->title_size} ) NOT NULL ,
                    `content` MEDIUMTEXT NOT NULL ,
                    PRIMARY KEY ( `history_id` )
                ) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        if (!$this->query_execute($sql)) return false;

        // create history trigger
        $sql = "CREATE TRIGGER `babbler_history_trigger` AFTER UPDATE ON `babbler_entries`
                FOR EACH ROW
                IF ( OLD.edited <> NEW.edited ) THEN
                    INSERT INTO `babbler_history` (
                        `entry_id`
                        , `created`
                        , `edited`
                        , `published`
                        , `is_draft`
                        , `is_hidden`
                        , `created_by`
                        , `edited_by`
                        , `category`
                        , `sub_category`
                        , `title`
                        , `content`
                    ) VALUES (
                        OLD.`entry_id`
                        , OLD.`created`
                        , OLD.`edited`
                        , OLD.`published`
                        , OLD.`is_draft`
                        , OLD.`is_hidden`
                        , OLD.`created_by`
                        , OLD.`edited_by`
                        , OLD.`category`
                        , OLD.`sub_category`
                        , OLD.`title`
                        , OLD.`content`
                    );
                END IF;
                CREATE TRIGGER `babbler_delete_trigger` BEFORE DELETE
                ON `babbler_entries` FOR EACH ROW
                BEGIN
                    INSERT INTO `babbler_history` (
                        `entry_id`
                        , `created`
                        , `edited`
                        , `published`
                        , `is_draft`
                        , `is_hidden`
                        , `created_by`
                        , `edited_by`
                        , `category`
                        , `sub_category`
                        , `title`
                        , `content`
                    ) VALUES (
                        OLD.`entry_id`
                        , OLD.`created`
                        , OLD.`edited`
                        , OLD.`published`
                        , OLD.`is_draft`
                        , OLD.`is_hidden`
                        , OLD.`created_by`
                        , OLD.`edited_by`
                        , OLD.`category`
                        , OLD.`sub_category`
                        , OLD.`title`
                        , OLD.`content`
                    );
                END;";
        if (!$this->query_execute($sql)) return false;

        return true;
    }

    // create
    public function create_levenshtein_function(): bool
    {
        return true;
        $sql = "DELIMITER $$
                CREATE FUNCTION levenshtein( s1 VARCHAR(255), s2 VARCHAR(255) )
                    RETURNS INT
                    DETERMINISTIC
                    BEGIN
                        DECLARE s1_len, s2_len, i, j, c, c_temp, cost INT;
                        DECLARE s1_char CHAR;
                        -- max strlen=255
                        DECLARE cv0, cv1 VARBINARY(256);
                
                        SET s1_len = CHAR_LENGTH(s1), s2_len = CHAR_LENGTH(s2), cv1 = 0x00, j = 1, i = 1, c = 0;
                
                        IF s1 = s2 THEN
                            RETURN 0;
                        ELSEIF s1_len = 0 THEN
                            RETURN s2_len;
                        ELSEIF s2_len = 0 THEN
                            RETURN s1_len;
                        ELSE
                            WHILE j <= s2_len DO
                                SET cv1 = CONCAT(cv1, UNHEX(HEX(j))), j = j + 1;
                            END WHILE;
                            WHILE i <= s1_len DO
                                SET s1_char = SUBSTRING(s1, i, 1), c = i, cv0 = UNHEX(HEX(i)), j = 1;
                                WHILE j <= s2_len DO
                                    SET c = c + 1;
                                    IF s1_char = SUBSTRING(s2, j, 1) THEN
                                        SET cost = 0; ELSE SET cost = 1;
                                    END IF;
                                    SET c_temp = CONV(HEX(SUBSTRING(cv1, j, 1)), 16, 10) + cost;
                                    IF c > c_temp THEN SET c = c_temp; END IF;
                                    SET c_temp = CONV(HEX(SUBSTRING(cv1, j+1, 1)), 16, 10) + 1;
                                    IF c > c_temp THEN
                                        SET c = c_temp;
                                    END IF;
                                    SET cv0 = CONCAT(cv0, UNHEX(HEX(c))), j = j + 1;
                                END WHILE;
                                SET cv1 = cv0, i = i + 1;
                            END WHILE;
                        END IF;
                        RETURN c;
                    END$$
                DELIMITER ;";

        $sql = "DROP FUNCTION IF EXISTS `levenshtein`;
                DELIMITER $$
                CREATE DEFINER=`" . NESTBOX_DB_USER . "`@`" . NESTBOX_DB_HOST . "` FUNCTION `levenshtein`( s1 VARCHAR(255), s2 VARCHAR(255) ) RETURNS int
                    DETERMINISTIC
                BEGIN
                    DECLARE s1_len, s2_len, i, j, c, c_temp, cost INT;
                    DECLARE s1_char CHAR;
                    -- max strlen=255
                    DECLARE cv0, cv1 VARBINARY(256);
            
                    SET s1_len = CHAR_LENGTH(s1), s2_len = CHAR_LENGTH(s2), cv1 = 0x00, j = 1, i = 1, c = 0;
            
                    IF s1 = s2 THEN
                        RETURN 0;
                    ELSEIF s1_len = 0 THEN
                        RETURN s2_len;
                    ELSEIF s2_len = 0 THEN
                        RETURN s1_len;
                    ELSE
                        WHILE j <= s2_len DO
                            SET cv1 = CONCAT(cv1, UNHEX(HEX(j))), j = j + 1;
                        END WHILE;
                        WHILE i <= s1_len DO
                            SET s1_char = SUBSTRING(s1, i, 1), c = i, cv0 = UNHEX(HEX(i)), j = 1;
                            WHILE j <= s2_len DO
                                SET c = c + 1;
                                IF s1_char = SUBSTRING(s2, j, 1) THEN
                                    SET cost = 0; ELSE SET cost = 1;
                                END IF;
                                SET c_temp = CONV(HEX(SUBSTRING(cv1, j, 1)), 16, 10) + cost;
                                IF c > c_temp THEN SET c = c_temp; END IF;
                                SET c_temp = CONV(HEX(SUBSTRING(cv1, j+1, 1)), 16, 10) + 1;
                                IF c > c_temp THEN
                                    SET c = c_temp;
                                END IF;
                                SET cv0 = CONCAT(cv0, UNHEX(HEX(c))), j = j + 1;
                            END WHILE;
                            SET cv1 = cv0, i = i + 1;
                        END WHILE;
                    END IF;
                    RETURN c;
                END$$
                DELIMITER ;";

        return $this->query_execute($sql);
    }

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
        $sql = "SELECT `category`, COUNT(*) as `count` FROM `babbler_entries` GROUP BY `category`;";
        return ($this->query_execute($sql)) ? $this->results() : [];
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
    public function fetch_entries_by_category(string $category, string $sub_category = '', string $order_by = 'created', string $sort = ''): array
    {
        $where = "`category` = :category" . (!(empty($sub_category)) ? " AND `sub_category` = :sub_category" : '');
        $where .= " AND `published` IS NOT NULL AND `is_draft` = 0 AND `is_hidden` = 0";
        $order_by = ($this->valid_schema(tbl: 'babbler_entries', col: $order_by)) ? $order_by : 'created';
        $sort = (in_array(needle: strtoupper($sort), haystack: ['ASC', 'DESC'])) ? strtoupper($sort) : 'ASC';

        $sql = "SELECT * FROM `babbler_entries` WHERE {$where} ORDER BY {$order_by} {$sort};";
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
        return ($this->query_execute($sql, $params))
            ? $this->results(true)
            : [];
    }
}
