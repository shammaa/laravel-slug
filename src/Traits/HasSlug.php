<?php

declare(strict_types=1);

namespace Shammaa\LaravelSlug\Traits;

use Shammaa\LaravelSlug\Services\SlugService;
use Shammaa\LaravelSlug\SlugOptions;

trait HasSlug
{
    // Properties are defined in the model, not in the trait
    // This avoids property conflict when model defines them

    /**
     * Boot the trait
     */
    protected static function bootHasSlug(): void
    {
        static::creating(function ($model) {
            // Check if using SlugOptions and doNotGenerateSlugsOnCreate is set
            $options = $model->getSlugOptions();
            if ($options && $options->getDoNotGenerateSlugsOnCreate()) {
                return;
            }

            if (!$model->shouldSkipSlugGeneration()) {
                $model->generateSlug();
            }
        });

        static::updating(function ($model) {
            if ($model->shouldSkipSlugGeneration()) {
                return;
            }
            
            if ($model->getRegenerateSlugOnUpdate()) {
                $sourceField = $model->getSlugSourceField();
                
                // For translated fields, check if translation changed
                if ($model->getSlugSourceIsTranslated()) {
                    // Always regenerate for translated fields (hard to detect changes)
                    $model->generateSlug();
                } elseif (is_string($sourceField) && $model->isDirty($sourceField)) {
                    // For normal fields, check if field is dirty
                    $model->generateSlug();
                } elseif (is_array($sourceField)) {
                    // For multiple fields, check if any field is dirty
                    $isDirty = false;
                    foreach ($sourceField as $field) {
                        if ($model->isDirty($field)) {
                            $isDirty = true;
                            break;
                        }
                    }
                    if ($isDirty) {
                        $model->generateSlug();
                    }
                } elseif (is_callable($sourceField)) {
                    // For callable, always regenerate (can't detect changes)
                    $model->generateSlug();
                }
            }
        });
    }

    /**
     * Generate slug for the model
     * Simple: Get value → Generate slug
     */
    public function generateSlug(): void
    {
        $sourceValue = $this->getSlugSourceValue();

        if (empty($sourceValue)) {
            return;
        }

        $slugService = app(SlugService::class);
        
        // Check if we're updating an existing model
        $excludeId = $this->exists ? $this->getKey() : null;
        
        // Get table name
        $table = $this->getTable();
        
        // Check if slug already exists and prevent overwrite is enabled
        $slugColumn = $this->getSlugColumn();
        if ($this->getPreventOverwrite() && $this->exists && $this->getAttribute($slugColumn)) {
            return; // Don't overwrite existing slug
        }
        
        // Get extra scope callback
        $extraScope = $this->getSlugExtraScope();
        
        // Generate unique slug
        $slug = $slugService->generateUnique(
            $sourceValue,
            $table,
            $slugColumn,
            $this->getSlugSeparator(),
            $excludeId,
            $extraScope,
            $this->getSlugSuffixStartFrom(),
            $this->getUseSuffixOnFirstOccurrence(),
            $this->getSlugSuffixGenerator()
        );

        $this->setAttribute($slugColumn, $slug);
    }


    /**
     * Get slug source value (supports translated fields, multiple fields, and callable)
     */
    protected function getSlugSourceValue(): ?string
    {
        $sourceField = $this->getSlugSourceField();

        // Check if source is a callable
        if (is_callable($sourceField)) {
            return call_user_func($sourceField, $this);
        }

        // Check if source is multiple fields (array)
        if (is_array($sourceField)) {
            $values = [];
            foreach ($sourceField as $field) {
                if ($this->getSlugSourceIsTranslated()) {
                    $value = $this->getTranslatedSlugSourceValue($field);
                } else {
                    $value = $this->getAttribute($field);
                }
                if (!empty($value)) {
                    $values[] = $value;
                }
            }
            return !empty($values) ? implode(' ', $values) : null;
        }

        // Single field (string)
        // Check if source field is translated
        if ($this->getSlugSourceIsTranslated()) {
            return $this->getTranslatedSlugSourceValue($sourceField);
        }

        // Try to get from attributes (normal field)
        $value = $this->getAttribute($sourceField);
        
        // If not found in attributes, try to get from a custom method
        if (empty($value) && method_exists($this, 'getSlugSourceValue')) {
            $value = $this->{'getSlugSourceValue'}();
        }

        return $value;
    }

