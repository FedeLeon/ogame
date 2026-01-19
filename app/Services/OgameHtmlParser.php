<?php

namespace App\Services;

use Illuminate\Support\Str;

class OgameHtmlParser
{
    public static function parseOverviewHtml(string $body): array
    {
        $meta = [];
        if (preg_match_all('/<meta\\s+name="(ogame-[^"]+)"\\s+content="([^"]*)"\\s*\\/>/i', $body, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $meta[$match[1]] = $match[2];
            }
        }

        $getMeta = function (string $key) use ($meta) {
            return $meta['ogame-' . $key] ?? null;
        };

        $playerId = self::extractFirstMatch($body, [
            '/playerId\\s*[:=]\\s*["\\\']?(\\d+)/i',
            '/playerId["\\\']\\s*:\\s*["\\\']?(\\d+)/i',
        ]);

        $playerName = self::extractFirstMatch($body, [
            '/playerName\\s*[:=]\\s*["\\\']([^"\\\']+)["\\\']/i',
            '/playername["\\\']\\s*content=["\\\']([^"\\\']+)["\\\']/i',
        ]);

        $token = self::extractFirstMatch($body, [
            '/token\\s*[:=]\\s*["\\\']([a-f0-9]{16,64})["\\\']/i',
            '/ajaxToken\\s*[:=]\\s*["\\\']([^"\\\']+)["\\\']/i',
        ]);

        $resources = array_filter([
            'metal' => self::extractResourceValue($body, 'resources_metal'),
            'crystal' => self::extractResourceValue($body, 'resources_crystal'),
            'deuterium' => self::extractResourceValue($body, 'resources_deuterium'),
            'energy' => self::extractResourceValue($body, 'resources_energy'),
            'food' => self::extractResourceValue($body, 'resources_food'),
            'darkmatter' => self::extractResourceValue($body, 'resources_darkmatter'),
        ], static fn ($value) => $value !== null);

        $fleets = array_filter([
            'fleets' => self::extractFirstMatch($body, ['/fleets\\s*[:=]\\s*(\\d+)/i']),
            'fleet_slots' => self::extractFirstMatch($body, ['/fleetSlots\\s*[:=]\\s*(\\d+)/i']),
            'expeditions' => self::extractFirstMatch($body, ['/expeditions\\s*[:=]\\s*(\\d+)/i']),
            'expedition_slots' => self::extractFirstMatch($body, ['/expeditionSlots\\s*[:=]\\s*(\\d+)/i']),
        ], static fn ($value) => $value !== null);

        $planets = self::extractPlanets($body);

        $isLobby = empty($meta)
            && (Str::contains($body, 'browsergamelobby')
                || Str::contains($body, 'gameforge.com/js/connect.js')
                || Str::contains($body, 'cookiebanner.js'));

        return [
            'meta' => $meta,
            'player_id' => $playerId,
            'player_name' => $playerName,
            'token' => $token,
            'resources' => $resources,
            'fleets' => $fleets,
            'planets' => $planets,
            'is_lobby' => $isLobby,
            'meta_universe' => $getMeta('universe') ?? $getMeta('universe-name'),
            'meta_session' => $getMeta('session'),
            'meta_version' => $getMeta('version'),
            'meta_timestamp' => $getMeta('timestamp'),
            'meta_speed' => $getMeta('universe-speed'),
            'meta_fleet_peaceful' => $getMeta('universe-speed-fleet-peaceful'),
            'meta_fleet_war' => $getMeta('universe-speed-fleet-war'),
            'meta_fleet_holding' => $getMeta('universe-speed-fleet-holding'),
            'meta_player_id' => $meta['ogame-player-id'] ?? null,
            'meta_player_name' => $meta['ogame-player-name'] ?? null,
            'meta_planet_id' => $meta['ogame-planet-id'] ?? null,
            'meta_planet_name' => $meta['ogame-planet-name'] ?? null,
            'meta_planet_coords' => $meta['ogame-planet-coordinates'] ?? null,
            'meta_planet_type' => $meta['ogame-planet-type'] ?? null,
            'meta_alliance_id' => $meta['ogame-alliance-id'] ?? null,
            'meta_alliance_name' => $meta['ogame-alliance-name'] ?? null,
            'meta_alliance_tag' => $meta['ogame-alliance-tag'] ?? null,
            'endpoints' => self::extractEndpoints($body),
        ];
    }

