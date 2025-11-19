# Laravel Slug Package

A professional, multilingual slug generator for Laravel with automatic model integration. This package provides powerful slug generation with excellent support for preserving original language characters (Arabic, French, Hindi, Persian, Russian, Chinese, and many more) or transliterating them to Latin characters.

## Features

- ✅ **Automatic Model Integration** - Simply add the `HasSlug` trait to your model
- ✅ **Preserve Original Language** - Keeps original characters by default (Arabic, French, Hindi, Persian, etc.)
- ✅ **Multilingual Support** - Supports 100+ languages with or without transliteration
- ✅ **Configurable per Model** - Customize source field, separator, and regeneration behavior
- ✅ **Unique Slug Generation** - Automatically ensures slugs are unique in database
- ✅ **Auto-regeneration** - Optionally regenerate slug when source field changes
- ✅ **Number Conversion** - Converts Arabic numbers to English automatically
- ✅ **Clean & Professional** - Removes HTML tags, punctuation, and special characters
- ✅ **PHP Intl Integration** - Optional Intl Transliterator for transliteration mode

## Installation

### Step 1: Add Repository (One-time setup)

Since the package is not yet published on Packagist, add the repository first:

```bash
composer config repositories.laravel-slug vcs https://github.com/shammaa/laravel-slug
```

### Step 2: Install via Composer

```bash
composer require shammaa/laravel-slug
```

**That's it!** The package will be installed automatically.

---

**Alternative: Manual Repository Setup**

If you prefer to add the repository manually in `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/shammaa/laravel-slug"
        }
    ]
}
```

Then run:
```bash
composer require shammaa/laravel-slug
```

### Step 3: Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=slug-config
```

This will create `config/slug.php` in your project with default settings.

## Quick Start

### 1. Add Trait to Your Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Shammaa\LaravelSlug\Traits\HasSlug;

class Category extends Model
{
    use HasSlug;

    // Configure slug settings
    protected $slugSourceField = 'name';           // Source field for slug
    protected $regenerateSlugOnUpdate = true;      // Regenerate on update
    protected $slugSeparator = '-';                // Separator character
    protected $slugColumn = 'slug';                 // Column name (default: 'slug')

    protected $fillable = ['name', 'slug'];
}
```

**That's it!** The package will automatically:
- Generate slug when creating a new model
- Regenerate slug when updating (if enabled)
- Ensure slug is unique in database
- Preserve original language characters by default

### Example Usage

```php
// Create a new category with Arabic text
$category = Category::create([
    'name' => 'مقالات تقنية'  // Arabic text
]);

// Slug will be automatically generated: "مقالات-تقنية" (preserved)
// Or "maqalat-taqniya" if preserve_original = false

// Update the name
$category->update([
    'name' => 'مقالات برمجية'  // New Arabic text
]);

// Slug will be automatically regenerated: "مقالات-برمجية" (preserved)
```

## Configuration

### Global Configuration

Edit `config/slug.php` to set defaults:

```php
return [
    'default_separator' => '-',
    'default_column' => 'slug',
    'default_source_field' => 'name',
    'regenerate_on_update' => true,
    'preserve_original' => true,  // Keep original language characters
    'use_intl' => true,          // Use Intl for transliteration (if preserve_original = false)
];
```

### Model-Level Configuration

Each model can define its own slug settings:

```php
class Article extends Model
{
    use HasSlug;

    // Source field (required)
    protected $slugSourceField = 'title';

    // Regenerate slug when source field changes (default: true)
    protected $regenerateSlugOnUpdate = true;

    // Separator character (default: '-')
    protected $slugSeparator = '-';

    // Slug column name (default: 'slug')
    protected $slugColumn = 'slug';
}
```

## Language Preservation vs Transliteration

The package supports two modes:

### Mode 1: Preserve Original (Default)

**Configuration:**
```php
'preserve_original' => true
```