    /**
     * Get translated slug source value
     * Supports multiple translation packages and pending translations (before save)
     */
    protected function getTranslatedSlugSourceValue(string $field, ?string $locale = null): ?string
    {
        $translationKey = $this->getSlugSourceTranslationKey() ?: $field;
        // Always use app locale by default (not a fixed locale)
        // Only use custom locale if explicitly provided via parameter or per-locale generation
        $locale = $locale ?: app()->getLocale();

        // Priority 1: Check pending translations (before save) - Most important for new models
        $pendingValue = $this->getPendingTranslation($translationKey, $locale);
        if ($pendingValue !== null) {
            return $pendingValue;
        }

        // Priority 2: Check if model has a translation method - Support multiple patterns
        // Pattern 1: translate('ar')->name (Astrotomic style)
        // Pattern 2: translate()->name (current locale)
        // Pattern 3: translate('name') (direct key access)
        if (method_exists($this, 'translate')) {
            try {
                // Try: translate($locale)->{$key} (Astrotomic style)
                $translation = $this->translate($locale);
                if ($translation) {
                    // Check if it's an object with the key as property
                    if (is_object($translation) && isset($translation->{$translationKey})) {
                        return $translation->{$translationKey};
                    }
                    // Check if it's an array with the key
                    if (is_array($translation) && isset($translation[$translationKey])) {
                        return $translation[$translationKey];
                    }
                }
                
                // Try: translate() without locale (uses current locale)
                $translation = $this->translate();
                if ($translation) {
                    if (is_object($translation) && isset($translation->{$translationKey})) {
                        return $translation->{$translationKey};
                    }
                    if (is_array($translation) && isset($translation[$translationKey])) {
                        return $translation[$translationKey];
                    }
                }
                
                // Try: translate($key) - direct key access (some packages)
                try {
                    $value = $this->translate($translationKey);
                    if (is_string($value) && !empty($value)) {
                        return $value;
                    }
                } catch (\Exception $e) {
                    // Not this pattern, continue
                }
            } catch (\Exception $e) {
                // Continue to next method
            }
        }

        // Pattern: trans($locale)->{$key} or trans()->{$key}
        if (method_exists($this, 'trans')) {
            try {
                $translation = $this->trans($locale);
                if ($translation) {
                    if (is_object($translation) && isset($translation->{$translationKey})) {
                        return $translation->{$translationKey};
                    }
                    if (is_array($translation) && isset($translation[$translationKey])) {
                        return $translation[$translationKey];
                    }
                }
                
                // Try without locale
                $translation = $this->trans();
                if ($translation) {
                    if (is_object($translation) && isset($translation->{$translationKey})) {
                        return $translation->{$translationKey};
                    }
                    if (is_array($translation) && isset($translation[$translationKey])) {
                        return $translation[$translationKey];
                    }
                }
            } catch (\Exception $e) {
                // Continue to next method
            }
        }

        // Pattern: getTranslation($locale) or getTranslation($key, $locale)
        if (method_exists($this, 'getTranslation')) {
            try {
                // Try: getTranslation($locale)->{$key}
                $translation = $this->getTranslation($locale);
                if ($translation) {
                    if (is_object($translation) && isset($translation->{$translationKey})) {
                        return $translation->{$translationKey};
                    }
                    if (is_array($translation) && isset($translation[$translationKey])) {
                        return $translation[$translationKey];
                    }
                }
                
                // Try: getTranslation($key, $locale) - direct key access
                try {
                    $value = $this->getTranslation($translationKey, $locale);
                    if (is_string($value) && !empty($value)) {
                        return $value;
                    }
                } catch (\Exception $e) {
                    // Not this pattern, continue
                }
                
                // Try: getTranslation($key) - without locale (uses current)
                try {
                    $value = $this->getTranslation($translationKey);
                    if (is_string($value) && !empty($value)) {
                        return $value;
                    }
                } catch (\Exception $e) {
                    // Not this pattern, continue
                }
            } catch (\Exception $e) {
                // Continue to next method
            }
        }

        // Priority 3: Check if model has translations relationship (e.g., Spatie Translatable, Astrotomic)
        if (method_exists($this, 'translations')) {
            try {
                // Try to eager load if not loaded
                if (!$this->relationLoaded('translations')) {
                    $this->load('translations');
                }
                
                if ($this->relationLoaded('translations')) {
                    $translation = $this->translations->where('locale', $locale)->first();
                    if ($translation) {
                        // Check if it's a model with the key as attribute
                        if (isset($translation->{$translationKey})) {
                            return $translation->{$translationKey};
                        }
                        // Check if it's a key-value pair (Spatie style)
                        if (method_exists($translation, 'getAttribute') && $translation->getAttribute('key') === $translationKey) {
                            return $translation->getAttribute('value');
                        }
                    }
                }
            } catch (\Exception $e) {
                // Continue to next method
            }
        }

        // Priority 4: Check if model has a getter method for the field (e.g., getNameAttribute())
        $getterMethod = 'get' . ucfirst($field) . 'Attribute';
        if (method_exists($this, $getterMethod)) {
            try {
                $value = $this->{$getterMethod}();
                if (!empty($value) && is_string($value)) {
                    return $value;
                }
            } catch (\Exception $e) {
                // Continue to next method
            }
        }

        // Priority 5: Try to access via magic property (works with many translation packages)
        try {
            $value = $this->{$field};
            if (!empty($value) && is_string($value)) {
                return $value;
            }
        } catch (\Exception $e) {
            // Ignore exceptions from magic properties
        }

        // Priority 6: Check if there's a translations table (e.g., lexi_translations, translations)
        // This works for both existing and new models
        $translation = $this->getTranslationFromTable($translationKey, $locale);
        if ($translation) {
            return $translation;
        }

        // Priority 7: Check if model has getTranslations method (laravel-translations package)
        if (method_exists($this, 'getTranslations')) {
            try {
                $translations = $this->getTranslations($locale);
                if ($translations && isset($translations[$translationKey])) {
                    return $translations[$translationKey];
                }
            } catch (\Exception $e) {
                // Continue
            }
        }

        // Priority 8: Check attributes directly (some packages store pending translations here)
        $value = $this->getAttribute($field);
        if (!empty($value) && is_string($value)) {
            return $value;
        }

        return null;
    }