    public static function parseGalaxyHtml(string $body): array
    {
        $galaxy = self::extractFirstMatch($body, [
            '/data-galaxy="(\\d+)"/i',
            '/galaxy\\s*[:=]\\s*(\\d+)/i',
        ]);
        $system = self::extractFirstMatch($body, [
            '/data-system="(\\d+)"/i',
            '/system\\s*[:=]\\s*(\\d+)/i',
        ]);

        $activityCount = self::countMatches($body, [
            '/class="[^"]*minute15[^"]*"/i',
            '/class="[^"]*minute[^"]*"/i',
        ]);
        $inactiveCount = self::countMatches($body, [
            '/status_abbr_inactive/i',
            '/status_abbr_longinactive/i',
        ]);
        $statusCounts = [
            'vacaciones' => self::countMatches($body, ['/status_abbr_vacation/i']),
            'baneado' => self::countMatches($body, ['/status_abbr_banned/i']),
            'admin' => self::countMatches($body, ['/status_abbr_admin/i']),
            'noob' => self::countMatches($body, ['/status_abbr_noob/i']),
            'fuerte' => self::countMatches($body, ['/status_abbr_strong/i']),
            'outlaw' => self::countMatches($body, ['/status_abbr_outlaw/i']),
        ];
        $visibleSlots = self::countMatches($body, [
            '/class="galaxy_row"/i',
            '/data-row="\\d+"/i',
        ]);
        $rows = self::extractGalaxyRows($body);

        return [
            'galaxy' => $galaxy,
            'system' => $system,
            'activity_count' => $activityCount,
            'inactive_count' => $inactiveCount,
            'status_counts' => array_filter($statusCounts, static fn ($value) => $value > 0),
            'visible_slots' => $visibleSlots,
            'rows' => $rows,
        ];
    }

    public static function parseFleetdispatchHtml(string $body): array
    {
        $fleets = array_filter([
            'fleets' => self::extractFirstMatch($body, ['/fleets\\s*[:=]\\s*(\\d+)/i']),
            'fleet_slots' => self::extractFirstMatch($body, ['/fleetSlots\\s*[:=]\\s*(\\d+)/i']),
            'expeditions' => self::extractFirstMatch($body, ['/expeditions\\s*[:=]\\s*(\\d+)/i']),
            'expedition_slots' => self::extractFirstMatch($body, ['/expeditionSlots\\s*[:=]\\s*(\\d+)/i']),
        ], static fn ($value) => $value !== null);

        $activeFlights = self::countMatches($body, [
            '/eventFleet/i',
            '/flight/i',
        ]);

        return [
            'slots' => $fleets,
            'active_flights' => $activeFlights,
        ];
    }

