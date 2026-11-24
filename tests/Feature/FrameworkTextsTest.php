<?php

use Illuminate\Support\Arr;
use Illuminate\Translation\FileLoader;

function frameworkTexts(string $group): array
{
    return require dirname((new ReflectionClass(FileLoader::class))->getFileName()) . "/lang/en/$group.php";
}

function packageTexts(string $locale, string $group): array
{
    return require dirname(__DIR__, 2) . "/lang/$locale/$group.php";
}

/** The message texts of a group, without the parts that belong to the application. */
function messages(array $texts): array
{
    return Arr::dot(Arr::except($texts, ['custom', 'attributes']));
}

function placeholders(string $text): array
{
    preg_match_all('/:[a-z]+/', $text, $matches);

    return collect($matches[0])->unique()->sort()->values()->all();
}

function useApplicationTexts(string $path): void
{
    app()->instance('path.lang', $path);
    app()->forgetInstance('translation.loader');
    app()->forgetInstance('translator');
}

dataset('texts', [
    'en failed' => ['en', 'auth.failed', [], 'These credentials do not match our records.'],
    'en throttle' => ['en', 'auth.throttle', ['seconds' => 30], 'Too many login attempts. Please try again in 30 seconds.'],
    'en required' => ['en', 'validation.required', ['attribute' => 'name'], 'The name field is required.'],
    'en max' => ['en', 'validation.max.string', ['attribute' => 'name', 'max' => 5], 'The name field must not be greater than 5 characters.'],
    'en sent' => ['en', 'passwords.sent', [], 'We have emailed your password reset link.'],
    'en next' => ['en', 'pagination.next', [], 'Next &raquo;'],
    'nl failed' => ['nl', 'auth.failed', [], 'Deze combinatie van inloggegevens is niet bekend.'],
    'nl throttle' => ['nl', 'auth.throttle', ['seconds' => 30], 'Te veel inlogpogingen. Probeer het over 30 seconden opnieuw.'],
    'nl required' => ['nl', 'validation.required', ['attribute' => 'naam'], 'Het veld naam is verplicht.'],
    'nl max' => ['nl', 'validation.max.string', ['attribute' => 'naam', 'max' => 5], 'Het veld naam mag niet meer dan 5 tekens bevatten.'],
    'nl sent' => ['nl', 'passwords.sent', [], 'De link om het wachtwoord opnieuw in te stellen is per e-mail verstuurd.'],
    'nl next' => ['nl', 'pagination.next', [], 'Volgende &raquo;'],
    'de failed' => ['de', 'auth.failed', [], 'Diese Kombination aus Zugangsdaten ist nicht bekannt.'],
    'de throttle' => ['de', 'auth.throttle', ['seconds' => 30], 'Zu viele Anmeldeversuche. Bitte versuchen Sie es in 30 Sekunden erneut.'],
    'de required' => ['de', 'validation.required', ['attribute' => 'Name'], 'Das Feld Name ist erforderlich.'],
    'de max' => ['de', 'validation.max.string', ['attribute' => 'Name', 'max' => 5], 'Das Feld Name darf nicht mehr als 5 Zeichen enthalten.'],
    'de sent' => ['de', 'passwords.sent', [], 'Der Link zum Zurücksetzen des Passworts wurde per E-Mail gesendet.'],
    'de next' => ['de', 'pagination.next', [], 'Weiter &raquo;'],
]);

it('gives the sentence of a framework text', function (string $locale, string $key, array $replace, string $sentence) {
    app()->setLocale($locale);

    expect(__($key, $replace))->toBe($sentence);
})->with('texts');

it('gives a sentence for a validation error', function (string $locale, string $sentence) {
    app()->setLocale($locale);

    expect(validator([], ['name' => 'required'])->errors()->first('name'))->toBe($sentence);
})->with([
    'en' => ['en', 'The name field is required.'],
    'nl' => ['nl', 'Het veld name is verplicht.'],
    'de' => ['de', 'Das Feld name ist erforderlich.'],
]);

it('uses the field name the application gives', function () {
    app()->setLocale('nl');

    expect(validator([], ['name' => 'required'], [], ['name' => 'voornaam'])->errors()->first('name'))
        ->toBe('Het veld voornaam is verplicht.');
});

it('falls back to the English text for another language', function () {
    app()->setLocale('fr');

    expect(__('auth.failed'))->toBe('These credentials do not match our records.');
});

it('has every text of the installed Laravel version, with the same placeholders', function (string $locale, string $group) {
    $translated = messages(packageTexts($locale, $group));

    foreach (messages(frameworkTexts($group)) as $key => $text) {
        expect(array_key_exists($key, $translated))->toBeTrue("$locale $group.$key is missing");
        expect(placeholders($translated[$key]))->toBe(placeholders($text), "$locale $group.$key");
    }
})->with(['nl', 'de'])->with(['auth', 'pagination', 'passwords', 'validation']);

it('has the same texts in every language', function (string $group) {
    expect(array_keys(Arr::dot(packageTexts('de', $group))))->toBe(array_keys(Arr::dot(packageTexts('nl', $group))));
})->with(['auth', 'pagination', 'passwords', 'validation']);

it('prefers the text of the application', function () {
    useApplicationTexts(__DIR__ . '/../Fixtures/lang-override');
    app()->setLocale('nl');

    expect(__('auth.failed'))->toBe('tekst van de applicatie')
        ->and(__('auth.password'))->toBe('Het wachtwoord is onjuist.');
});

it('prefers the text in the database over all files', function () {
    useApplicationTexts(__DIR__ . '/../Fixtures/lang-override');
    createLanguageLine('*', 'auth', 'failed', ['nl' => 'tekst uit de database']);
    createLanguageLine('*', 'validation', 'required', ['nl' => 'vul :attribute in']);
    app()->setLocale('nl');

    expect(__('auth.failed'))->toBe('tekst uit de database')
        ->and(__('validation.required', ['attribute' => 'naam']))->toBe('vul naam in');
});