    /**
     * Get pending translation (before save) - supports multiple translation packages
     * Comprehensive support for all translation patterns
     */
    protected function getPendingTranslation(string $key, string $locale): ?string
    {
        // Method 1: Check if model has pendingTranslations property (custom implementations)
        if (property_exists($this, 'pendingTranslations') && isset($this->pendingTranslations)) {
            $pending = $this->pendingTranslations;
            if (isset($pending[$locale][$key])) {
                return $pending[$locale][$key];
            }
            // Also check if it's stored as [locale => [key => value]]
            if (isset($pending[$locale]) && is_array($pending[$locale]) && isset($pending[$locale][$key])) {
                return $pending[$locale][$key];
            }
        }

        // Method 2: Check if model has getPendingTranslation method
        if (method_exists($this, 'getPendingTranslation')) {
            try {
                $value = $this->getPendingTranslation($key, $locale);
                if ($value) {
                    return $value;
                }
            } catch (\Exception $e) {
                // Continue
            }
        }

        // Method 3: Check attributes for translation keys (some packages use this)
        $translationAttribute = "{$key}_{$locale}";
        $value = $this->getAttribute($translationAttribute);
        if (!empty($value) && is_string($value)) {
            return $value;
        }

        // Method 4: Check if model has translations array in attributes
        $translations = $this->getAttribute('translations');
        if (is_array($translations)) {
            // Pattern: translations[locale][key]
            if (isset($translations[$locale][$key])) {
                return $translations[$locale][$key];
            }
            // Pattern: translations[key][locale] (alternative structure)
            if (isset($translations[$key][$locale])) {
                return $translations[$key][$locale];
            }
        }

        // Method 5: For laravel-translations package and similar - check pending translations storage
        if (method_exists($this, 'setTranslation')) {
            // Try to get from a potential pending translations storage
            // This is package-specific, so we check common patterns
            try {
                $reflection = new \ReflectionClass($this);
                $properties = $reflection->getProperties(\ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PRIVATE);
                
                foreach ($properties as $property) {
                    $property->setAccessible(true);
                    $value = $property->getValue($this);
                    
                    // Pattern: [locale => [key => value]]
                    if (is_array($value) && isset($value[$locale][$key])) {
                        return $value[$locale][$key];
                    }
                    // Pattern: [key => [locale => value]]
                    if (is_array($value) && isset($value[$key][$locale])) {
                        return $value[$key][$locale];
                    }
                }
            } catch (\Exception $e) {
                // Reflection might fail, continue
            }
        }

        // Method 6: Try translate() method for pending translations (before save)
        // Some packages allow: translate('ar')->name or translate()->name
        if (method_exists($this, 'translate')) {
            try {
                // Try with locale
                $translation = $this->translate($locale);
                if ($translation) {
                    if (is_object($translation) && isset($translation->{$key})) {
                        return $translation->{$key};
                    }
                    if (is_array($translation) && isset($translation[$key])) {
                        return $translation[$key];
                    }
                }
                
                // Try without locale (current locale)
                $translation = $this->translate();
                if ($translation) {
                    if (is_object($translation) && isset($translation->{$key})) {
                        return $translation->{$key};
                    }
                    if (is_array($translation) && isset($translation[$key])) {
                        return $translation[$key];
                    }
                }
            } catch (\Exception $e) {
                // Continue
            }
        }

        // Method 7: Try direct key access translate($key) or getTranslation($key)
        if (method_exists($this, 'translate')) {
            try {
                $value = $this->translate($key);
                if (is_string($value) && !empty($value)) {
                    return $value;
                }
            } catch (\Exception $e) {
                // Not this pattern
            }
        }

        if (method_exists($this, 'getTranslation')) {
            try {
                $value = $this->getTranslation($key);
                if (is_string($value) && !empty($value)) {
                    return $value;
                }
            } catch (\Exception $e) {
                // Not this pattern
            }
        }

        return null;
    }