    public static function parseEventboxHtml(string $body): array
    {
        $hostile = self::countMatches($body, ['/eventHostile/i']);
        $neutral = self::countMatches($body, ['/eventNeutral/i']);
        $friendly = self::countMatches($body, ['/eventFriendly/i']);
        $own = self::countMatches($body, ['/eventBoxEnd/i', '/eventFriendly/i']);

        $events = [];
        $fleetRows = [];
        if (preg_match_all('/<tr[^>]*class="[^"]*eventFleet[^"]*"[^>]*>.*?<\\/tr>/is', $body, $rowMatches)) {
            $fleetRows = $rowMatches[0];
        }
        foreach ($fleetRows as $row) {
            $rowTag = '';
            if (preg_match('/<tr[^>]*class="[^"]*eventFleet[^"]*"[^>]*>/i', $row, $tagMatch)) {
                $rowTag = $tagMatch[0];
            }
            $attrs = self::extractHtmlAttributes($rowTag);
            $missionTitle = self::extractFirstMatch($row, [
                '/<img[^>]*class="[^"]*tooltipHTML[^"]*"[^>]*title="([^"]+)"/i',
            ]);
            $arrivalText = self::extractFirstMatch($row, [
                '/<td[^>]*class="[^"]*arrivalTime[^"]*"[^>]*>([^<]+)<\\/td>/i',
            ]);
            $originName = self::extractFirstMatch($row, [
                '/<td[^>]*class="[^"]*originFleet[^"]*"[^>]*>\\s*(?:<[^>]+>\\s*)*([^<]+)</i',
            ]);
            $originCoords = self::extractFirstMatch($row, [
                '/<td[^>]*class="[^"]*coordsOrigin[^"]*"[^>]*>\\s*<a[^>]*>\\s*\\[([^\\]]+)\\]\\s*<\\/a>/i',
            ]);
            $destName = self::extractFirstMatch($row, [
                '/<td[^>]*class="[^"]*destFleet[^"]*"[^>]*>\\s*(?:<[^>]+>\\s*)*([^<]+)</i',
            ]);
            $destCoords = self::extractFirstMatch($row, [
                '/<td[^>]*class="[^"]*destCoords[^"]*"[^>]*>\\s*<a[^>]*>\\s*\\[([^\\]]+)\\]\\s*<\\/a>/i',
            ]);
            $shipsTotal = self::extractFirstMatch($row, [
                '/<td[^>]*class="[^"]*detailsFleet[^"]*"[^>]*>\\s*<span>([^<]+)<\\/span>/i',
            ]);

            $mission = null;
            if ($missionTitle) {
                $parts = array_map('trim', explode('|', $missionTitle));
                $mission = end($parts) ?: null;
            }

            $events[] = [
                'type' => 'eventFleet',
                'mission_type' => $attrs['data-mission-type'] ?? null,
                'mission' => $mission,
                'mission_title' => $missionTitle,
                'return' => $attrs['data-return-flight'] ?? null,
                'arrival_time' => $attrs['data-arrival-time'] ?? null,
                'arrival_time_text' => $arrivalText ? trim($arrivalText) : null,
                'origin_name' => $originName ? trim($originName) : null,
                'origin_coords' => $originCoords,
                'dest_name' => $destName ? trim($destName) : null,
                'dest_coords' => $destCoords,
                'ships_total' => $shipsTotal ? self::normalizeNumber($shipsTotal) : null,
            ];
        }

        if (! $fleetRows) {
            if (preg_match_all('/<[^>]*class="[^"]*(eventHostile|eventNeutral|eventFriendly|eventFleet)[^"]*"[^>]*>/i', $body, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $attrs = self::extractHtmlAttributes($match[0]);
                    $events[] = [
                        'type' => $match[1],
                        'time' => $attrs['data-time'] ?? $attrs['data-arrival-time'] ?? null,
                        'arrival_time' => $attrs['data-arrival-time'] ?? null,
                        'arrival_time_seconds' => $attrs['data-arrival-time-seconds'] ?? null,
                        'mission_type' => $attrs['data-mission-type'] ?? null,
                        'mission' => $attrs['data-mission'] ?? null,
                        'return' => $attrs['data-return-flight'] ?? null,
                    ];
                }
            } elseif (preg_match_all('/data-time="(\\d+)"[^>]*class="[^"]*(eventHostile|eventNeutral|eventFriendly)[^"]*"/i', $body, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $events[] = [
                        'time' => $match[1],
                        'type' => $match[2],
                    ];
                }
            }
        }

        if ($fleetRows) {
            $friendly = max($friendly, count($fleetRows));
            $own = max($own, count($fleetRows));
        }

        return [
            'hostile' => $hostile,
            'neutral' => $neutral,
            'friendly' => $friendly,
            'own' => $own,
            'events' => $events,
        ];
    }

