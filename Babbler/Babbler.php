<?php
declare( strict_types = 1 );

namespace app\Nestbox\Babbler;

use app\Nestbox\Nestbox;

class Babbler extends Nestbox {
    // vars
    private $cols = ['entry_id', 'created', 'edited', 'created_by', 'edited_by', 'category', 'title', 'content'];

    protected int $author_size = 32;
    protected int $category_size = 64;
    protected int $title_size = 255;

    // constructor
    public function __construct() {
        // database functions
        parent::__construct();
        // create class tables
        $this->create_entry_table();
        $this->create_history_table();
        // create levenshtein function
        $this->create_levenshtein_function();
    }

    // add entry
    public function add_entry( string $category, string $title, string $content, string $author, string $created = "" ): bool
    {
        // prepare vars and verify _depricated_data
        $params = [];

        $params['category'] = trim($category);
        if( empty( $params['category'] )) {
            $this->err[] = "Missing _depricated_data for PunyCMS entry: 'Category'";
            return false;
        }

        $params['title'] = trim($title);
        if( empty( $params['title'] )) {
            $this->err[] = "Missing _depricated_data for PunyCMS entry: 'Title'";
            return false;
        }

        $params['content'] = trim($content);
        if( empty( $params['content'] )) {
            $this->err[] = "Missing _depricated_data for PunyCMS entry: 'Content'";
            return false;
        }

        $params['created_by'] = trim($author);
        if( empty( $params['created_by'] )) {
            $this->err[] = "Missing _depricated_data for PunyCMS entry: 'Author'";
            return false;
        }

        $params['edited_by'] = $params['created_by'];
        $params['created'] = (!empty(trim($created)))
            ? date( 'Y-m-d H:i:s', strtotime(trim($created)))
            : date( 'Y-m-d H:i:s' );

        // create query
        $sql = "INSERT INTO `punycms_entries` (
                    `category`
                    , `title`
                    , `content`
                    , `created_by`
                    , `edited_by`
                    , `created`
                ) VALUES (
                    :category
                    , :title
                    , :content
                    , :created_by
                    , :edited_by
                    , :created
                );";