    /**
     * Get translation from translations table (for custom implementations like lexi_translations)
     * Supports multiple table structures
     */
    protected function getTranslationFromTable(string $key, string $locale): ?string
    {
        // Skip if model doesn't exist yet (no ID)
        if (!$this->exists || !$this->getKey()) {
            return null;
        }

        // Try common translation table names
        $possibleTables = ['lexi_translations', 'translations', 'model_translations', 'translation_translations'];
        
        foreach ($possibleTables as $table) {
            if (!\Illuminate\Support\Facades\Schema::hasTable($table)) {
                continue;
            }

            try {
                $modelClass = get_class($this);
                
                // Try different table structures
                $queries = [
                    // Structure 1: key-value with translatable_type/translatable_id
                    function() use ($table, $modelClass, $key, $locale) {
                        return \Illuminate\Support\Facades\DB::table($table)
                            ->where('translatable_type', $modelClass)
                            ->where('translatable_id', $this->getKey())
                            ->where('key', $key)
                            ->where('locale', $locale)
                            ->value('value');
                    },
                    // Structure 2: Column-based (laravel-translations style)
                    function() use ($table, $modelClass, $key, $locale) {
                        return \Illuminate\Support\Facades\DB::table($table)
                            ->where('translatable_type', $modelClass)
                            ->where('translatable_id', $this->getKey())
                            ->where('locale', $locale)
                            ->value($key);
                    },
                    // Structure 3: Spatie style with model_id/model_type
                    function() use ($table, $modelClass, $key, $locale) {
                        return \Illuminate\Support\Facades\DB::table($table)
                            ->where('model_type', $modelClass)
                            ->where('model_id', $this->getKey())
                            ->where('key', $key)
                            ->where('locale', $locale)
                            ->value('value');
                    },
                ];

                foreach ($queries as $query) {
                    try {
                        $translation = $query();
                        if ($translation) {
                            return $translation;
                        }
                    } catch (\Exception $e) {
                        // Try next query structure
                        continue;
                    }
                }
            } catch (\Exception $e) {
                // Table structure might be different, continue to next table
                continue;
            }
        }

        return null;
    }

