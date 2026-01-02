# Laravel Slug - TODO & Future Improvements

## Version 2.0.0 (Major Release)

### 🎯 High Priority

#### 1. Multi-Locale Slug Generation Enhancements
**Status:** Implemented (v1.2.0) - Needs refinement
**Improvements:**
- Add option to customize slug format per locale (e.g., Arabic slugs with/without transliteration)
- Support locale-specific slug patterns
- Add conflict resolution strategies

```php
protected array $slugPatterns = [
    'ar' => 'transliterate', // or 'keep-arabic', 'ascii-only'
    'en' => 'lowercase',
    'tr' => 'turkish-chars',
];
```

#### 2. Automatic Slug Update on Translation Change
**Status:** Partially working
**Issue:** Slug doesn't regenerate when only translation changes
**Solution:**
```php
// Detect changes in translated source fields
protected function shouldRegenerateSlugForLocale(string $locale): bool
{
    // Check if translation for this locale changed
}
```

#### 3. Better Integration with Translation Packages
**Status:** Working with reflection - Can be improved
**Improvement:** Create adapters for popular packages

```php
// Adapters System
interface TranslationAdapterInterface
{
    public function setPendingTranslation(string $key, $value, string $locale);
    public function getPendingTranslation(string $key, string $locale);
}

// ShammaTranslationAdapter
// SpatieTranslationAdapter  
// AstrotomicTranslationAdapter
```

---

### 🔧 Medium Priority

#### 4. Slug History & Redirects
Track old slugs for SEO:

```php
// Schema
create_table('slug_history', [
    'model_type', 'model_id', 'locale', 'old_slug', 'new_slug', 'created_at'
]);

// Usage
$category->getSlugHistory('ar');
Route::get('/{oldSlug}', function($oldSlug) {
    // Auto-redirect to new slug
});
```

#### 5. Custom Slug Generators per Locale
Allow different slug generation strategies:

```php
protected array $slugGenerators = [
    'ar' => ArabicSlugGenerator::class,
    'en' => EnglishSlugGenerator::class,
    'zh' => ChineseSlugGenerator::class,
];
```

#### 6. Slug Preview/Validation
Add method to preview slug before saving:

```php
public function previewSlug(string $source, ?string $locale = null): string
public function validateSlug(string $slug, ?string $locale = null): bool
```

---

### 💡 Low Priority

#### 7. Slug Analytics
Track slug performance and clicks:
```php
public function getSlugViews(string $locale): int
public function getMostViewedLocaleSlug(): string
```

#### 8. Slug A/B Testing
Test different slug formats:
```php
public function testSlug(string $variant, array $locales): void
public function getSlugPerformance(string $variant): array
```

#### 9. Smart Slug Suggestions
AI-powered slug suggestions:
```php
public function suggestSlugs(string $text, string $locale): array
// Returns: ['suggested-slug-1', 'suggested-slug-2', ...]
```

---

## Performance Optimizations

### 10. Reduce Reflection Overhead
**Current:** Using reflection to detect `setTranslation` signature
**Improvement:** Cache reflection results or use config

```php
// In config/slug.php
'translation_adapters' => [
    HasTranslations::class => 'shammaa', // ($key, $value, $locale)
    Translatable::class => 'spatie',     // ($key, $locale, $value)
],
```

### 11. Batch Slug Generation
Generate slugs for multiple models at once:
```php
Slug::batchGenerate(Category::all(), ['ar', 'en']);
```

---

## Developer Experience

### 12. Better Error Messages
When slug generation fails:
- Show which locale failed
- Suggest fixes (e.g., "Source field 'name' is empty for locale 'ar'")
- Provide debugging info

### 13. Slug Testing Helpers
```php
// In tests
$this->assertSlugGenerated($model, 'expected-slug', 'ar');
$this->assertSlugUnique($model, 'ar');
```

### 14. Slug Commands
```php
php artisan slug:generate Category --locales=ar,en
php artisan slug:regenerate --all
php artisan slug:check-duplicates
```

---

## Breaking Changes Planned for v2.0

1. Remove `setTranslatedSlugAttribute` - replace with `setTranslatedSlugAttributeForLocale`
2. Make multi-locale slug generation default behavior
3. Require explicit slug column configuration (remove auto-detection)
4. Minimum Laravel version: 10.0
5. Minimum PHP version: 8.2

---

## Community Requests

- [ ] Support for Eloquent UUID slugs
- [ ] Integration with Laravel Nova
- [ ] Integration with Filament
- [ ] WordPress-style slug editing in admin
- [ ] Slug versioning with changelog

---

## Known Issues

### Issue #1: Slug not generated for empty source on create
**Workaround:** Manually call `$model->generateSlug()` after setting translations
**Target Fix:** v1.3.0

### Issue #2: Performance hit with large datasets
**Impact:** Noticeable when generating slugs for 1000+ records
**Target Fix:** v2.0.0 (batch generation)

---

**Last Updated:** 2026-01-02
**Current Stable Version:** v1.2.0
**Next Planned Release:** v1.3.0 (Bug fixes)
**Major Release:** v2.0.0 (Q3 2026)
