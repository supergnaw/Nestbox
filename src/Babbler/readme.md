# Babbler

## Settings

| Setting                | Description                                 | Default |
|:-----------------------|---------------------------------------------|:-------:|
| babblerAuthorSize      | defines author column character width       |  `32`   | 
| babblerCategorySize    | defines  category column character width    |  `64`   | 
| babblerSubCategorySize | defines sub_category column character width |  `64`   | 
| babblerTitleSize       | defines title column character width        |  `255`  |

## Usage

### Add Entry

```php
add_entry(
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
```

### Edit Entry

```php
edit_entry(
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
```

### Delete Entry

```php
delete_entry(int $entry_id): bool
```

### Search Entries

```php
search_entries(string $words, string $category = "*", bool $strict = true, int $buffer = 100): array
```

### Search Title

```php
search_title(string $title): array
```

### Search URL Title

```php
search_url_title(string $title): array
```

### Fetch Entry Table

```php
fetch_entry_table(string $orderBy = "", string $sort = "", int $limit = 50, int $start = 0): array
```

### Fetch Entry

```php
fetch_entry(int $entry_id): array
```

### Fetch Categories

```php
fetch_categories(): array
```

### Fetch Subcategories

```php
fetch_sub_categories(string $category = ''): array
```

### Fetch Entries by Category

```php
fetch_entries_by_category(string $category, string $sub_category = '', string $order_by = 'created', string $sort = '', int $start = 0, int $limit = 10): array
```

### Fetch Entry by Category and Title

```php
fetch_entry_by_category_and_title(string $category, string $title, string $sub_category = ''): array
```