    /**
     * Check if slug source field is translated
     */
    public function getSlugSourceIsTranslated(): bool
    {
        // Check if using SlugOptions
        $options = $this->getSlugOptions();
        if ($options) {
            return $options->getSourceIsTranslated();
        }

        // Fallback to properties
        return property_exists($this, 'slugSourceIsTranslated') && isset($this->slugSourceIsTranslated)
            ? $this->slugSourceIsTranslated
            : false;
    }

    /**
     * Set if slug source field is translated
     */
    public function setSlugSourceIsTranslated(bool $isTranslated): self
    {
        $this->slugSourceIsTranslated = $isTranslated;
        return $this;
    }

    /**
     * Get slug source translation key (if different from source field)
     */
    public function getSlugSourceTranslationKey(): ?string
    {
        // Check if using SlugOptions
        $options = $this->getSlugOptions();
        if ($options && $options->getSourceTranslationKey() !== null) {
            return $options->getSourceTranslationKey();
        }

        // Fallback to properties
        return property_exists($this, 'slugSourceTranslationKey') && isset($this->slugSourceTranslationKey)
            ? $this->slugSourceTranslationKey
            : null;
    }

    /**
     * Set slug source translation key
     */
    public function setSlugSourceTranslationKey(?string $key): self
    {
        $this->slugSourceTranslationKey = $key;
        return $this;
    }

    /**
     * Get slug source translation locale
     * Returns null by default to use app()->getLocale() automatically
     */
    public function getSlugSourceTranslationLocale(): ?string
    {
        // Always return null to use app locale automatically
        // This ensures slug is generated from current site language, not a fixed locale
        return null;
    }

    /**
     * Set slug source translation locale
     * Note: Setting a fixed locale is not recommended. The package automatically uses app()->getLocale()
     * This method is kept for backward compatibility but has no effect.
     * 
     * @deprecated Use app()->setLocale() instead to change the site language
     */
    public function setSlugSourceTranslationLocale(?string $locale): self
    {
        // No-op: We always use app()->getLocale() for flexibility
        // This ensures slug matches the current site language automatically
        return $this;
    }


    /**
     * Get slug source field (supports string, array, or callable)
     */
    public function getSlugSourceField(): string|array|callable
    {
        // Check if using SlugOptions
        $options = $this->getSlugOptions();
        if ($options && $options->getGenerateSlugsFrom() !== null) {
            return $options->getGenerateSlugsFrom();
        }

        // Fallback to properties
        return property_exists($this, 'slugSourceField') && isset($this->slugSourceField)
            ? $this->slugSourceField
            : config('slug.default_source_field', 'name');
    }

