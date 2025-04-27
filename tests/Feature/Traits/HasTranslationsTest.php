<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use TheRealJanJanssens\PakkaCore\Models\Page;
use TheRealJanJanssens\PakkaCore\Models\Translation;
use function Pest\Laravel\{config};


uses(RefreshDatabase::class);

// beforeEach(function () {
//     // Here you can optionally seed default languages or configs
//     config()->set('app.fallback_locale', 'en');
// });

it('creates empty translations on page creation', function () {
    $page = Page::factory()->create([
        'name' => 1, // assuming translation_id 1
    ]);

    expect(Translation::where('translation_id', 1)
        ->where('input_name', 'name')
        ->where('language_code', 'en')
        ->exists())->toBeTrue();
});

it('returns translated attribute value dynamically', function () {
    $page = Page::factory()->create([
        'name' => 1,
    ]);

    Translation::create([
        'translation_id' => 1,
        'input_name'     => 'name',
        'language_code'  => 'en',
        'value'          => 'Test Title',
    ]);

    $freshPage = Page::find($page->id);

    expect($freshPage->name)->toBe('Test Title');
});

it('falls back to fallback locale if translation missing', function () {
    $page = Page::factory()->create([
        'name' => 2,
    ]);

    Translation::create([
        'translation_id' => 2,
        'input_name'     => 'name',
        'language_code'  => 'en',
        'value'          => 'Fallback Title',
    ]);

    $freshPage = Page::find($page->id);

    $freshPage->setTranslationLocale('nl'); // Dutch translation doesn't exist

    expect($freshPage->name)->toBe('Fallback Title');
});

it('can manually set and retrieve a translation', function () {
    $page = Page::factory()->create([
        'name' => 3,
    ]);

    $page->setTranslation('name', 'Nieuwe Titel', 'nl');

    expect($page->translate('name', 'nl'))->toBe('Nieuwe Titel');
});

it('can scope pages that have a translation', function () {
    $page = Page::factory()->create([
        'name' => 4,
    ]);

    Translation::create([
        'translation_id' => 4,
        'input_name'     => 'name',
        'language_code'  => 'en',
        'value'          => 'Scoped Title',
    ]);

    $pages = Page::whereTranslated('name', 'en')->get();

    expect($pages)->toHaveCount(1);
    expect($pages->first()->id)->toBe($page->id);
});
