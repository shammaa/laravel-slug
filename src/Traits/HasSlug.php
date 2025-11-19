<?php

declare(strict_types=1);

namespace Shammaa\LaravelSlug\Traits;

use Shammaa\LaravelSlug\Services\SlugService;

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
            $model->generateSlug();
        });

        static::updating(function ($model) {
            if ($model->getRegenerateSlugOnUpdate() && $model->isDirty($model->getSlugSourceField())) {
                $model->generateSlug();
            }
        });
    }

    /**
     * Generate slug for the model
     */
    public function generateSlug(): void
    {
        $sourceField = $this->getSlugSourceField();
        $sourceValue = $this->getAttribute($sourceField);

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
            $this->getSlugColumn(),
            $this->getSlugSeparator(),
            $excludeId
        );

        $this->setAttribute($this->getSlugColumn(), $slug);
    }

    /**
     * Get slug source field
     */
    public function getSlugSourceField(): string
    {
        return $this->slugSourceField ?? config('slug.default_source_field', 'name');
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
        return $this->regenerateSlugOnUpdate ?? config('slug.regenerate_on_update', true);
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
        return $this->slugSeparator ?? config('slug.default_separator', '-');
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
        return $this->slugColumn ?? config('slug.default_column', 'slug');
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