    /**
     * Get slug options (if using SlugOptions pattern)
     */
    public function getSlugOptions(): ?SlugOptions
    {
        if (method_exists($this, 'slugOptions')) {
            return $this->slugOptions();
        }

        return null;
    }

    /**
     * Set slug source field
     */
    public function setSlugSourceField(string $field): self
    {
        $this->slugSourceField = $field;
        return $this;
    }

    /**
     * Get regenerate slug on update setting
     */
    public function getRegenerateSlugOnUpdate(): bool
    {
        // Check if using SlugOptions
        $options = $this->getSlugOptions();
        if ($options) {
            // If doNotGenerateSlugsOnUpdate is true, return false
            if ($options->getDoNotGenerateSlugsOnUpdate()) {
                return false;
            }
            return $options->getRegenerateOnUpdate();
        }

        // Fallback to properties
        return property_exists($this, 'regenerateSlugOnUpdate') && isset($this->regenerateSlugOnUpdate)
            ? $this->regenerateSlugOnUpdate
            : config('slug.regenerate_on_update', true);
    }

    /**
     * Set regenerate slug on update
     */
    public function setRegenerateSlugOnUpdate(bool $regenerate): self
    {
        $this->regenerateSlugOnUpdate = $regenerate;
        return $this;
    }

    /**
     * Get slug separator
     */
    public function getSlugSeparator(): string
    {
        // Check if using SlugOptions
        $options = $this->getSlugOptions();
        if ($options) {
            return $options->getSeparator();
        }

        // Fallback to properties
        return property_exists($this, 'slugSeparator') && isset($this->slugSeparator)
            ? $this->slugSeparator
            : config('slug.default_separator', '-');
    }

    /**
     * Set slug separator
     */
    public function setSlugSeparator(string $separator): self
    {
        $this->slugSeparator = $separator;
        return $this;
    }

    /**
     * Get slug column name
     */
    public function getSlugColumn(): string
    {
        // Check if using SlugOptions
        $options = $this->getSlugOptions();
        if ($options) {
            return $options->getSaveSlugsTo();
        }

        // Fallback to properties
        return property_exists($this, 'slugColumn') && isset($this->slugColumn)
            ? $this->slugColumn
            : config('slug.default_column', 'slug');
    }

    /**
     * Set slug column name
     */
    public function setSlugColumn(string $column): self
    {
        $this->slugColumn = $column;
        return $this;
    }

    /**
     * Manually regenerate slug
     */
    public function regenerateSlug(): self
    {
        $this->generateSlug();
        return $this;
    }

    /**
     * Find model by slug (alternative method name to avoid conflicts)
     * 
     * IMPORTANT: We use findWhereSlug() instead of findBySlug() to avoid conflicts
     * with other packages that define findBySlug() with different signatures
     * (e.g., Aliziodev\LaravelTaxonomy).
     * 
     * If you need findBySlug() and your model doesn't have conflicts, you can
     * add this method to your model:
     * 
     * public static function findBySlug(string $slug, array|string $columns = ['*']): ?static
     * {
     *     return static::findWhereSlug($slug, $columns);
     * }
     * 
     * @param string $slug
     * @param array|string $columns
     * @return static|null
     */
    public static function findWhereSlug(string $slug, array|string $columns = ['*']): ?static
    {
        $instance = new static();
        $slugColumn = $instance->getSlugColumn();
        
        return static::where($slugColumn, $slug)->first($columns);
    }

    /**
     * Check if slug generation should be skipped
     */
    protected function shouldSkipSlugGeneration(): bool
    {
        // Check if using SlugOptions
        $options = $this->getSlugOptions();
        if ($options && $options->getSkipGenerateWhen()) {
            $skipCallback = $options->getSkipGenerateWhen();
            if (is_callable($skipCallback)) {
                return call_user_func($skipCallback, $this);
            }
        }

        // Fallback to properties
        $skipCallback = $this->getSkipSlugGenerationCallback();
        if ($skipCallback && is_callable($skipCallback)) {
            return call_user_func($skipCallback, $this);
        }
        
        return false;
    }

