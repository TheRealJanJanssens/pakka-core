![Tests](https://github.com/TheRealJanJanssens/pakka-core/actions/workflows/tests.yml/badge.svg)


# 📖 HasTranslations & InteractsWithTranslations

This package provides dynamic translation support for Eloquent models in Laravel.

## 🛠 Installation

Include the traits inside your model:

```php
use TheRealJanJanssens\PakkaCore\Traits\Models\HasTranslations;
use TheRealJanJanssens\PakkaCore\Traits\Models\InteractsWithTranslations;

class Product extends Model
{
    use HasTranslations, InteractsWithTranslations;

    protected array $translatable = [
        'title',
        'description',
    ];
}
```

---

## 🔥 Features

- **Dynamic translations**: Access translated attributes directly (`$product->title`)
- **Locale switching**: `$product->setTranslationLocale('nl')`
- **Automatic fallback**: Falls back to `app.fallback_locale` if translation is missing
- **Caching**: Translations are cached per model for performance
- **Eager loading**: Compatible with `$model->load('translations')`
- **Auto-initialize**: Creates empty translation records on `created` event
- **Helper methods**:
  - `$product->translate('title', 'nl')`
  - `$product->setTranslation('title', 'New Title', 'nl')`
- **Query scopes**:
  - `Product::whereTranslated('title', 'en')->get()`

---

## ✨ Usage

### Access translated attributes
```php
$product = Product::find(1);
echo $product->title;
```

### Switch locale
```php
$product->setTranslationLocale('nl');
echo $product->title;
```

### Manually get a translation
```php
$title = $product->translate('title', 'nl');
```

### Manually set a translation
```php
$product->setTranslation('title', 'Nieuwe Titel', 'nl');
```

### Query models that have translations
```php
$products = Product::whereTranslated('title', 'nl')->get();
```

### Auto-created empty translations
When a model is created, empty translations for each attribute are generated automatically for the fallback locale.

---

## 📚 Configuration

The fallback locale is taken from your `config/app.php`:

```php
'fallback_locale' => 'en',
```

You can override it per model by adding:

```php
protected ?string $fallbackLocale = 'en';
```

and calling:

```php
$product->setFallbackLocale('fr');
```

---

# 🎯 Requirements

- Laravel 10+
- PHP 8.2+

---

# 🚀 Future Ideas

- Translation validation rules
- JSON API translation responses
- Bulk translation import/export

---

# 📃 License

MIT License.
