<?php

declare(strict_types=1);

namespace Shammaa\LaravelSlug;

class SlugOptions
{
    protected mixed $generateSlugsFrom = null;
    protected string $saveSlugsTo = 'slug';
    protected string $separator = '-';
    protected bool $regenerateOnUpdate = true;
    protected bool $doNotGenerateSlugsOnCreate = false;
    protected bool $doNotGenerateSlugsOnUpdate = false;
    protected mixed $skipGenerateWhen = null;
    protected bool $preventOverwrite = false;
    protected mixed $extraScope = null;
    protected int $suffixStartFrom = 1;
    protected bool $useSuffixOnFirstOccurrence = false;
    protected mixed $suffixGenerator = null;
    protected bool $sourceIsTranslated = false;
    protected bool $slugIsTranslated = false;
    protected ?string $sourceTranslationKey = null;

    /**
     * Create new SlugOptions instance
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Set the field(s) or callable to generate slugs from
     */
    public function generateSlugsFrom(mixed $field): self
    {
        $this->generateSlugsFrom = $field;
        return $this;
    }

    /**
     * Set the column to save slugs to
     */
    public function saveSlugsTo(string $column): self
    {
        $this->saveSlugsTo = $column;
        return $this;
    }

    /**
     * Set the separator character
     */
    public function separator(string $separator): self
    {
        $this->separator = $separator;
        return $this;
    }

    /**
     * Don't generate slugs on create
     */
    public function doNotGenerateSlugsOnCreate(): self
    {
        $this->doNotGenerateSlugsOnCreate = true;
        return $this;
    }

    /**
     * Don't generate slugs on update
     */
    public function doNotGenerateSlugsOnUpdate(): self
    {
        $this->doNotGenerateSlugsOnUpdate = true;
        return $this;
    }

    /**
     * Skip slug generation when condition is met
     */
    public function skipGenerateWhen(callable $callback): self
    {
        $this->skipGenerateWhen = $callback;
        return $this;
    }

    /**
     * Prevent overwriting existing slugs
     */
    public function preventOverwrite(): self
    {
        $this->preventOverwrite = true;
        return $this;
    }

    /**
     * Add extra scope for uniqueness check (multi-tenant support)
     */
    public function extraScope(callable $callback): self
    {
        $this->extraScope = $callback;
        return $this;
    }

    /**
     * Set suffix starting number
     */
    public function startSlugSuffixFrom(int $startFrom): self
    {
        $this->suffixStartFrom = $startFrom;
        return $this;
    }

    /**
     * Use suffix on first occurrence
     */
    public function useSuffixOnFirstOccurrence(): self
    {
        $this->useSuffixOnFirstOccurrence = true;
        return $this;
    }

    /**
     * Set custom suffix generator
     */
    public function usingSuffixGenerator(callable $callback): self
    {
        $this->suffixGenerator = $callback;
        return $this;
    }

    /**
     * Mark source field as translated
     */
    public function sourceIsTranslated(bool $isTranslated = true): self
    {
        $this->sourceIsTranslated = $isTranslated;
        return $this;
    }

    /**
     * Mark slug column as translated
     */
    public function slugIsTranslated(bool $isTranslated = true): self
    {
        $this->slugIsTranslated = $isTranslated;
        return $this;
    }

    /**
     * Set source translation key
     */
    public function sourceTranslationKey(?string $key): self
    {
        $this->sourceTranslationKey = $key;
        return $this;
    }

    /**
     * Set regenerate on update
     */
    public function regenerateOnUpdate(bool $regenerate = true): self
    {
        $this->regenerateOnUpdate = $regenerate;
        return $this;
    }

    // Getters

    public function getGenerateSlugsFrom(): mixed
    {
        return $this->generateSlugsFrom;
    }

    public function getSaveSlugsTo(): string
    {
        return $this->saveSlugsTo;
    }

    public function getSeparator(): string
    {
        return $this->separator;
    }

    public function getDoNotGenerateSlugsOnCreate(): bool
    {
        return $this->doNotGenerateSlugsOnCreate;
    }

    public function getDoNotGenerateSlugsOnUpdate(): bool
    {
        return $this->doNotGenerateSlugsOnUpdate;
    }

    public function getSkipGenerateWhen(): ?callable
    {
        return $this->skipGenerateWhen;
    }

    public function getPreventOverwrite(): bool
    {
        return $this->preventOverwrite;
    }

    public function getExtraScope(): ?callable
    {
        return $this->extraScope;
    }

    public function getSuffixStartFrom(): int
    {
        return $this->suffixStartFrom;
    }

    public function getUseSuffixOnFirstOccurrence(): bool
    {
        return $this->useSuffixOnFirstOccurrence;
    }

    public function getSuffixGenerator(): ?callable
    {
        return $this->suffixGenerator;
    }

    public function getSourceIsTranslated(): bool
    {
        return $this->sourceIsTranslated;
    }

    public function getSlugIsTranslated(): bool
    {
        return $this->slugIsTranslated;
    }

    public function getSourceTranslationKey(): ?string
    {
        return $this->sourceTranslationKey;
    }

    public function getRegenerateOnUpdate(): bool
    {
        return $this->regenerateOnUpdate;
    }
}

