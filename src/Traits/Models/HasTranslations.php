<?php

namespace TheRealJanJanssens\PakkaCore\Traits\Models;

use TheRealJanJanssens\PakkaCore\Models\Translation;
use Illuminate\Support\Arr;

trait HasTranslations
{
    protected ?string $translationLocale = null;
    protected ?array $cachedTranslations = null;

    public function translations()
    {
        $query = Translation::query();

        foreach ($this->getTranslatableAttributes() as $attribute) {
            $translationId = $this->getAttribute($attribute);

            if ($translationId) {
                $query->orWhere(function ($q) use ($translationId, $attribute) {
                    $q->where('translation_id', $translationId)
                      ->where('input_name', $attribute);
                });
            }
        }

        return $query;
    }

    public function getTranslatableAttributes(): array
    {
        return is_array($this->translatable)
            ? $this->translatable
            : [];
    }

    public function getCasts(): array
    {
        return array_merge(
            parent::getCasts(),
            array_fill_keys($this->getTranslatableAttributes(), 'array')
        );
    }

    public function translationsFor($attributes)
    {
        $attributes = (array) $attributes;

        return $this->translations()
            ->whereIn('input_name', $attributes);
    }

    public function getTranslationLocale(): string
    {
        return $this->translationLocale ?? app()->getLocale();
    }

    public function setTranslationLocale(string $locale): static
    {
        $this->translationLocale = $locale;

        return $this;
    }

    public function getFallbackLocale(): string
    {
        return config('app.fallback_locale', 'en');
    }

    public function getAttribute($key)
    {
        if (in_array($key, $this->getTranslatableAttributes())) {
            $locale = $this->getTranslationLocale();

            // Get translations once and cache
            $translations = $this->getCachedTranslations();

            // Try to find translation in current locale
            $translation = $translations[$key][$locale] ?? null;

            // Fallback if missing
            if (!$translation && $locale !== $this->getFallbackLocale()) {
                $fallback = $this->getFallbackLocale();
                $translation = $translations[$key][$fallback] ?? null;
            }

            if ($translation) {
                return $this->castTranslationValue($key, $translation);
            }
        }

        return parent::getAttribute($key);
    }

    protected function getCachedTranslations(): array
    {
        if ($this->cachedTranslations !== null) {
            return $this->cachedTranslations;
        }

        $this->cachedTranslations = [];

        // If translations are already eager loaded
        $translations = $this->relationLoaded('translations')
            ? $this->getRelation('translations')
            : $this->translations()->get();

        foreach ($translations as $translation) {
            $this->cachedTranslations[$translation->input_name][$translation->locale] = $translation->value;
        }

        return $this->cachedTranslations;
    }

    protected function castTranslationValue(string $key, mixed $value): mixed
    {
        $casts = $this->getCasts();

        if (isset($casts[$key])) {
            switch ($casts[$key]) {
                case 'array':
                    return is_array($value) ? $value : json_decode($value, true);
                case 'json':
                    return json_decode($value, true);
                case 'int':
                    return (int) $value;
                case 'float':
                    return (float) $value;
                case 'bool':
                    return (bool) $value;
                case 'string':
                default:
                    return (string) $value;
            }
        }

        return $value;
    }

    /**
     * After a new model is created
     * It loops all translatable attributes For each one, it checks if a Translation already exists
     * If not, it creates an empty translation for the fallback locale
     * ✅ No duplicates
     * ✅ Auto-prepared translations
     */
    public static function bootHasTranslations()
    {
        static::created(function ($model) {
            $model->initializeTranslations();
        });
    }

    protected function initializeTranslations(): void
    {
        $translations = [];

        foreach ($this->getTranslatableAttributes() as $attribute) {
            $translationId = $this->getAttribute($attribute);

            if ($translationId) {
                // Check if translation records already exist
                $existing = Translation::where('translation_id', $translationId)
                    ->where('input_name', $attribute)
                    ->exists();

                if (!$existing) {
                    // Create an empty translation for fallback locale
                    $translations[] = [
                        'translation_id' => $translationId,
                        'input_name'     => $attribute,
                        'language_code'  => $this->getFallbackLocale(),
                        'value'          => '',
                    ];
                }
            }
        }

        if (!empty($translations)) {
            Translation::insert($translations);
        }
    }

}
