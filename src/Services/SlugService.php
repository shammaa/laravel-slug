<?php

declare(strict_types=1);

namespace Shammaa\LaravelSlug\Services;

use Illuminate\Support\Facades\Log;
use Shammaa\LaravelSlug\Exceptions\InvalidSlugException;
use Shammaa\LaravelSlug\Services\SlugValidator;

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

        // Remove Arabic diacritics (including shadda) - Fix for Arabic text with diacritics
        $string = $this->removeArabicDiacritics($string);

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
     * Remove Arabic diacritics (including shadda) from text
     * This fixes issues with Arabic text containing diacritics like شدة (ّ)
     */
    protected function removeArabicDiacritics(string $text): string
    {
        // Arabic diacritics Unicode ranges
        // U+064B to U+065F: Arabic diacritics
        // U+0670: Arabic letter superscript alef
        // U+06D6 to U+06ED: Additional Arabic diacritics
        $arabicDiacritics = [
            // Common diacritics
            "\u{064B}", // Tanwin Fath (ً)
            "\u{064C}", // Tanwin Damm (ٌ)
            "\u{064D}", // Tanwin Kasr (ٍ)
            "\u{064E}", // Fatha (َ)
            "\u{064F}", // Damma (ُ)
            "\u{0650}", // Kasra (ِ)
            "\u{0651}", // Shadda (ّ) - THE PROBLEM!
            "\u{0652}", // Sukun (ْ)
            "\u{0653}", // Maddah (ٓ)
            "\u{0654}", // Hamza Above (ٔ)
            "\u{0655}", // Hamza Below (ٕ)
            "\u{0656}", // Subscript Alef (ٖ)
            "\u{0657}", // Inverted Damma (ٗ)
            "\u{0658}", // Mark Noon Ghunna (٘)
            "\u{0659}", // Zwarakay (ٙ)
            "\u{065A}", // Vowel Sign Small V Above (ٚ)
            "\u{065B}", // Vowel Sign Inverted Small V Above (ٛ)
            "\u{065C}", // Vowel Sign Dot Below (ٜ)
            "\u{065D}", // Reversed Damma (ٝ)
            "\u{065E}", // Fatha With Two Dots (ٞ)
            "\u{065F}", // Wavy Hamza Below (ٟ)
            "\u{0670}", // Arabic Letter Superscript Alef
            "\u{06D6}", // Arabic Small High Ligature Sad With Lam With Alef Maksura
            "\u{06D7}", // Arabic Small High Ligature Qaf With Lam With Alef Maksura
            "\u{06D8}", // Arabic Small High Meem Initial Form
            "\u{06D9}", // Arabic Small High Lam Alef
            "\u{06DA}", // Arabic Small High Jeem
            "\u{06DB}", // Arabic Small High Three Dots
            "\u{06DC}", // Arabic Small High Seen
            "\u{06DD}", // Arabic End Of Ayah
            "\u{06DE}", // Arabic Start Of Rub El Hizb
            "\u{06DF}", // Arabic Small High Rounded Zero
            "\u{06E0}", // Arabic Small High Upright Rectangular Zero
            "\u{06E1}", // Arabic Small High Dotless Head Of Khah
            "\u{06E2}", // Arabic Small High Meem Isolated Form
            "\u{06E3}", // Arabic Small Low Seen
            "\u{06E4}", // Arabic Small High Madda
            "\u{06E5}", // Arabic Small Waw
            "\u{06E6}", // Arabic Small Yeh
            "\u{06E7}", // Arabic Small High Yeh
            "\u{06E8}", // Arabic Small High Noon
            "\u{06E9}", // Arabic Placeholder Mark
            "\u{06EA}", // Arabic Empty Centre Low Stop
            "\u{06EB}", // Arabic Empty Centre High Stop
            "\u{06EC}", // Arabic Rounded High Stop With Filled Centre
            "\u{06ED}", // Arabic Small Low Meem
        ];

        // Remove all Arabic diacritics
        foreach ($arabicDiacritics as $diacritic) {
            $text = str_replace($diacritic, '', $text);
        }

        // Also use regex to remove any remaining combining diacritical marks (more comprehensive)
        // This catches any diacritics we might have missed
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', '', $text);

        return $text;
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
        ?int $excludeId = null,
        ?callable $extraScope = null,
        int $suffixStartFrom = 1,
        bool $useSuffixOnFirstOccurrence = false,
        ?callable $suffixGenerator = null
    ): string {
        $baseSlug = $this->generate($string, $separator);
        $slug = $baseSlug;
        $counter = $suffixStartFrom;
        $iteration = 0;

        // If useSuffixOnFirstOccurrence is true, always add suffix even if slug is unique
        if ($useSuffixOnFirstOccurrence) {
            $slug = $this->generateSuffix($baseSlug, $counter, $separator, $suffixGenerator, $iteration);
            $counter++;
            $iteration++;
        }

        while ($this->slugExists($table, $column, $slug, $excludeId, $extraScope)) {
            $slug = $this->generateSuffix($baseSlug, $counter, $separator, $suffixGenerator, $iteration);
            $counter++;
            $iteration++;
        }

        return $slug;
    }

    /**
     * Generate slug with suffix
     */
    protected function generateSuffix(
        string $baseSlug,
        int $counter,
        string $separator,
        ?callable $suffixGenerator,
        int $iteration
    ): string {
        if ($suffixGenerator && is_callable($suffixGenerator)) {
            $suffix = call_user_func($suffixGenerator, $baseSlug, $iteration);
            return $baseSlug . $separator . $suffix;
        }

        return $baseSlug . $separator . $counter;
    }

    /**
     * Check if slug exists in database
     */
    protected function slugExists(
        string $table,
        string $column,
        string $slug,
        ?int $excludeId = null,
        ?callable $extraScope = null
    ): bool {
        $query = DB::table($table)->where($column, $slug);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        // Apply extra scope if provided
        if ($extraScope && is_callable($extraScope)) {
            $extraScope($query);
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