**Behavior:**
- Keeps original language characters in slug
- Works with all languages (Arabic, French, Hindi, Persian, Russian, Chinese, etc.)
- Only converts Arabic numbers to English
- Removes HTML tags and punctuation

**Examples:**
```php
// Arabic
'مقالات تقنية' → 'مقالات-تقنية'
'أخبار الرياضة' → 'أخبار-الرياضة'

// French
'Café français' → 'Café-français'
'Été à Paris' → 'Été-à-Paris'

// Persian/Farsi
'سلام دنیا' → 'سلام-دنیا'
'ایران' → 'ایران'

// Hindi
'हिन्दी भाषा' → 'हिन्दी-भाषा'
'नमस्ते दुनिया' → 'नमस्ते-दुनिया'

// Russian
'Привет мир' → 'Привет-мир'
'Русский язык' → 'Русский-язык'

// Chinese
'你好世界' → '你好世界'
'中文' → '中文'

// Mixed languages
'مقالات Technology' → 'مقالات-Technology'
'News أخبار' → 'News-أخبار'
'Café français و مقالات' → 'Café-français-و-مقالات'

// Arabic numbers (always converted to English)
'مقال ١٢٣' → 'مقال-123'
```

### Mode 2: Transliterate to Latin

**Configuration:**
```php
'preserve_original' => false
```

**Behavior:**
- Transliterates all characters to Latin
- Uses PHP Intl Extension if available (best quality)
- Falls back to manual transliteration maps if Intl not available
- Converts to lowercase

**Examples:**
```php
// Arabic - transliterated
'مقالات تقنية' → 'maqalat-taqniya'
'أخبار الرياضة' → 'akhbar-al-riyada'

// French - transliterated
'Café français' → 'cafe-francais'
'Été à Paris' → 'ete-a-paris'

// Persian/Farsi - transliterated
'سلام دنیا' → 'salam-donya'
'ایران' → 'iran'

// Hindi - transliterated (requires Intl)
'हिन्दी भाषा' → 'hindi-bhasa'
'नमस्ते दुनिया' → 'namaste-duniya'
```

## Advanced Usage

### Custom Separator

```php
class Tag extends Model
{
    use HasSlug;

    protected $slugSourceField = 'name';
    protected $slugSeparator = '_';  // Use underscore instead of dash
}
```

**Result:**
```php
'مقالات تقنية' → 'مقالات_تقنية'  // or 'maqalat_taqniya' if transliterated
```

### Disable Regeneration on Update

```php
class Post extends Model
{
    use HasSlug;

    protected $slugSourceField = 'title';
    protected $regenerateSlugOnUpdate = false;  // Keep original slug
}
```

**Use Case:** When you want to keep the original slug even if the title changes (for SEO purposes).

### Custom Slug Column

```php
class Product extends Model
{
    use HasSlug;

    protected $slugSourceField = 'name';
    protected $slugColumn = 'url_slug';  // Use different column name
}
```

### Manual Slug Generation

```php
use Shammaa\LaravelSlug\Facades\Slug;

// Generate slug from string (preserves original by default)
$slug = Slug::generate('مقالات تقنية', '-');
// Result: "مقالات-تقنية" (if preserve_original = true)
// Result: "maqalat-taqniya" (if preserve_original = false)

// Generate unique slug (checks database)
$slug = Slug::generateUnique(
    'مقالات تقنية',
    'categories',
    'slug',
    '-',
    $excludeId = null  // Exclude this ID when checking
);
```

### Regenerate Slug Manually

```php
$category = Category::find(1);
$category->regenerateSlug();
$category->save();
```

## Key Features Explained

### Automatic Uniqueness

Slugs are automatically made unique by appending numbers:

```php
// First category (preserve_original = true)
Category::create(['name' => 'مقالات']);
// Slug: "مقالات"

// Second category with same name
Category::create(['name' => 'مقالات']);
// Slug: "مقالات-1"

// Third category
Category::create(['name' => 'مقالات']);
// Slug: "مقالات-2"

// If preserve_original = false
Category::create(['name' => 'مقالات']);
// Slug: "maqalat"

Category::create(['name' => 'مقالات']);
// Slug: "maqalat-1"
```

