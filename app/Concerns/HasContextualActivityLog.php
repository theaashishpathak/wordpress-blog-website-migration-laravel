<?php

namespace App\Concerns;

use App\Support\IpGeolocator;
use App\Support\UserAgentParser;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Wraps Spatie's LogsActivity and enriches every activity entry with
 * the request context — IP address, browser, country, city — so the
 * admin Activity Logs UI shows where each change originated.
 *
 * Each model that uses this trait should override `activityLogOptions()`
 * to declare which attributes to log, the log name (channel), and the
 * description format. The trait then layers IP/geo/browser onto the
 * properties bag automatically.
 *
 * Example usage in a model:
 *
 *     use App\Concerns\HasContextualActivityLog;
 *
 *     class Listing extends Model
 *     {
 *         use HasContextualActivityLog;
 *
 *         public function activityLogOptions(): LogOptions
 *         {
 *             return LogOptions::defaults()
 *                 ->logOnly(['title', 'status', 'category_id'])
 *                 ->logOnlyDirty()
 *                 ->useLogName('business')
 *                 ->setDescriptionForEvent(fn (string $event): string => "Listing {$event}");
 *         }
 *     }
 */
trait HasContextualActivityLog
{
    use LogsActivity;

    /**
     * Override in the consuming model. Default: log all attributes
     * under the `default` channel with a generic description.
     */
    public function getActivitylogOptions(): LogOptions
    {
        // Models may override `activityLogOptions()` for a cleaner API.
        if (method_exists($this, 'activityLogOptions')) {
            return $this->activityLogOptions();
        }

        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Hook called by Spatie just before the Activity row is persisted.
     * We inject request context (IP, browser, country, city) into the
     * properties bag here so it is queryable from the admin UI.
     */
    public function tapActivity(\Spatie\Activitylog\Models\Activity $activity, string $eventName): void
    {
        $request = request();
        $userAgent = $request?->userAgent();
        $ip = $request?->ip();
        $geo = IpGeolocator::lookup($ip);
        $parsedAgent = UserAgentParser::parse($userAgent);

        $properties = collect($activity->properties);

        $context = collect([
            'ip_address'   => $ip,
            'user_agent'   => $userAgent,
            'browser'      => $parsedAgent['browser'] ?? null,
            'platform'     => $parsedAgent['platform'] ?? null,
            'device_type'  => $parsedAgent['device_type'] ?? null,
            'country'      => $geo['country'] ?? null,
            'country_code' => $geo['country_code'] ?? null,
            'city'         => $geo['city'] ?? null,
        ])->filter(fn ($value): bool => $value !== null && $value !== '');

        $activity->properties = $properties->merge(['context' => $context->all()]);
    }
}