    public static function parseEventboxPayload(array $payload): array
    {
        if (isset($payload['friendly']) || isset($payload['hostile']) || isset($payload['neutral'])) {
            $events = [];
            if (! empty($payload['eventType']) || ! empty($payload['eventText']) || isset($payload['eventTime'])) {
                $events[] = [
                    'type' => $payload['eventType'] ?? null,
                    'time' => $payload['eventTime'] ?? null,
                    'mission' => $payload['eventText'] ?? null,
                ];
            }

            return [
                'source' => 'ajax',
                'hostile' => $payload['hostile'] ?? 0,
                'neutral' => $payload['neutral'] ?? 0,
                'friendly' => $payload['friendly'] ?? 0,
                'own' => $payload['friendly'] ?? 0,
                'events' => $events,
            ];
        }

        $candidates = [];
        $knownKeys = [
            'eventbox',
            'eventBox',
            'eventList',
            'eventListHtml',
            'eventboxHtml',
            'eventBoxHtml',
            'html',
            'content',
        ];

        foreach ($knownKeys as $key) {
            if (isset($payload[$key]) && is_string($payload[$key])) {
                $candidates[$key] = $payload[$key];
            }
        }

        if (! $candidates) {
            foreach ($payload as $key => $value) {
                if (is_string($value) && Str::contains($value, ['eventHostile', 'eventNeutral', 'eventFriendly', 'eventFleet'])) {
                    $candidates[$key] = $value;
                }
            }
        }

        foreach ($candidates as $key => $html) {
            $parsed = self::parseEventboxHtml($html);
            if (! empty($parsed['events']) || $parsed['hostile'] || $parsed['neutral'] || $parsed['friendly']) {
                $parsed['source'] = 'ajax';
                $parsed['payload_key'] = $key;
                return $parsed;
            }
        }

        return [
            'source' => 'ajax',
            'payload' => $payload,
        ];
    }

