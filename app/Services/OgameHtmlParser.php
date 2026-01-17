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

        return [
            'galaxy' => $galaxy,
            'system' => $system,
            'activity_count' => $activityCount,
            'inactive_count' => $inactiveCount,
            'status_counts' => array_filter($statusCounts, static fn ($value) => $value > 0),
            'visible_slots' => $visibleSlots,
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
        if (preg_match_all('/data-time="(\\d+)"[^>]*class="[^"]*(eventHostile|eventNeutral|eventFriendly)[^"]*"/i', $body, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $events[] = [
                    'time' => $match[1],
                    'type' => $match[2],
                ];
            }
        }

        return [
            'hostile' => $hostile,
            'neutral' => $neutral,
            'friendly' => $friendly,
            'own' => $own,
            'events' => $events,
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
}
