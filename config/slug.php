<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Slug Separator
    |--------------------------------------------------------------------------
    |
    | The default separator used when generating slugs.
    |
    */
    'default_separator' => '-',

    /*
    |--------------------------------------------------------------------------
    | Default Slug Column
    |--------------------------------------------------------------------------
    |
    | The default column name for storing slugs in database.
    |
    */
    'default_column' => 'slug',

    /*
    |--------------------------------------------------------------------------
    | Default Source Field
    |--------------------------------------------------------------------------
    |
    | The default field name used as source for slug generation.
    |
    */
    'default_source_field' => 'name',

    /*
    |--------------------------------------------------------------------------
    | Regenerate Slug on Update
    |--------------------------------------------------------------------------
    |
    | Whether to regenerate slug when the source field is updated.
    |
    */
    'regenerate_on_update' => true,

    /*
    |--------------------------------------------------------------------------
    | Preserve Original Language
    |--------------------------------------------------------------------------
    |
    | If enabled, the package will preserve the original language characters
    | in the slug instead of transliterating them to Latin.
    |
    | Examples:
    | - Arabic: 'مقالات تقنية' → 'مقالات-تقنية' (preserved)
    | - French: 'Café français' → 'café-français' (preserved)
    | - Persian: 'سلام دنیا' → 'سلام-دنیا' (preserved)
    |
    | If disabled, the package will transliterate to Latin characters.
    |
    */
    'preserve_original' => true,

    /*
    |--------------------------------------------------------------------------
    | Use PHP Intl Extension
    |--------------------------------------------------------------------------
    |
    | If enabled and PHP Intl extension is available, the package will use
    | Intl Transliterator for transliteration (only used if preserve_original = false).
    |
    | If disabled or Intl is not available, the package will use manual
    | transliteration maps (Arabic + Latin characters).
    |
    */
    'use_intl' => true,
];