    private static function extractFirstMatch(string $body, array $patterns): ?string
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $body, $matches)) {
                return $matches[1] ?? null;
            }
        }
        return null;
    }

    private static function extractResourceValue(string $body, string $id): ?int
    {
        if (preg_match('/id="' . preg_quote($id, '/') . '"[^>]*>([^<]+)</i', $body, $matches)) {
            return self::normalizeNumber($matches[1]);
        }
        if (preg_match('/' . preg_quote($id, '/') . '"\\s*:\\s*["\\\']?([0-9\\.\\,]+)/i', $body, $matches)) {
            return self::normalizeNumber($matches[1]);
        }
        return null;
    }

    private static function normalizeNumber(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }
        $digits = preg_replace('/[^0-9]/', '', $value);
        return $digits !== '' ? (int) $digits : null;
    }

    private static function extractPlanets(string $body): array
    {
        $planets = [];
        if (preg_match_all('/data-planet-id="(\\d+)"[^>]*data-planet-name="([^"]+)"[^>]*data-coordinates="\\[([^\\]]+)\\]"/i', $body, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $planets[] = [
                    'id' => $match[1],
                    'name' => $match[2],
                    'coords' => $match[3],
                ];
            }
        }
        return $planets;
    }

    private static function countMatches(string $body, array $patterns): int
    {
        $count = 0;
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $body, $matches)) {
                $count += count($matches[0] ?? []);
            }
        }
        return $count;
    }

    private static function extractEndpoints(string $body): array
    {
        $endpoints = [];
        $mapping = [
            'ajaxEventboxURI' => 'ajaxEventboxURI',
            'ajaxRecallFleetURI' => 'ajaxRecallFleetURI',
            'ajaxResourceboxURI' => 'ajaxResourceboxURI',
            'eventlistLink' => 'eventlistLink',
            'miniFleetLink' => 'miniFleetLink',
        ];

        foreach ($mapping as $name => $key) {
            $value = self::extractFirstMatch($body, [
                '/var\\s+' . preg_quote($name, '/') . '\\s*=\\s*[\\\'"]([^\\\'"]+)[\\\'"]/i',
            ]);
            if ($value) {
                $value = str_replace('\\/', '/', $value);
                $value = str_replace('\\', '', $value);
                $endpoints[$key] = $value;
            }
        }

        $currentSpaceObjectId = self::extractFirstMatch($body, [
            '/currentSpaceObjectId\\s*=\\s*(\\d+)/i',
        ]);
        if ($currentSpaceObjectId) {
            $endpoints['currentSpaceObjectId'] = $currentSpaceObjectId;
        }

        return $endpoints;
    }

    private static function extractHtmlAttributes(string $tag): array
    {
        $attributes = [];
        if (preg_match_all('/([a-zA-Z0-9_-]+)="([^"]*)"/', $tag, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $attributes[strtolower($match[1])] = $match[2];
            }
        }
        return $attributes;
    }

    private static function extractGalaxyRows(string $body): array
    {
        $rows = [];
        if (! preg_match_all('/<tr[^>]*(?:class="[^"]*(?:galaxyRow|galaxy_row)[^"]*"|data-row="\\d+"|id="galaxyRow-?\\d+")[^>]*>.*?<\\/tr>/is', $body, $matches)) {
            return $rows;
        }

        foreach ($matches[0] as $rowHtml) {
            $rowTag = '';
            if (preg_match('/<tr[^>]*>/i', $rowHtml, $tagMatch)) {
                $rowTag = $tagMatch[0];
            }
            $attrs = self::extractHtmlAttributes($rowTag);
            $position = null;
            if (isset($attrs['data-row'])) {
                $position = (int) $attrs['data-row'];
            } elseif (isset($attrs['id']) && preg_match('/galaxyRow-?(\\d+)/i', $attrs['id'], $idMatch)) {
                $position = (int) $idMatch[1];
            }

            $planetName = self::extractFirstMatch($rowHtml, [
                '/class="[^"]*planetname[^"]*"[^>]*>\\s*([^<]+)/i',
                '/class="[^"]*planetName[^"]*"[^>]*>\\s*([^<]+)/i',
                '/data-planet-name="([^"]+)"/i',
            ]);
            $playerName = self::extractFirstMatch($rowHtml, [
                '/class="[^"]*playername[^"]*"[^>]*>\\s*([^<]+)/i',
                '/class="[^"]*playerName[^"]*"[^>]*>\\s*([^<]+)/i',
                '/data-player-name="([^"]+)"/i',
            ]);
            $debrisMetal = self::extractFirstMatch($rowHtml, [
                '/data-debris-metal="([0-9\\.]+)/i',
                '/data-recyclable-metal="([0-9\\.]+)/i',
                '/class="[^"]*debrisMetal[^"]*"[^>]*>\\s*([^<]+)/i',
            ]);
            $debrisCrystal = self::extractFirstMatch($rowHtml, [
                '/data-debris-crystal="([0-9\\.]+)/i',
                '/data-recyclable-crystal="([0-9\\.]+)/i',
                '/class="[^"]*debrisCrystal[^"]*"[^>]*>\\s*([^<]+)/i',
            ]);

            $statusFlags = [];
            $statusPatterns = [
                'inactive' => '/status_abbr_inactive/i',
                'inactive_long' => '/status_abbr_longinactive/i',
                'vacation' => '/status_abbr_vacation/i',
                'banned' => '/status_abbr_banned/i',
                'admin' => '/status_abbr_admin/i',
                'noob' => '/status_abbr_noob/i',
                'strong' => '/status_abbr_strong/i',
                'outlaw' => '/status_abbr_outlaw/i',
            ];
            foreach ($statusPatterns as $flag => $pattern) {
                if (preg_match($pattern, $rowHtml)) {
                    $statusFlags[] = $flag;
                }
            }

            $activity = null;
            if (preg_match('/minute15/i', $rowHtml)) {
                $activity = '15m';
            } elseif (preg_match('/minute/i', $rowHtml)) {
                $activity = 'activo';
            }

            $rows[] = [
                'position' => $position,
                'planet_name' => $planetName ? trim($planetName) : null,
                'player_name' => $playerName ? trim($playerName) : null,
                'debris_metal' => self::normalizeNumber($debrisMetal),
                'debris_crystal' => self::normalizeNumber($debrisCrystal),
                'status' => $statusFlags,
                'activity' => $activity,
            ];
        }

        return $rows;
    }
}
