<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox\Lorikeet;

use Supergnaw\Nestbox\Nestbox;

class Lorikeet extends Nestbox
{
    // vars
    protected string $image_save_directory = ".";
    protected string $image_thumbnail_directory = ".";
    protected bool $keep_aspect_ratio = true;
    protected int $max_width = 0;
    protected int $max_height = 0;
    protected int $max_filesize_mb = 2;
    protected bool $allow_bmp = true;
    protected bool $allow_gif = true;
    protected bool $allow_jpg = true;
    protected bool $allow_png = true;
    protected bool $allow_webp = true;
    protected string $convert_to_filetype = "webp";
    public array $errors = [];

    // constructor
    public function __construct(string $image_directory = null)
    {
        // database functions
        parent::__construct();
        // create class tables
        // $thi
        //// todo: use Nestbox->load_settings()s->create_lorikeet_tables(); instead
        $this->load_settings();
        $this->create_image_directory($image_directory);
    }

    private function create_lorikeet_tables(): bool
    {
        // check if entry table exists
        if (!$this->valid_schema('lorikeet_images')) {
            $sql = "CREATE TABLE IF NOT EXISTS `lorikeet_images` (
                        `image_id` VARCHAR( 64 ) NOT NULL ,
                        `image_title` VARCHAR( 128 ) NOT NULL ,
                        `image_caption` VARCHAR( 256 ) NULL ,
                        `edited` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT TIMESTAMP ,
                        `tags` MEDIUMTEXT NOT NULL ,
                        PRIMARY KEY ( `image_id` )
                    ) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
            if (!$this->query_execute($sql)) return false;
        }

        if (!$this->valid_schema('lorikeet_settings')) {
            // todo: create nestbox master table for settings and use that instead of individual ones
            // todo: add two columns: "module" and "datatype" so raw values can be stored and parsed properly
            $sql = "CREATE TABLE IF NOT EXISTS `lorikeet_settings` (
                        `setting_name` VARCHAR( 5 ) NOT NULL ,
                        `setting_value` VARCHAR( 5 ) NOT NULL ,
                        PRIMARY KEY ( `setting_name` )
                    ) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
            if (!$this->query_execute($sql)) return false;

            $sql = "INSERT INTO `lorikeet_settings` ( `setting_name`, `setting_value` )
                    VALUES
                        ('image_save_directory', :image_save_directory),
                        ('image_thumbnail_directory', :image_thumbnail_directory),
                        ('keep_aspect_ratio', :keep_aspect_ratio),
                        ('max_width', :max_width),
                        ('max_height', :max_height),
                        ('max_filesize_mb', :max_filesize_mb),
                        ('allow_bmp', :allow_bmp),
                        ('allow_gif', :allow_gif),
                        ('allow_jpg', :allow_jpg),
                        ('allow_png', :allow_png),
                        ('allow_webp', :allow_webp),
                        ('convert_to_filetype', :convert_to_filetype),
                        ('virus_total_api', :virus_total_api)
                    ;";
            $params = [
                "image_save_directory" => $this->image_save_directory,
                "image_thumbnail_directory" => $this->image_thumbnail_directory,
                "keep_aspect_ratio" => ($this->keep_aspect_ratio) ? "1" : "0",
                "max_width" => "{$this->max_width}",
                "max_height" => "{$this->max_height}",
                "max_filesize_mb" => "{$this->max_filesize_mb}",
                "allow_bmp" => ($this->allow_bmp) ? "1" : "0",
                "allow_gif" => ($this->allow_gif) ? "1" : "0",
                "allow_jpg" => ($this->allow_jpg) ? "1" : "0",
                "allow_png" => ($this->allow_png) ? "1" : "0",
                "allow_webp" => ($this->allow_webp) ? "1" : "0",
                "convert_to_filetype" => $this->convert_to_filetype,
                "virus_total_api" => ($this->virus_total_api),
            ];
            if (!$this->query_execute($sql, $params)) return false;
        }

        return true;
    }

    // todo: use Nestbox->load_settings() instead
    private function load_settings(): bool
    {
        return true;
    }

    public function get_settings(): array
    {
        if (!$this->load_settings()) return [];
        return [
            "image_save_directory" => $this->image_save_directory,
            "keep_aspect_ratio" => $this->keep_aspect_ratio,
            "max_width" => $this->max_width,
            "max_height" => $this->max_height,
            "max_filesize_mb" => $this->max_filesize_mb,
            "allow_bmp" => $this->allow_bmp,
            "allow_gif" => $this->allow_gif,
            "allow_jpg" => $this->allow_jpg,
            "allow_png" => $this->allow_png,
            "allow_webp" => $this->allow_webp,
            "convert_to_filetype" => $this->convert_to_filetype,
        ];
    }

    private function create_save_directory(string $image_directory = null): bool
    {
        die($image_directory);
        return true;
    }

    public function change_save_directory(): bool
    {
        return true;
    }

    public function create_thumbnail_directory(): bool
    {
        return true;
    }

    public function change_thumbnail_directory(): bool
    {
        return true;
    }

    public function upload_image(): bool
    {
        // verify file size is not zero
        // verify file extension is approved
        // verify file magic number
        // |  Ext  | First 12 Hex digits (x = variable)           | ASCII            |
        // | ----- | -------------------------------------------- | ---------------- |
        // |  .bmp | 42 4d xx xx xx xx xx xx xx xx xx xx xx xx xx | BM______________ |
        // |  .gif | 47 49 46 38 xx xx xx xx xx xx xx xx xx xx xx | GIF8____________ |
        // |  .jpg | ff d8 ff e0 xx xx xx xx xx xx xx xx xx xx xx | ????____________ |
        // |  .png | 89 50 4e 47 xx xx xx xx xx xx xx xx xx xx xx | .PNG____________ |
        // | .webp | 52 49 46 46 xx xx xx xx 57 45 42 50 56 50 38 | RIFF____WEBPVP8? |
        // verify file info fileinfo()
        // - https://www.php.net/manual/en/book.fileinfo.php
        // get file hash
        // verify image size getimagesize()
        // - https://www.php.net/manual/en/function.getimagesize.php
        // copy image contents from uploaded image to new image
        // scale image as defined in settings
        // change filetype as needed
        // save to target directory with source file hash
        // create thumbnail
        // add image to database with hash as id to prevent duplicates
        // - modify database if new image data was provided with duplicate
        return true;
    }

    // process image
    public function resize_image(): bool
    {
        return true;
    }

    public function convert_type(): bool
    {
        return true;
    }

    public function generate_thumbnail(): bool
    {
        return true;
    }


    // image database entries
    public function add_image(): bool
    {
        return true;
    }

    public function edit_image(): bool
    {
        return true;
    }

    public function delete_image(): bool
    {
        return true;
    }

    // image search
    public function search_by_id(string $id): array
    {
        return [];
    }

    public function search_by_title(string $title, bool $exact_match = true): array
    {
        return [];
    }

    public function search_by_caption(string $title, bool $exact_match = true): array
    {
        return [];
    }

    public function search_by_tags(array $tags, bool $match_all = false): array
    {
        return [];
    }

    public function image_search(string $id = "", string $title = "", string $caption = "", array $tags = []): array
    {
        return [];
    }

    public function display_image(string $id): void
    {
        return;
    }
}
