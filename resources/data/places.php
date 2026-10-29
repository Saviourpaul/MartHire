<?php

/*
 * Where things are on the hero map. A job's place is matched against `cities` first, then `countries`
 * (names, ISO codes and `aliases`, all lower case with accents removed). Anything unmatched, such as
 * "Remote, worldwide", simply gets no marker. Add more rows here as your jobs need them.
 *
 *   cities:    name => [country code, latitude, longitude]
 *   countries: code => [name, latitude, longitude] (the middle of the country)
 *   aliases:   other spelling => country code
 *
 * Flags live in public/asset/nearbyworks/flags/{code}.svg.
 */

return [
    'cities' => [
        'london' => ['GB', 51.51, -0.13], 'manchester' => ['GB', 53.48, -2.24], 'leeds' => ['GB', 53.80, -1.55],
        'bristol' => ['GB', 51.45, -2.59], 'birmingham' => ['GB', 52.49, -1.89], 'edinburgh' => ['GB', 55.95, -3.19],
        'glasgow' => ['GB', 55.86, -4.25], 'dublin' => ['IE', 53.35, -6.26],
        'paris' => ['FR', 48.86, 2.35], 'lyon' => ['FR', 45.76, 4.84],
        'berlin' => ['DE', 52.52, 13.40], 'hamburg' => ['DE', 53.55, 9.99], 'munich' => ['DE', 48.14, 11.58], 'frankfurt' => ['DE', 50.11, 8.68],
        'amsterdam' => ['NL', 52.37, 4.90], 'rotterdam' => ['NL', 51.92, 4.48], 'brussels' => ['BE', 50.85, 4.35],
        'madrid' => ['ES', 40.42, -3.70], 'barcelona' => ['ES', 41.39, 2.17], 'lisbon' => ['PT', 38.72, -9.14],
        'rome' => ['IT', 41.90, 12.50], 'milan' => ['IT', 45.46, 9.19], 'zurich' => ['CH', 47.38, 8.54], 'vienna' => ['AT', 48.21, 16.37],
        'stockholm' => ['SE', 59.33, 18.07], 'oslo' => ['NO', 59.91, 10.75], 'copenhagen' => ['DK', 55.68, 12.57], 'helsinki' => ['FI', 60.17, 24.94],
        'warsaw' => ['PL', 52.23, 21.01], 'prague' => ['CZ', 50.08, 14.44], 'bucharest' => ['RO', 44.43, 26.10], 'athens' => ['GR', 37.98, 23.73],
        'istanbul' => ['TR', 41.01, 28.98], 'kyiv' => ['UA', 50.45, 30.52],
        'lagos' => ['NG', 6.52, 3.38], 'abuja' => ['NG', 9.08, 7.40], 'accra' => ['GH', 5.60, -0.19], 'nairobi' => ['KE', -1.29, 36.82],
        'cape town' => ['ZA', -33.92, 18.42], 'johannesburg' => ['ZA', -26.20, 28.05], 'cairo' => ['EG', 30.04, 31.24], 'casablanca' => ['MA', 33.57, -7.59],
        'addis ababa' => ['ET', 9.03, 38.74], 'dar es salaam' => ['TZ', -6.79, 39.21], 'kampala' => ['UG', 0.35, 32.58], 'kigali' => ['RW', -1.95, 30.06],
        'dakar' => ['SN', 14.72, -17.47],
        'dubai' => ['AE', 25.20, 55.27], 'abu dhabi' => ['AE', 24.45, 54.38], 'riyadh' => ['SA', 24.71, 46.68], 'tel aviv' => ['IL', 32.09, 34.78],
        'mumbai' => ['IN', 19.08, 72.88], 'delhi' => ['IN', 28.61, 77.21], 'bengaluru' => ['IN', 12.97, 77.59], 'bangalore' => ['IN', 12.97, 77.59],
        'karachi' => ['PK', 24.86, 67.01], 'dhaka' => ['BD', 23.81, 90.41], 'colombo' => ['LK', 6.93, 79.86],
        'singapore' => ['SG', 1.35, 103.82], 'kuala lumpur' => ['MY', 3.14, 101.69], 'jakarta' => ['ID', -6.21, 106.85], 'manila' => ['PH', 14.60, 120.98],
        'bangkok' => ['TH', 13.76, 100.50], 'ho chi minh city' => ['VN', 10.82, 106.63], 'hanoi' => ['VN', 21.03, 105.85],
        'shanghai' => ['CN', 31.23, 121.47], 'beijing' => ['CN', 39.90, 116.40], 'hong kong' => ['HK', 22.32, 114.17],
        'tokyo' => ['JP', 35.68, 139.69], 'seoul' => ['KR', 37.57, 126.98],
        'sydney' => ['AU', -33.87, 151.21], 'melbourne' => ['AU', -37.81, 144.96], 'auckland' => ['NZ', -36.85, 174.76],
        'toronto' => ['CA', 43.65, -79.38], 'vancouver' => ['CA', 49.28, -123.12], 'montreal' => ['CA', 45.50, -73.57],
        'new york' => ['US', 40.71, -74.01], 'austin' => ['US', 30.27, -97.74], 'san francisco' => ['US', 37.77, -122.42],
        'los angeles' => ['US', 34.05, -118.24], 'chicago' => ['US', 41.88, -87.63], 'seattle' => ['US', 47.61, -122.33],
        'boston' => ['US', 42.36, -71.06], 'miami' => ['US', 25.76, -80.19],
        'mexico city' => ['MX', 19.43, -99.13], 'sao paulo' => ['BR', -23.55, -46.63], 'rio de janeiro' => ['BR', -22.91, -43.17],
        'buenos aires' => ['AR', -34.60, -58.38], 'santiago' => ['CL', -33.45, -70.67], 'bogota' => ['CO', 4.71, -74.07], 'lima' => ['PE', -12.05, -77.04],
    ],

    'countries' => [
        'GB' => ['United Kingdom', 54.0, -2.5], 'IE' => ['Ireland', 53.2, -8.0], 'FR' => ['France', 46.6, 2.2], 'DE' => ['Germany', 51.2, 10.4],
        'NL' => ['Netherlands', 52.2, 5.3], 'BE' => ['Belgium', 50.6, 4.7], 'ES' => ['Spain', 40.2, -3.7], 'PT' => ['Portugal', 39.6, -8.0],
        'IT' => ['Italy', 42.8, 12.5], 'CH' => ['Switzerland', 46.8, 8.2], 'AT' => ['Austria', 47.6, 14.1], 'SE' => ['Sweden', 62.0, 15.0],
        'NO' => ['Norway', 61.0, 9.0], 'DK' => ['Denmark', 56.0, 10.0], 'FI' => ['Finland', 64.0, 26.0], 'PL' => ['Poland', 52.0, 19.4],
        'CZ' => ['Czechia', 49.8, 15.5], 'RO' => ['Romania', 45.9, 25.0], 'GR' => ['Greece', 39.0, 22.0], 'TR' => ['Turkey', 39.0, 35.0],
        'UA' => ['Ukraine', 49.0, 31.4],
        'NG' => ['Nigeria', 9.1, 8.7], 'GH' => ['Ghana', 7.9, -1.0], 'KE' => ['Kenya', 0.2, 37.9], 'ZA' => ['South Africa', -29.0, 24.0],
        'EG' => ['Egypt', 26.8, 30.8], 'MA' => ['Morocco', 31.8, -7.1], 'ET' => ['Ethiopia', 9.1, 40.5], 'TZ' => ['Tanzania', -6.4, 34.9],
        'UG' => ['Uganda', 1.4, 32.3], 'RW' => ['Rwanda', -1.9, 29.9], 'SN' => ['Senegal', 14.5, -14.4],
        'AE' => ['United Arab Emirates', 24.0, 54.0], 'SA' => ['Saudi Arabia', 24.0, 45.0], 'IL' => ['Israel', 31.0, 35.0],
        'IN' => ['India', 21.0, 78.0], 'PK' => ['Pakistan', 30.0, 70.0], 'BD' => ['Bangladesh', 23.7, 90.3], 'LK' => ['Sri Lanka', 7.8, 80.7],
        'SG' => ['Singapore', 1.35, 103.8], 'MY' => ['Malaysia', 4.2, 102.0], 'ID' => ['Indonesia', -2.5, 118.0], 'PH' => ['Philippines', 12.9, 121.8],
        'TH' => ['Thailand', 15.0, 101.0], 'VN' => ['Vietnam', 16.0, 107.8], 'CN' => ['China', 35.0, 103.0], 'HK' => ['Hong Kong', 22.3, 114.2],
        'JP' => ['Japan', 36.0, 138.0], 'KR' => ['South Korea', 36.5, 127.8], 'AU' => ['Australia', -25.0, 134.0], 'NZ' => ['New Zealand', -41.0, 174.0],
        'US' => ['United States', 39.8, -98.6], 'CA' => ['Canada', 56.1, -106.3], 'MX' => ['Mexico', 23.6, -102.5], 'BR' => ['Brazil', -10.0, -52.0],
        'AR' => ['Argentina', -34.0, -64.0], 'CL' => ['Chile', -35.0, -71.0], 'CO' => ['Colombia', 4.6, -74.3], 'PE' => ['Peru', -9.2, -75.0],
    ],

    'aliases' => [
        'uk' => 'GB', 'u.k.' => 'GB', 'great britain' => 'GB', 'england' => 'GB', 'scotland' => 'GB', 'wales' => 'GB', 'northern ireland' => 'GB',
        'usa' => 'US', 'u.s.' => 'US', 'u.s.a.' => 'US', 'united states of america' => 'US', 'america' => 'US',
        'uae' => 'AE', 'holland' => 'NL', 'czech republic' => 'CZ', 'turkiye' => 'TR', 'korea' => 'KR', 'republic of korea' => 'KR',
    ],
];
