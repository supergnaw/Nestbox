<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox\Lorikeet;

use Supergnaw\Nestbox\Nestbox;

class Lorikeet extends Nestbox
{
    final protected const string PACKAGE_NAME = 'lorikeet';
    // settings variables
    public string $lorikeetImageSaveDirectory = ".";
    public string $lorikeetImageThumbnailDirectory = ".";
    public bool $lorikeetKeepAspectRatio = true;
    public int $lorikeetMaxWidth = 0;
    public int $lorikeetMaxHeight = 0;
    public int $lorikeetMaxFilesizeMb = 2;
    public bool $lorikeetAllowBmp = true;
    public bool $lorikeetAllowGif = true;
    public bool $lorikeetAllowJpg = true;
    public bool $lorikeetAllowPng = true;
    public bool $lorikeetAllowWebp = true;
    public string $lorikeetConvertToFiletype = "webp";
    public string $lorikeetVirusTotalApiKey = "";

    public function create_class_table_lorikeet_images(): bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS `lorikeet_images` (
                    `image_id` VARCHAR( 64 ) NOT NULL ,
                    `image_title` VARCHAR( 128 ) NOT NULL ,
                    `image_caption` VARCHAR( 256 ) NULL ,
                    `saved` NOT NULL DEFAULT CURRENT TIMESTAMP ,
                    `edited` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT TIMESTAMP ,
                    `tags` MEDIUMTEXT NOT NULL ,
                    PRIMARY KEY ( `image_id` )
                ) ENGINE = InnoDB DEFAULT CHARSET=UTF8MB4 COLLATE=utf8_unicode_ci;";

        return $this->query_execute($sql);
    }

    public function create_save_directory(string $image_directory = null): bool
    {
        die($image_directory);
        return true;
    }

    public function change_save_directory(): bool
    {
        // create new directory
        // move files from old directory to new directory
        // delete old files and directory
        return true;
    }

    public function create_thumbnail_directory(): bool
    {
        return true;
    }

    public function change_thumbnail_directory(): bool
    {
        // create new directory
        // move files from old directory to new directory
        // delete old files and directory
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