    /**
     * Get skip slug generation callback
     */
    protected function getSkipSlugGenerationCallback(): ?callable
    {
        return property_exists($this, 'skipSlugGenerationWhen') && isset($this->skipSlugGenerationWhen)
            ? $this->skipSlugGenerationWhen
            : null;
    }

    /**
     * Set skip slug generation callback
     */
    public function skipSlugGenerationWhen(callable $callback): self
    {
        $this->skipSlugGenerationWhen = $callback;
        return $this;
    }

    /**
     * Get prevent overwrite setting
     */
    protected function getPreventOverwrite(): bool
    {
        // Check if using SlugOptions
        $options = $this->getSlugOptions();
        if ($options) {
            return $options->getPreventOverwrite();
        }

        // Fallback to properties
        return property_exists($this, 'preventSlugOverwrite') && isset($this->preventSlugOverwrite)
            ? $this->preventSlugOverwrite
            : false;
    }

    /**
     * Set prevent overwrite
     */
    public function preventSlugOverwrite(bool $prevent = true): self
    {
        $this->preventSlugOverwrite = $prevent;
        return $this;
    }

    /**
     * Get extra scope callback for uniqueness check
     */
    protected function getSlugExtraScope(): ?callable
    {
        // Check if using SlugOptions
        $options = $this->getSlugOptions();
        if ($options && $options->getExtraScope()) {
            return $options->getExtraScope();
        }

        // Fallback to properties
        return property_exists($this, 'slugExtraScope') && isset($this->slugExtraScope)
            ? $this->slugExtraScope
            : null;
    }

    /**
     * Set extra scope for uniqueness check
     */
    public function setSlugExtraScope(callable $callback): self
    {
        $this->slugExtraScope = $callback;
        return $this;
    }

    /**
     * Get slug suffix start from number
     */
    protected function getSlugSuffixStartFrom(): int
    {
        // Check if using SlugOptions
        $options = $this->getSlugOptions();
        if ($options) {
            return $options->getSuffixStartFrom();
        }

        // Fallback to properties
        return property_exists($this, 'slugSuffixStartFrom') && isset($this->slugSuffixStartFrom)
            ? $this->slugSuffixStartFrom
            : 1;
    }

    /**
     * Set slug suffix start from number
     */
    public function setSlugSuffixStartFrom(int $startFrom): self
    {
        $this->slugSuffixStartFrom = $startFrom;
        return $this;
    }

    /**
     * Get use suffix on first occurrence setting
     */
    protected function getUseSuffixOnFirstOccurrence(): bool
    {
        // Check if using SlugOptions
        $options = $this->getSlugOptions();
        if ($options) {
            return $options->getUseSuffixOnFirstOccurrence();
        }

        // Fallback to properties
        return property_exists($this, 'useSuffixOnFirstOccurrence') && isset($this->useSuffixOnFirstOccurrence)
            ? $this->useSuffixOnFirstOccurrence
            : false;
    }

    /**
     * Set use suffix on first occurrence
     */
    public function setUseSuffixOnFirstOccurrence(bool $use = true): self
    {
        $this->useSuffixOnFirstOccurrence = $use;
        return $this;
    }

    /**
     * Get slug suffix generator callback
     */
    protected function getSlugSuffixGenerator(): ?callable
    {
        // Check if using SlugOptions
        $options = $this->getSlugOptions();
        if ($options && $options->getSuffixGenerator()) {
            return $options->getSuffixGenerator();
        }

        // Fallback to properties
        return property_exists($this, 'slugSuffixGenerator') && isset($this->slugSuffixGenerator)
            ? $this->slugSuffixGenerator
            : null;
    }

    /**
     * Set slug suffix generator callback
     */
    public function setSlugSuffixGenerator(callable $callback): self
    {
        $this->slugSuffixGenerator = $callback;
        return $this;
    }
}

