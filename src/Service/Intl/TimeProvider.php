<?php
declare(strict_types=1);

namespace App\Service\Intl;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Symfony\Contracts\Translation\TranslatorInterface;

class TimeProvider
{
    public const DATE = 0;
    public const DATETIME = 1;
    public const SHORT_DATE = 2;
    public const SHORT_DATETIME = 3;
    public const MONTH_YEAR = 4;

    public function __construct(
        private readonly TranslatorInterface $translator,
    )
    {
    }

    public function formatIntervalAsShortDate(DateTimeInterface $datetime1, DateTimeInterface $datetime2, string $timezone = null, string $locale = null): string
    {
        $year1 = $datetime1->format('Y');
        $month1 = $datetime1->format('m');
        $day1 = $datetime1->format('d');
        $year2 = $datetime2->format('Y');
        $month2 = $datetime2->format('m');
        $day2 = $datetime2->format('d');

        if ($year1 === $year2 && $month1 === $month2 && $day1 === $day2) {
            return $this->formatAsShortDate($datetime1, $timezone, $locale);
        }

        if ($year1 === $year2 && $month1 === $month2) {
            $parameters = [
                'day' => $day1 . ' - ' . $day2,
                'short_month' => $this->getShortMonth($month1, locale: $locale),
                'year' => $year1,
            ];

            return $this->trans('short_date', $parameters, $locale);
        }

        if ($year1 === $year2) {
            $parameters = [
                'day1' => $day1,
                'short_month1' => $this->getShortMonth($month1, locale: $locale),
                'day2' => $day2,
                'short_month2' => $this->getShortMonth($month2, locale: $locale),
                'year' => $year1,
            ];

            return $this->trans('short_date_interval_same_year', $parameters, $locale);
        }

        $date1 = $this->formatAsShortDate($datetime1, $timezone, $locale);
        $date2 = $this->formatAsShortDate($datetime2, $timezone, $locale);

        return $date1 . ' - ' . $date2;
    }

    public function format(int $format, DateTimeInterface $datetime, string $timezone = null, string $locale = null): ?string
    {
        return match ($format) {
            self::DATE => $this->formatAsDate($datetime, $timezone, $locale),
            self::DATETIME => $this->formatAsDatetime($datetime, $timezone, $locale),
            self::SHORT_DATE => $this->formatAsShortDate($datetime, $timezone, $locale),
            self::SHORT_DATETIME => $this->formatAsShortDatetime($datetime, $timezone, $locale),
            self::MONTH_YEAR => $this->formatTimeAsMonthYear($datetime, $timezone, $locale),
            default => null,
        };
    }

    public function formatAsDate(DateTimeInterface $datetime, string $timezone = null, string $locale = null): string
    {
        $datetime = $this->applyTimezone($datetime, $timezone);
        $locale = $locale ?? $this->translator->getLocale();
        $parameters = [
            'day' => $datetime->format('d'),
            'month' => $this->getMonth($datetime->format('m'), locale: $locale),
            'year' => $datetime->format('Y'),
        ];

        return $this->trans('date', $parameters, $locale);
    }

    private function applyTimezone(DateTimeInterface $datetime, string $timezone = null): DateTimeInterface
    {
        if ($timezone === null) {
            return $datetime;
        }

        return $datetime->setTimezone(new DateTimeZone($timezone));
    }

    private function getMonth(int|string $num, string $locale = null): string
    {
        return $this->trans(sprintf('month.%d', $num), locale: $locale);
    }

    private function trans(string $id, array $parameters = [], string $locale = null): string
    {
        return $this->translator->trans($id, $parameters, 'time', $locale);
    }

    public function formatAsDatetime(DateTimeInterface $datetime, string $timezone = null, string $locale = null): string
    {
        $datetime = $this->applyTimezone($datetime, $timezone);
        $locale = $locale ?? $this->translator->getLocale();
        $parameters = [
            'date' => $this->formatAsDate($datetime, locale: $locale),
            'hour' => $datetime->format('H'),
            'minute' => $datetime->format('i'),
        ];

        return $this->trans('datetime', $parameters, $locale);
    }

    public function formatAsShortDate(DateTimeInterface $datetime, string $timezone = null, string $locale = null): string
    {
        $datetime = $this->applyTimezone($datetime, $timezone);
        $locale = $locale ?? $this->translator->getLocale();
        $parameters = [
            'day' => $datetime->format('d'),
            'short_month' => $this->getShortMonth($datetime->format('m'), locale: $locale),
            'year' => $datetime->format('Y'),
        ];

        return $this->trans('short_date', $parameters, $locale);
    }

    private function getShortMonth(int|string $num, string $locale = null): string
    {
        return $this->trans(sprintf('short_month.%d', $num), locale: $locale);
    }

    public function formatAsShortDatetime(DateTimeInterface $datetime, string $timezone = null, string $locale = null): string
    {
        $datetime = $this->applyTimezone($datetime, $timezone);
        $locale = $locale ?? $this->translator->getLocale();
        $parameters = [
            'short_date' => $this->formatAsShortDate($datetime, locale: $locale),
            'hour' => $datetime->format('H'),
            'minute' => $datetime->format('i'),
        ];

        return $this->trans('short_datetime', $parameters, $locale);
    }

    public function formatTimeAsMonthYear(DateTimeInterface $datetime, string $timezone = null, string $locale = null): string
    {
        $datetime = $this->applyTimezone($datetime, $timezone);
        $locale = $locale ?? $this->translator->getLocale();

        $month = $this->getMonth($datetime->format('m'), locale: $locale);
        $year = $datetime->format('Y');

        return $month . ' ' . $year;
    }

    /**
     * Format should be like: Січень 2022
     *
     * @param string $date
     * @param string $locale
     * @return DateTimeInterface|null
     */
    public function createFromMonthYear(string $date, string $locale): ?DateTimeInterface
    {
        [$monthName, $year] = explode(' ', $date);

        if (empty($monthName) || empty($year) || !is_numeric($year)) {
            return null;
        }

        for ($month = 1; $month < 13; $month++) {
            if ($this->getMonth($month, $locale) === $monthName) {
                return DateTimeImmutable::createFromFormat('m Y', $month . ' ' . $year);
            }
        }

        return null;
    }
}