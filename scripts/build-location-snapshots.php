<?php

// Build-time only: run before removing nnjeim/world, never during HTTP requests.
declare(strict_types=1);
$root = dirname(__DIR__);
$source = $argv[1] ?? $root.'/vendor/nnjeim/world/resources/json';
$target = $root.'/resources/locations';
$lock = json_decode(file_get_contents($root.'/composer.lock'), true, 512, JSON_THROW_ON_ERROR);
$package = array_values(array_filter($lock['packages'], fn ($row) => $row['name'] === 'nnjeim/world'))[0] ?? null;
$package ??= ['version' => '1.1.39', 'source' => ['reference' => '1a419baf4e9dc6e83bdb392c8c8ebf328135856a']];
if (($package['version'] ?? null) !== '1.1.39') {
    throw new RuntimeException('Use the pinned nnjeim/world 1.1.39 datasets.');
}

if (is_file($target.'/manifest.json')) {
    $previousManifest = json_decode(file_get_contents($target.'/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
    foreach (['countries', 'states', 'cities'] as $dataset) {
        if (! hash_equals($previousManifest['inputs'][$dataset], hash_file('sha256', $source.'/'.$dataset.'.json'))) {
            throw new RuntimeException('Input checksum changed. Review and pin the dataset before regenerating.');
        }
    }
}

/** Stream objects without decoding the entire 53 MB city array. */
function records(string $file): Generator
{
    $stream = fopen($file, 'rb');
    if (! $stream) {
        throw new RuntimeException('Cannot open dataset.');
    }
    $depth = 0;
    $quoted = false;
    $escaped = false;
    $object = '';
    try {
        while (! feof($stream)) {
            $chunk = fread($stream, 65536);
            for ($i = 0, $length = strlen($chunk); $i < $length; $i++) {
                $char = $chunk[$i];
                if ($depth === 0 && $char !== '{') {
                    continue;
                }
                $object .= $char;
                if ($quoted) {
                    if ($escaped) {
                        $escaped = false;
                    } elseif ($char === '\\') {
                        $escaped = true;
                    } elseif ($char === '"') {
                        $quoted = false;
                    }
                } elseif ($char === '"') {
                    $quoted = true;
                } elseif ($char === '{') {
                    $depth++;
                } elseif ($char === '}' && --$depth === 0) {
                    yield json_decode($object, true, 512, JSON_THROW_ON_ERROR);
                    $object = '';
                }
            }
        }
        if ($depth !== 0) {
            throw new RuntimeException('Incomplete JSON dataset.');
        }
    } finally {
        fclose($stream);
    }
}

function options(array $names): array
{
    $names = array_values(array_unique($names));
    usort($names, fn ($a, $b) => strnatcasecmp($a, $b));

    return array_map(fn ($name) => ['value' => $name, 'label' => $name], $names);
}

function writeSnapshot(string $target, string $relative, array $data): string
{
    $path = $target.'/'.$relative;
    if (! is_dir(dirname($path)) && ! mkdir(dirname($path), 0755, true)) {
        throw new RuntimeException('Cannot create snapshot directory.');
    }
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
    if (file_put_contents($path, $json) === false) {
        throw new RuntimeException('Cannot write snapshot.');
    }

    return hash('sha256', $json);
}

$countries = [];
$catalog = [];
foreach (records($source.'/countries.json') as $row) {
    $code = $row['iso2'];
    if (! preg_match('/^[A-Z]{2}$/D', $code)) {
        throw new RuntimeException('Invalid country code.');
    }
    $countries[] = ['value' => $code, 'label' => $row['name']];
    $catalog[$code] = ['states' => [], 'cities' => []];
}
$stateParents = [];
foreach (records($source.'/states.json') as $row) {
    $code = $row['country_code'];
    if (! isset($catalog[$code])) {
        throw new RuntimeException('Unknown state parent.');
    }
    $name = $row['name'];
    $catalog[$code]['states'][] = $name;
    $catalog[$code]['cities'][$name] ??= [];
    $stateParents[$row['id']] = [$code, $name];
}
$cityCount = 0;
foreach (records($source.'/cities.json') as $row) {
    [$code, $name] = $stateParents[$row['state_id']] ?? throw new RuntimeException('Unknown city parent.');
    if ($row['country_code'] !== $code || $row['state_name'] !== $name) {
        throw new RuntimeException('Wrong city parent.');
    }
    $catalog[$code]['cities'][$name][] = $row['name'];
    $cityCount++;
}
usort($countries, fn ($a, $b) => strnatcasecmp($a['label'], $b['label']));
$manifest = [
    'schema' => 1,
    'source' => 'https://github.com/nnjeim/world',
    'version' => '1.1.39',
    'commit' => $package['source']['reference'],
    'license' => 'MIT',
    'counts' => ['countries' => count($countries), 'states' => count($stateParents), 'cities' => $cityCount],
    'inputs' => [],
    'countries_sha256' => writeSnapshot($target, 'countries.json', $countries),
    'country_files' => [],
];
foreach (['countries', 'states', 'cities'] as $name) {
    $manifest['inputs'][$name] = hash_file('sha256', $source.'/'.$name.'.json');
}
foreach ($catalog as $code => $data) {
    $index = ['states' => options($data['states']), 'cities' => []];
    foreach ($data['cities'] as $name => $cities) {
        $relative = 'cities/'.$code.'/'.hash('sha256', $name).'.json';
        $index['cities'][$name] = ['file' => $relative, 'sha256' => writeSnapshot($target, $relative, options($cities))];
    }
    $manifest['country_files'][$code] = writeSnapshot($target, 'countries/'.$code.'.json', $index);
}
writeSnapshot($target, 'manifest.json', $manifest);
echo json_encode($manifest['counts'])."\n";
