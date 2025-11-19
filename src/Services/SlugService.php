<?php

declare(strict_types=1);

namespace Shammaa\LaravelSlug\Services;

use Illuminate\Support\Facades\DB;

class SlugService
{
    /**
     * Preserve original language characters
     */
    protected bool $preserveOriginal = true;

    /**
     * Use PHP Intl Transliterator for multilingual support
     */
    protected bool $useIntl = false;

    /**
     * Intl Transliterator instance
     */
    protected ?\Transliterator $transliterator = null;

    /**
     * Arabic to English transliteration map
     */
    protected array $arabicTransliteration = [
        'أ' => 'a', 'إ' => 'i', 'آ' => 'aa', 'ا' => 'a', 'ى' => 'a', 'ئ' => 'y',
        'ب' => 'b', 'ت' => 't', 'ث' => 'th', 'ج' => 'j', 'ح' => 'h', 'خ' => 'kh',
        'د' => 'd', 'ذ' => 'th', 'ر' => 'r', 'ز' => 'z', 'س' => 's', 'ش' => 'sh',
        'ص' => 's', 'ض' => 'd', 'ط' => 't', 'ظ' => 'z', 'ع' => 'a', 'غ' => 'gh',
        'ف' => 'f', 'ق' => 'q', 'ك' => 'k', 'ل' => 'l', 'م' => 'm', 'ن' => 'n',
        'ه' => 'h', 'و' => 'w', 'ي' => 'y', 'ة' => 'h', 'ء' => 'a',
        'أ' => 'a', 'إ' => 'i', 'آ' => 'aa', 'ا' => 'a', 'ى' => 'a',
    ];

    /**
     * Latin character transliteration
     */
    protected array $latinTransliteration = [
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A',
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
        'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
        'Ç' => 'C', 'ç' => 'c', 'Ñ' => 'N', 'ñ' => 'n', 'ß' => 'ss',
    ];

    /**
     * Arabic to English numbers
     */
    protected array $arabicNumbers = [
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ];

    /**
     * Characters to replace with separator
     */
    protected array $punctuationMarks = [
        '...' => ' ', '..' => ' ', '.' => ' ', '(' => ' ', ')' => ' ',
        '[' => ' ', ']' => ' ', '{' => ' ', '}' => ' ', '،' => ' ',
        '؛' => ' ', ':' => ' ', '"' => ' ', "'" => ' ', '`' => ' ',
        ',' => ' ', ';' => ' ', '!' => ' ', '?' => ' ', '؟' => ' ',
        '*' => ' ', '+' => ' ', '=' => ' ', '~' => ' ', '@' => ' ',
        '#' => ' ', '$' => ' ', '%' => ' ', '^' => ' ', '&' => ' ',
        '|' => ' ', '\\' => ' ', '/' => ' ', '–' => ' ', '—' => ' ',
    ];

    /**
     * Constructor - check if Intl extension is available
     */
    public function __construct()
    {
        $this->preserveOriginal = config('slug.preserve_original', true);
        
        // Only use Intl if we're not preserving original language
        if (!$this->preserveOriginal) {
            $useIntlConfig = config('slug.use_intl', true);
            $this->useIntl = $useIntlConfig && extension_loaded('intl') && class_exists('\Transliterator');
            
            if ($this->useIntl) {
                try {
                    // Use Intl Transliterator for multilingual support
                    // This supports: Arabic, French, Hindi, Persian, Russian, Chinese, Japanese, and many more
                    $this->transliterator = \Transliterator::create('Any-Latin; Latin-ASCII; Lower()');
                    
                    // If creation failed, fallback to manual transliteration
                    if (!$this->transliterator) {
                        $this->useIntl = false;
                    }
                } catch (\Exception $e) {
                    // If Intl fails, fallback to manual transliteration
                    $this->useIntl = false;
                }
            }
        }
    }

    /**
     * Generate slug from string
     */
    public function generate(string $string, string $separator = '-', ?string $fallback = null): string
    {
        if (empty(trim($string))) {
            return $fallback ?? $this->generateFallback();
        }

        // Ensure UTF-8 encoding
        if (!mb_check_encoding($string, 'UTF-8')) {
            $string = mb_convert_encoding($string, 'UTF-8');
        }

        // Remove HTML tags
        $string = strip_tags($string);

        // Remove quotes
        $quotes = ['"', "'", '`', '«', '»', '„', '‚', '‹', '›'];
        $string = str_replace($quotes, '', $string);

        // Preserve original language or transliterate
        if ($this->preserveOriginal) {
            // Keep original language characters - only convert Arabic numbers
            $string = str_replace(array_keys($this->arabicNumbers), array_values($this->arabicNumbers), $string);
        } else {
            // Transliterate to Latin characters
            if ($this->useIntl && $this->transliterator) {
                $string = $this->transliterator->transliterate($string);
            } else {
                // Fallback: Manual transliteration (Arabic + Latin)
                $string = $this->transliterateArabic($string);
                $string = str_replace(array_keys($this->latinTransliteration), array_values($this->latinTransliteration), $string);
            }
            
            // Convert Arabic numbers to English
            $string = str_replace(array_keys($this->arabicNumbers), array_values($this->arabicNumbers), $string);
        }

        // Replace punctuation marks with spaces
        $string = str_replace(array_keys($this->punctuationMarks), array_values($this->punctuationMarks), $string);

        // Clean multiple spaces
        $string = preg_replace('/\s+/u', ' ', $string);
        $string = trim($string);

        // Replace spaces with separator
        $string = str_replace(' ', $separator, $string);

        // Remove multiple separators
        $doubleSeparator = $separator . $separator;
        while (strpos($string, $doubleSeparator) !== false) {
            $string = str_replace($doubleSeparator, $separator, $string);
        }

        // Remove separator from start and end
        $string = trim($string, $separator);

        // Convert Latin letters to lowercase (only if not preserving original)
        if (!$this->preserveOriginal) {
            $string = preg_replace_callback('/[a-zA-Z]+/', function ($matches) {
                return strtolower($matches[0]);
            }, $string);
        }

        // If preserving original, keep the string as is (Unicode is supported in modern URLs)
        // No encoding needed - modern browsers and servers handle Unicode slugs correctly

        // If result is empty, use fallback
        if (empty($string) || $string === $separator) {
            return $fallback ?? $this->generateFallback();
        }

        return $string;
    }

    /**
     * Transliterate Arabic text to English
     */
    protected function transliterateArabic(string $text): string
    {
        $result = '';
        $length = mb_strlen($text, 'UTF-8');

        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($text, $i, 1, 'UTF-8');
            
            if (isset($this->arabicTransliteration[$char])) {
                $result .= $this->arabicTransliteration[$char];
            } else {
                $result .= $char;
            }
        }

        return $result;
    }

    /**
     * Generate unique slug by checking database
     */
    public function generateUnique(
        string $string,
        string $table,
        string $column = 'slug',
        string $separator = '-',
        ?int $excludeId = null
    ): string {
        $baseSlug = $this->generate($string, $separator);
        $slug = $baseSlug;
        $counter = 1;

        while ($this->slugExists($table, $column, $slug, $excludeId)) {
            $slug = $baseSlug . $separator . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if slug exists in database
     */
    protected function slugExists(string $table, string $column, string $slug, ?int $excludeId = null): bool
    {
        $query = DB::table($table)->where($column, $slug);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }


    /**
     * Generate fallback slug
     */
    protected function generateFallback(): string
    {
        return 'item-' . date('Y-m-d-H-i-s') . '-' . uniqid();
    }
}

