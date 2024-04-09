<?php

namespace App\Models\Setting;

use App\Enums\DateFormat;
use App\Enums\NumberFormat;
use App\Enums\TimeFormat;
use App\Enums\WeekStart;
use App\Traits\Blamable;
use App\Traits\CompanyOwned;
use Carbon\Carbon;
use Database\Factories\Setting\LocalizationFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use NumberFormatter;
use ResourceBundle;
use Wallo\Transmatic\Facades\Transmatic;

class Localization extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $table = 'localizations';

    protected $fillable = [
        'company_id',
        'language',
        'timezone',
        'date_format',
        'time_format',
        'fiscal_year_end_month',
        'fiscal_year_end_day',
        'week_start',
        'number_format',
        'percent_first',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date_format' => DateFormat::class,
        'time_format' => TimeFormat::class,
        'fiscal_year_end_month' => 'integer',
        'fiscal_year_end_day' => 'integer',
        'week_start' => WeekStart::class,
        'number_format' => NumberFormat::class,
    ];

    public static function getLocale(string $language, string $countryCode): string
    {
        $fullLocale = "{$language}_{$countryCode}";

        if (in_array($fullLocale, ResourceBundle::getLocales(''), true)) {
            return $fullLocale;
        }

        return $language;
    }

    public static function getWeekStart(string $locale): int
    {
        /** @var Carbon $date */
        $date = now()->locale($locale);

        $firstDay = $date->startOfWeek()->dayOfWeekIso;

        return WeekStart::from($firstDay)->value ?? WeekStart::DEFAULT;
    }

    public static function isPercentFirst(string $language, string $countryCode): bool
    {
        $test = 25;
        $fullLocale = "{$language}_{$countryCode}";

        $formatter = new NumberFormatter($fullLocale, NumberFormatter::PERCENT);
        $formattedPercent = $formatter->format($test);

        return strpos($formattedPercent, '%') < strpos($formattedPercent, $test);
    }

    public function fiscalYearStartDate(): string
    {
        return Carbon::parse($this->fiscalYearEndDate())->subYear()->addDay()->toDateString();
    }

    public function fiscalYearEndDate(): string
    {
        $today = now();
        $fiscalYearEndThisYear = Carbon::createFromDate($today->year, $this->fiscal_year_end_month, $this->fiscal_year_end_day);

        if ($today->gt($fiscalYearEndThisYear)) {
            return $fiscalYearEndThisYear->copy()->addYear()->toDateString();
        }

        return $fiscalYearEndThisYear->toDateString();
    }

    public function getDateTimeFormatAttribute(): string
    {
        return $this->date_format . ' ' . $this->time_format;
    }

    public static function getAllLanguages(): array
    {
        return Transmatic::getSupportedLanguages();
    }

    public static function newFactory(): Factory
    {
        return LocalizationFactory::new();
    }
}
