<?php

declare(strict_types=1);

namespace Shammaa\LaravelSlug\Traits;

use Shammaa\LaravelSlug\Services\SlugService;

trait HasSlug
{
    /**
     * Source field for slug generation
     */
    protected string $slugSourceField = 'name';

    /**
     * Regenerate slug on update
     */
    protected bool $regenerateSlugOnUpdate = true;

    /**
     * Slug separator
     */
    protected string $slugSeparator = '-';

    /**
     * Slug column name
     */
    protected string $slugColumn = 'slug';

    /**
     * Boot the trait
     */
    protected static function bootHasSlug(): void
    {
        static::creating(function ($model) {
            $model->generateSlug();
        });

        static::updating(function ($model) {
            if ($model->regenerateSlugOnUpdate && $model->isDirty($model->slugSourceField)) {
                $model->generateSlug();
            }
        });
    }

    /**
     * Generate slug for the model
     */
    public function generateSlug(): void
    {
        $sourceValue = $this->getAttribute($this->slugSourceField);

        if (empty($sourceValue)) {
            return;
        }

        $slugService = app(SlugService::class);
        
        // Check if we're updating an existing model
        $excludeId = $this->exists ? $this->getKey() : null;
        
        // Get table name
        $table = $this->getTable();
        
        // Generate unique slug
        $slug = $slugService->generateUnique(
            $sourceValue,
            $table,
            $this->slugColumn,
            $this->slugSeparator,
            $excludeId
        );

        $this->setAttribute($this->slugColumn, $slug);
    }

    /**
     * Get slug source field
     */
    public function getSlugSourceField(): string
    {
        return $this->slugSourceField;
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
        return $this->regenerateSlugOnUpdate;
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
        return $this->slugSeparator;
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
        return $this->slugColumn;
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
}