### HTML Tag Removal

HTML tags are automatically removed:

```php
// preserve_original = true
'<h1>مقالات</h1>' → 'مقالات'
'<p>أخبار <strong>مهمة</strong></p>' → 'أخبار-مهمة'

// preserve_original = false
'<h1>مقالات</h1>' → 'maqalat'
'<p>أخبار <strong>مهمة</strong></p>' → 'akhbar-muhima'
```

### Punctuation Handling

Punctuation marks are properly handled:

```php
// preserve_original = true
'مقالات، تقنية!' → 'مقالات-تقنية'
'أخبار؟ مهمة.' → 'أخبار-مهمة'

// preserve_original = false
'مقالات، تقنية!' → 'maqalat-taqniya'
'أخبار؟ مهمة.' → 'akhbar-muhima'
```

## Supported Languages

The package supports **all languages** when `preserve_original = true`. When `preserve_original = false`, it uses transliteration which supports:

- ✅ **Arabic** (العربية)
- ✅ **English** (with accented characters: é, è, ê, ë, etc.)
- ✅ **French** (Français)
- ✅ **Hindi** (हिन्दी) - requires Intl
- ✅ **Persian/Farsi** (فارسی)
- ✅ **Russian** (Русский) - requires Intl
- ✅ **Chinese** (中文) - requires Intl
- ✅ **Japanese** (日本語) - requires Intl
- ✅ **Korean** (한국어) - requires Intl
- ✅ **Turkish** (Türkçe)
- ✅ **Greek** (Ελληνικά)
- ✅ **Hebrew** (עברית)
- ✅ **Thai** (ไทย) - requires Intl
- ✅ **Vietnamese** (Tiếng Việt) - requires Intl
- ✅ And 100+ more languages!

## API Reference

### SlugService Methods

#### `generate(string $string, string $separator = '-', ?string $fallback = null): string`

Generate a slug from a string.

**Parameters:**
- `$string` - The string to convert to slug
- `$separator` - Separator character (default: '-')
- `$fallback` - Fallback string if result is empty (optional)

**Returns:** Generated slug string

**Example:**
```php
Slug::generate('مقالات تقنية', '-');
// Returns: "مقالات-تقنية" (if preserve_original = true)
// Returns: "maqalat-taqniya" (if preserve_original = false)
```

#### `generateUnique(string $string, string $table, string $column = 'slug', string $separator = '-', ?int $excludeId = null): string`

Generate a unique slug by checking the database.

**Parameters:**
- `$string` - The string to convert to slug
- `$table` - Database table name
- `$column` - Slug column name (default: 'slug')
- `$separator` - Separator character (default: '-')
- `$excludeId` - ID to exclude when checking (optional)

**Returns:** Unique slug string

**Example:**
```php
Slug::generateUnique('مقالات', 'categories', 'slug', '-', $excludeId);
// Returns: "مقالات" or "مقالات-1" if exists (if preserve_original = true)
// Returns: "maqalat" or "maqalat-1" if exists (if preserve_original = false)
```

### HasSlug Trait Methods

#### `generateSlug(): void`

Manually generate slug for the model.

```php
$category = Category::find(1);
$category->generateSlug();
$category->save();
```

#### `regenerateSlug(): self`

Regenerate slug and return the model instance.

```php
$category = Category::find(1);
$category->regenerateSlug()->save();
```

#### `getSlugSourceField(): string`

Get the source field name.

#### `setSlugSourceField(string $field): self`

Set the source field name.

#### `getRegenerateSlugOnUpdate(): bool`

Get regeneration setting.

#### `setRegenerateSlugOnUpdate(bool $regenerate): self`

Set regeneration setting.

#### `getSlugSeparator(): string`

Get the separator character.

