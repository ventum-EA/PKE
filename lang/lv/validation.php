<?php

declare(strict_types=1);

/**
 * Latvian validation messages.
 *
 * Without this file Laravel falls back to the built-in English messages,
 * which is why users saw "The email has already been taken." in a Latvian UI.
 * Only the rules actually used by the app are translated in detail; the
 * rest fall back to sensible generic wording.
 */
return [
    'accepted' => ':attribute laukam ir jābūt apstiprinātam.',
    'boolean' => ':attribute laukam jābūt patiess vai aplams.',
    'confirmed' => ':attribute apstiprinājums nesakrīt.',
    'current_password' => 'Parole ir nepareiza.',
    'date' => ':attribute nav derīgs datums.',
    'email' => ':attribute jābūt derīgai e-pasta adresei.',
    'exists' => 'Izvēlētais :attribute nav derīgs.',
    'image' => ':attribute jābūt attēlam.',
    'in' => 'Izvēlētais :attribute nav derīgs.',
    'integer' => ':attribute jābūt veselam skaitlim.',
    'max' => [
        'numeric' => ':attribute nedrīkst pārsniegt :max.',
        'file' => ':attribute nedrīkst pārsniegt :max kilobaitus.',
        'string' => ':attribute nedrīkst pārsniegt :max rakstzīmes.',
        'array' => ':attribute nedrīkst saturēt vairāk par :max elementiem.',
    ],
    'mimes' => ':attribute jābūt failam ar tipu: :values.',
    'min' => [
        'numeric' => ':attribute jābūt vismaz :min.',
        'file' => ':attribute jābūt vismaz :min kilobaitiem.',
        'string' => ':attribute jābūt vismaz :min rakstzīmes garam.',
        'array' => ':attribute jāsatur vismaz :min elementi.',
    ],
    'numeric' => ':attribute jābūt skaitlim.',
    'required' => ':attribute lauks ir obligāts.',
    'same' => ':attribute un :other laukiem jāsakrīt.',
    'size' => [
        'numeric' => ':attribute jābūt :size.',
        'file' => ':attribute jābūt :size kilobaitiem.',
        'string' => ':attribute jābūt :size rakstzīmes garam.',
        'array' => ':attribute jāsatur :size elementi.',
    ],
    'string' => ':attribute jābūt tekstam.',
    'unique' => 'Šāds :attribute jau ir aizņemts.',
    'url' => ':attribute jābūt derīgai saitei.',

    'attributes' => [
        'name' => 'lietotājvārds',
        'email' => 'e-pasts',
        'password' => 'parole',
        'password_confirmation' => 'paroles apstiprinājums',
        'current_password' => 'pašreizējā parole',
        'move' => 'gājiens',
        'pgn' => 'PGN',
        'result' => 'rezultāts',
        'locale' => 'valoda',
    ],
];
