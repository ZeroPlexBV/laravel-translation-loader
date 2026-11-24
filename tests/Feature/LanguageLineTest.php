<?php

use Spatie\TranslationLoader\LanguageLine;

it('can get a translation', function () {
    $languageLine = createLanguageLine('*', 'group', 'new', ['en' => 'english', 'nl' => 'nederlands']);

    expect($languageLine->getTranslation('en'))->toEqual('english')
        ->and($languageLine->getTranslation('nl'))->toEqual('nederlands');
});

it('can set a translation', function () {
    $languageLine = createLanguageLine('*', 'group', 'new', ['en' => 'english']);

    $languageLine->setTranslation('nl', 'nederlands');

    expect($languageLine->getTranslation('en'))->toEqual('english')
        ->and($languageLine->getTranslation('nl'))->toEqual('nederlands');
});

it('can set a translation on a fresh model', function () {
    $languageLine = new LanguageLine();

    $languageLine->setTranslation('nl', 'nederlands');

    expect($languageLine->getTranslation('nl'))->toEqual('nederlands');
});

it('doesnt show error when getting nonexistent translation', function () {
    $languageLine = createLanguageLine('*', 'group', 'new', ['nl' => 'nederlands']);
    expect($languageLine->getTranslation('en'))->toBeNull();
});

it('will get a fallback locale if doesnt exists', function () {
    $languageLine = createLanguageLine('*', 'group', 'new', ['en' => 'English']);
    expect($languageLine->getTranslation('es'))->toEqual('English');
});

it('can be created, changed and deleted without a namespace', function () {
    expect(trans('group.new'))->toEqual('group.new');

    $languageLine = LanguageLine::create(['group' => 'group', 'key' => 'new', 'text' => ['en' => 'english']]);
    flushIlluminateTranslatorCache();
    expect(trans('group.new'))->toEqual('english');

    $languageLine->setTranslation('en', 'changed')->save();
    flushIlluminateTranslatorCache();
    expect(trans('group.new'))->toEqual('changed');

    $languageLine->delete();
    flushIlluminateTranslatorCache();
    expect(trans('group.new'))->toEqual('group.new');
});

it('loads the texts of the application when no namespace is given', function () {
    createLanguageLine('other', 'group', 'key', ['en' => 'other namespace']);

    expect(LanguageLine::getTranslationsForGroup('en', 'group'))->toEqual(['key' => 'english'])
        ->and(app('translation.loader')->load('en', 'group'))->toEqual(['key' => 'english'])
        ->and(LanguageLine::getTranslationsForGroup('en', 'group', 'other'))->toEqual(['key' => 'other namespace']);
});