#### `setSlugSeparator(string $separator): self`

Set the separator character.

#### `getSlugColumn(): string`

Get the slug column name.

#### `setSlugColumn(string $column): self`

Set the slug column name.

## Complete Examples

### Example 1: Basic Category Model (Preserve Original)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Shammaa\LaravelSlug\Traits\HasSlug;

class Category extends Model
{
    use HasSlug;

    protected $slugSourceField = 'name';
    protected $regenerateSlugOnUpdate = true;
    protected $slugSeparator = '-';

    protected $fillable = ['name', 'slug'];
}

// Usage
$category = Category::create(['name' => 'مقالات تقنية']);
// Slug: "مقالات-تقنية"
```

### Example 2: Article Model with Title (Transliterate)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Shammaa\LaravelSlug\Traits\HasSlug;

class Article extends Model
{
    use HasSlug;

    protected $slugSourceField = 'title';
    protected $regenerateSlugOnUpdate = false;  // Keep original slug
    protected $slugSeparator = '-';

    protected $fillable = ['title', 'slug', 'content'];
}

// Make sure preserve_original = false in config/slug.php
// Usage
$article = Article::create(['title' => 'مقالات تقنية']);
// Slug: "maqalat-taqniya"
```

### Example 3: Product Model with Custom Column

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Shammaa\LaravelSlug\Traits\HasSlug;

class Product extends Model
{
    use HasSlug;

    protected $slugSourceField = 'name';
    protected $slugColumn = 'url_slug';  // Custom column name
    protected $slugSeparator = '_';

    protected $fillable = ['name', 'url_slug'];
}

// Usage
$product = Product::create(['name' => 'مقالات تقنية']);
// Slug in 'url_slug' column: "مقالات_تقنية"
```

## Requirements

- PHP >= 8.1
- Laravel >= 9.0
- Illuminate packages (support, database)
- **PHP Intl Extension** (optional but recommended for transliteration mode)

### Installing PHP Intl Extension

**Ubuntu/Debian:**
```bash
sudo apt-get install php-intl
sudo systemctl restart php-fpm  # or apache2/nginx
```

**CentOS/RHEL:**
```bash
sudo yum install php-intl
sudo systemctl restart php-fpm
```

**macOS (Homebrew):**
```bash
brew install php-intl
```

**Windows:**
Enable the `php_intl.dll` extension in your `php.ini` file:
```ini
extension=php_intl.dll
```

**Note:** 
- The package works **perfectly** without Intl extension when `preserve_original = true` (default)
- Intl extension is only needed for transliteration mode (`preserve_original = false`) with languages like Hindi, Russian, Chinese, etc.

## Troubleshooting

### Slug is not being generated

**Problem:** Slug column remains empty after creating a model.

**Solutions:**
1. Make sure the `slug` column exists in your database table
2. Check that `$slugSourceField` matches your actual field name
3. Verify the source field has a value before saving

### Slug contains HTML tags

**Problem:** HTML tags appear in the slug.

**Solution:** The package automatically removes HTML tags. If you see tags, make sure you're using the latest version.

### Duplicate slugs

**Problem:** Multiple models have the same slug.

**Solution:** The package automatically handles uniqueness. If you see duplicates, check:
1. The `slug` column has a unique index in database
2. The `generateUnique` method is being used (automatic with trait)

### Unicode characters not working

**Problem:** Unicode characters (Arabic, Chinese, etc.) appear as question marks or empty.

**Solutions:**
1. Make sure your database uses UTF-8 encoding
2. Set database charset to `utf8mb4`:
   ```php
   // config/database.php
   'charset' => 'utf8mb4',
   'collation' => 'utf8mb4_unicode_ci',
   ```
3. Ensure your PHP file is saved with UTF-8 encoding

## License

MIT License - feel free to use in commercial and personal projects.

## Support

For issues, questions, or contributions, please open an issue on GitHub.

## Author

Shadi Shammaa

---
