<?php

namespace TheRealJanJanssens\PakkaCore\Traits\Models;

/**
 * 🎯 Quick explanation
 * Method:	                                    Purpose:
 * translate('title')	                        Fetch a specific translation manually
 * setTranslation('title', 'New Title')	        Set (and create if missing) a translation
 * scopeWhereTranslated('title')	            Query models that have a translation for 'title
 */
trait InteractsWithTranslations
{
    public function translate(string $attribute, ?string $locale = null): mixed
    {
        $locale = $locale ?? $this->getTranslationLocale();

        $translations = $this->getCachedTranslations();

        return $translations[$attribute][$locale] ?? null;
    }

    public function setTranslation(string $attribute, mixed $value, ?string $locale = null): static
    {
        $locale = $locale ?? $this->getTranslationLocale();

        $translation = $this->translationsFor($attribute)
            ->where('locale', $locale)
            ->first();

        if (!$translation) {
            // Create a new translation if it doesn't exist
            $translation = $this->translations()->make([
                'input_name' => $attribute,
                'locale' => $locale,
                'translation_id' => $this->getAttribute($attribute),
            ]);
        }

        $translation->value = is_array($value) ? json_encode($value) : $value;
        $translation->save();

        // Clear cache
        $this->cachedTranslations = null;

        return $this;
    }

    public function scopeWhereTranslated($query, string $attribute, ?string $locale = null)
    {
        $locale = $locale ?? app()->getLocale();

        return $query->whereHas('translations', function ($q) use ($attribute, $locale) {
            $q->where('input_name', $attribute)
              ->where('locale', $locale);
        });
    }
}