        // execute
        if ($this->query_execute($sql, $params)) {
            return (1 == $this->row_count()) ? true : false;
        }
        return false;
    }

    // edit entry
    public function edit_entry( int $entryID, string $category, string $title, string $content, string $editor ): bool
    {
        // verify entry _depricated_data
        $params['entry_id'] = $entryID;
        if( 0 != $params['entry_id']) {
            $this->err[] = "Missing _depricated_data for PunyCMS entry: 'Entry ID'";
            return false;
        }

        $params['category'] = trim( $category );
        if( empty( $params['category'] )) {
            $this->err[] = "Missing _depricated_data for PunyCMS entry: 'Category'";
            return false;
        }

        $params['title'] = trim( $title );
        if( empty( $params['title'] )) {
            $this->err[] = "Missing _depricated_data for PunyCMS entry: 'Title'";
            return false;
        }

        $params['content'] = trim( $content );
        if( empty( $params['content'] )) {
            $this->err[] = "Missing _depricated_data for PunyCMS entry: 'Content'";
            return false;
        }

        $params['edited_by'] = trim( $editor );
        if( empty( $params['edited_by'] )) {
            $this->err[] = "Missing _depricated_data for PunyCMS entry: 'Editor'";
            return false;
        }

        // create query
        $sql = "UPDATE `punycms_entries` SET
                `category` = :category,
                `title` = :title,
                `content` = :content,
                `edited_by` = :edited_by
                WHERE `entry_id` = :entry_id;";

        // execute
        if ($this->query_execute($sql, $params)) {
            return (1 == $this->row_count()) ? true : false;
        }
        return false;
    }

    // delete entry
    public function delete_entry( int $entryID ): bool
    {
        $sql = "DELETE FROM `punycms_entries` WHERE `entry_id` = :eid";
        if ($this->query_execute($sql,['eid'=>$entryID])) {
            return (1 == $this->results()) ? true : false;
        }
        return false;
    }

    // search entries
    public function search_entries( string $words, bool $strict = true ): array
    {
        $params = [
            'category'=>[],
            'title'=>[],
            'content'=>[]
        ];

        $words = explode(" ", $words);
        foreach ($words as $k => $word) {
            $params["search_{$k}"] = "%{$word}%";
            $search['category'][] = "search_{$k}";
            $search['title'][] = "search_{$k}";
            $search['content'][] = "search_{$k}";
        }

        $conjunction = ($strict) ? 'AND' : 'OR';
        $search['category'] = implode(" {$conjunction} `category` LIKE :", $words);
        $search['title'] = implode(" {$conjunction} `title` LIKE :", $words);
        $search['content'] = implode(" {$conjunction} `content` LIKE :", $words);


        $sql = "SELECT * FROM `punycms_entries`
                WHERE (`category` LIKE :search_1 AND LIKE :search_2)
                OR (`title` LIKE :search)
                OR (`content` LIKE :search);";
        return ($this->query_execute($sql, $params)) ? $this->results() : [];
    }

    // find fuzzy title
    public function search_fuzzy_title( string $title, int $distance = 2): array
    {
        // search for entry
        $sql = "SELECT
                    *
                FROM `punycms_entries`
                WHERE levenshtein(`title`,:title) <= :distance
                -- ORDER BY `l_distance` ASC;";
        return ($this->query_execute($sql,['title'=>$title,'distance'=>$distance])) ? $this->results : [];
    }

    // find exact title
    public function search_title( string $title ): array
    {
        $sql = "SELECT * FROM `punycms_entries` WHERE `title` = :title;";
        return ($this->query_execute($sql,['title'=>$title])) ? $this->results() : [];
    }

    // create entry table
    public function create_entry_table(): bool
    {
        // check if entry table exists
        if ($this->valid_schema('punycms_entries')) return true;

        $sql = "CREATE TABLE IF NOT EXISTS `punycms_entries` (
                    `entry_id` INT NOT NULL AUTO_INCREMENT ,
                    `created` DATETIME NOT NULL ,
                    `edited` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
                    `created_by` VARCHAR( {$this->author_size} ) NOT NULL ,
                    `edited_by` VARCHAR( {$this->author_size} ) NOT NULL ,
                    `category` VARCHAR( {$this->category_size} ) NOT NULL ,
                    `title` VARCHAR( {$this->title_size} ) NOT NULL ,
                    `content` MEDIUMTEXT NOT NULL ,
                    PRIMARY KEY ( `entry_id` ),
                    UNIQUE KEY `title` ( `title` )
                ) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        return $this->query_execute($sql);
    }

    // create history table and update trigger
    public function create_history_table(): bool
    {
        // check if history table exists
        if ($this->valid_schema('punycms_history')) return true;

        // create the history table
        $sql = "CREATE TABLE IF NOT EXISTS `punycms_history` (
                    `history_id` INT NOT NULL AUTO_INCREMENT ,
                    `entry_id` INT NOT NULL ,
                    `created` DATETIME NOT NULL ,
                    `edited` TIMESTAMP ,
                    `created_by` VARCHAR( {$this->author_size} ) NOT NULL ,
                    `edited_by` VARCHAR( {$this->author_size} ) NOT NULL ,
                    `category` VARCHAR( {$this->category_size} ) NOT NULL ,
                    `title` VARCHAR( {$this->title_size} ) NOT NULL ,
                    `content` MEDIUMTEXT NOT NULL ,
                    PRIMARY KEY ( `history_id` )
                ) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        if (!$this->query_execute($sql)) return false;

        // create history trigger
        $sql = 'CREATE TRIGGER `punycms_history_trigger` AFTER UPDATE ON `punycms_entries`
                FOR EACH ROW
                IF ( OLD.edited <> NEW.edited ) THEN
                    INSERT INTO punycms_history (
                        `entry_id`
                        ,`created`
                        ,`edited`
                        ,`created_by`
                        ,`edited_by`
                        ,`category`
                        ,`title`
                        ,`content`
                    ) VALUES (
                        OLD.`entry_id`
                        , OLD.`created`
                        , OLD.`edited`
                        , OLD.`created_by`
                        , OLD.`edited_by`
                        , OLD.`category`
                        , OLD.`title`
                        , OLD.`content`
                    );
                END IF;';
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
                CREATE DEFINER=`" . NESTBOX_DB_USER . "`@`". NESTBOX_DB_HOST ."` FUNCTION `levenshtein`( s1 VARCHAR(255), s2 VARCHAR(255) ) RETURNS int
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
    public function fetch_entry_table( string $orderBy = "", string $sort = "" ): array
    {
        $orderBy = (!empty($orderBy) && $this->valid_schema('punycms_entries', $orderBy)) ? $orderBy : 'created';
        $sort = ( in_array( strtoupper( $sort ), array( 'ASC', 'DESC' ))) ? strtoupper( $sort ) : 'ASC';
        $sql = "SELECT * FROM `punycms_entries` ORDER BY `{$orderBy}` {$sort};";

        return ($this->query_execute($sql)) ? $this->results() : [];
    }

    // get entry by ID
    public function fetch_entry( int $entryID ): array
    {
        $sql = "SELECT * FROM `punycms_entries` WHERE `entry_id` = :entryID;";
        $res = $this->sqlexec( $sql, array( 'entryID' => $entryID ));
        return (count( $res ) != 1) ? [] : $res[0];
    }

    // get available categories
    public function fetch_categories(): array
    {
        $sql = "SELECT `category`, COUNT(*) as `count` FROM `punycms_entries` GROUP BY `category`;";
        return ($this->query_execute($sql)) ? $this->results() : [];
    }

    // get all entries of a certain category
    public function fetch_entries_by_category( string $category, string $orderBy = 'created', string $sort = '' ): array
    {
        $orderBy = ($this->valid_schema('punycms_entries', $orderBy)) ? $orderBy : 'created';
        $sort = ( in_array( strtoupper( $sort ), array( 'ASC', 'DESC' ))) ? strtoupper( $sort ) : 'ASC';

        $sql = "SELECT * FROM `punycms_entries` WHERE `category` = :category ORDER BY {$orderBy} {$sort};";

        return ($this->query_execute($sql,['category'=>$category])) ? $this->results() : [];
    }

    // get entry by category and title
    public function fetch_entry_by_category_and_title( string $category, string $title): array
    {
        $sql = "SELECT * FROM `punycms_entries` WHERE `category` = :category AND `title` = :title;";
        return ($this->query_execute($sql,['category'=>$category,'title'=>$title]))
            ? $this->results(true)
            : [];
    }
}
