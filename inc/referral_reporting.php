<?php
/** CSV adapters and campaign-level monetization reports. */

if (defined('REFERRAL_REPORTING_INCLUDED')) {
    return;
}
define('REFERRAL_REPORTING_INCLUDED', true);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function referralReportNormalizeHeader(string $header): string
{
    $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);
    $header = strtolower(trim((string) $header));
    $header = preg_replace('/[^a-z0-9]+/', '_', $header);
    return trim((string) $header, '_');
}

function referralReportHeaderIndex(array $map, array $aliases): ?int
{
    foreach ($aliases as $alias) {
        $normalized = referralReportNormalizeHeader($alias);
        if (array_key_exists($normalized, $map)) {
            return (int) $map[$normalized];
        }
    }
    return null;
}

function referralReportCell(array $row, array $map, array $aliases): string
{
    $index = referralReportHeaderIndex($map, $aliases);
    return $index !== null ? trim((string) ($row[$index] ?? '')) : '';
}

function referralReportNumber(string $raw): float
{
    $raw = trim($raw);
    $negative = str_starts_with($raw, '(') && str_ends_with($raw, ')');
    $clean = preg_replace('/[^0-9.\-]/', '', $raw);
    $number = is_numeric($clean) ? (float) $clean : 0.0;
    return $negative ? -abs($number) : $number;
}

function referralReportDate(string $raw): string
{
    $raw = trim($raw);
    if ($raw === '') {
        return '';
    }
    try {
        return (new DateTimeImmutable($raw))->format('Y-m-d');
    } catch (Throwable $e) {
        return '';
    }
}

function referralReportCampaign(string $raw): string
{
    $raw = rawurldecode(trim($raw));
    if (preg_match('/[?&]campaign=([^&]+)/i', $raw, $matches)) {
        $raw = rawurldecode((string) $matches[1]);
    }
    return trim($raw);
}

function referralReportRawRow(array $header, array $row): array
{
    $raw = [];
    foreach ($header as $index => $column) {
        $raw[(string) $column] = (string) ($row[$index] ?? '');
    }
    return $raw;
}

/**
 * Import either the existing normalized conversion CSV or a Viator
 * Performance Trends campaign export.
 *
 * @return array{status:string,source_type:string,summary:array,error_log:?string}
 */
function referralImportCampaignCsv(string $providerId, string $filePath): array
{
    $fh = fopen($filePath, 'r');
    if (!$fh) {
        throw new RuntimeException('Unable to open uploaded CSV.');
    }

    $header = fgetcsv($fh, null, ',', '"', '');
    if (!is_array($header)) {
        fclose($fh);
        throw new RuntimeException('CSV header not found.');
    }

    $map = [];
    foreach ($header as $index => $column) {
        $map[referralReportNormalizeHeader((string) $column)] = $index;
    }

    $externalIdAliases = ['external_conversion_id', 'booking_reference', 'booking_ref', 'booking_id'];
    $campaignAliases = ['campaign_slug', 'campaign', 'campaign_value', 'campaign_name'];
    $dateAliases = ['report_date', 'date', 'day', 'booking_date', 'booked_at'];
    $isConversion = referralReportHeaderIndex($map, $externalIdAliases) !== null;
    $isPerformance = referralReportHeaderIndex($map, $campaignAliases) !== null
        && referralReportHeaderIndex($map, $dateAliases) !== null
        && (
            referralReportHeaderIndex($map, ['visitors', 'sessions', 'bookings']) !== null
            || referralReportHeaderIndex($map, ['gross_commission', 'commission', 'commission_value']) !== null
        );

    if (!$isConversion && !$isPerformance) {
        fclose($fh);
        throw new RuntimeException(
            'Unrecognized CSV. Include either external_conversion_id or campaign + date + performance metrics.'
        );
    }

    $processed = 0;
    $upserted = 0;
    $skipped = 0;
    $errors = [];
    $db = getDb();
    $db->exec('BEGIN IMMEDIATE');

    try {
        while (($row = fgetcsv($fh, null, ',', '"', '')) !== false) {
            $processed++;
            if (!is_array($row) || count(array_filter($row, static fn($value): bool => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $campaignValue = referralReportCampaign(referralReportCell($row, $map, $campaignAliases));
            $campaign = $campaignValue !== '' ? queryOne(
                'SELECT id, slug FROM referral_campaigns WHERE provider_id = :provider_id AND slug = :slug LIMIT 1',
                [':provider_id' => $providerId, ':slug' => $campaignValue]
            ) : null;
            $campaignId = trim((string) ($campaign['id'] ?? ''));

            if ($isConversion) {
                $externalId = referralReportCell($row, $map, $externalIdAliases);
                if ($externalId === '') {
                    $skipped++;
                    $errors[] = 'Row ' . $processed . ': missing conversion/booking identifier';
                    continue;
                }
                $currency = strtoupper(referralReportCell($row, $map, ['currency', 'currency_code']) ?: 'USD');
                $bookingValue = referralReportNumber(referralReportCell($row, $map, ['booking_value', 'gross_booking_value', 'gross_booking_amount']));
                $commission = referralReportNumber(referralReportCell($row, $map, ['commission_value', 'gross_commission', 'commission']));
                $bookedAt = referralReportCell($row, $map, ['booked_at', 'booking_date', 'date']);

                execute(
                    'INSERT INTO referral_conversions
                        (id, provider_id, campaign_id, external_conversion_id, click_id, booking_value,
                         commission_value, currency, booked_at, imported_at, raw_json, created_at, updated_at)
                     VALUES
                        (:id, :provider_id, :campaign_id, :external_id, NULL, :booking_value,
                         :commission, :currency, :booked_at, CURRENT_TIMESTAMP, :raw_json, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                     ON CONFLICT(provider_id, external_conversion_id) DO UPDATE SET
                        campaign_id = excluded.campaign_id,
                        booking_value = excluded.booking_value,
                        commission_value = excluded.commission_value,
                        currency = excluded.currency,
                        booked_at = excluded.booked_at,
                        imported_at = CURRENT_TIMESTAMP,
                        raw_json = excluded.raw_json,
                        updated_at = CURRENT_TIMESTAMP',
                    [
                        ':id' => uuid(), ':provider_id' => $providerId,
                        ':campaign_id' => $campaignId !== '' ? $campaignId : null,
                        ':external_id' => $externalId, ':booking_value' => $bookingValue,
                        ':commission' => $commission, ':currency' => $currency,
                        ':booked_at' => $bookedAt !== '' ? $bookedAt : null,
                        ':raw_json' => json_encode(referralReportRawRow($header, $row), JSON_UNESCAPED_SLASHES),
                    ]
                );
                $upserted++;
                continue;
            }

            $reportDate = referralReportDate(referralReportCell($row, $map, $dateAliases));
            if ($campaignValue === '' || $reportDate === '') {
                $skipped++;
                $errors[] = 'Row ' . $processed . ': campaign and valid report date are required';
                continue;
            }
            if ($campaignId === '') {
                $errors[] = 'Row ' . $processed . ': unknown campaign ' . $campaignValue;
            }

            $productCode = strtoupper(referralReportCell($row, $map, ['product_code', 'experience_code', 'experience_product_code']));
            $currency = strtoupper(referralReportCell($row, $map, ['currency', 'currency_code']) ?: 'USD');
            $visitors = max(0, (int) round(referralReportNumber(referralReportCell($row, $map, ['visitors', 'sessions']))));
            $bookings = max(0, (int) round(referralReportNumber(referralReportCell($row, $map, ['bookings', 'booking_count']))));
            $bookingValue = referralReportNumber(referralReportCell($row, $map, ['booking_value', 'gross_booking_value', 'gross_booking_amount']));
            $commission = referralReportNumber(referralReportCell($row, $map, ['commission_value', 'gross_commission', 'commission']));

            execute(
                'INSERT INTO referral_campaign_daily
                    (id, provider_id, campaign_id, report_date, source, campaign_value, product_code,
                     visitors, bookings, booking_value, commission_value, currency, raw_json,
                     imported_at, created_at, updated_at)
                 VALUES
                    (:id, :provider_id, :campaign_id, :report_date, "viator_performance_csv", :campaign_value, :product_code,
                     :visitors, :bookings, :booking_value, :commission, :currency, :raw_json,
                     CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                 ON CONFLICT(provider_id, report_date, campaign_value, product_code, currency) DO UPDATE SET
                    campaign_id = excluded.campaign_id,
                    visitors = excluded.visitors,
                    bookings = excluded.bookings,
                    booking_value = excluded.booking_value,
                    commission_value = excluded.commission_value,
                    raw_json = excluded.raw_json,
                    imported_at = CURRENT_TIMESTAMP,
                    updated_at = CURRENT_TIMESTAMP',
                [
                    ':id' => uuid(), ':provider_id' => $providerId,
                    ':campaign_id' => $campaignId !== '' ? $campaignId : null,
                    ':report_date' => $reportDate, ':campaign_value' => $campaignValue,
                    ':product_code' => $productCode, ':visitors' => $visitors,
                    ':bookings' => $bookings, ':booking_value' => $bookingValue,
                    ':commission' => $commission, ':currency' => $currency,
                    ':raw_json' => json_encode(referralReportRawRow($header, $row), JSON_UNESCAPED_SLASHES),
                ]
            );
            $upserted++;
        }
        $db->exec('COMMIT');
    } catch (Throwable $e) {
        $db->exec('ROLLBACK');
        fclose($fh);
        throw $e;
    }
    fclose($fh);

    $summary = [
        'format' => $isConversion ? 'conversion_rows' : 'viator_campaign_performance',
        'processed' => $processed,
        'upserted' => $upserted,
        'skipped' => $skipped,
        'errors' => count($errors),
    ];

    return [
        'status' => $errors === [] ? 'completed' : 'completed_with_errors',
        'source_type' => $isConversion ? 'csv_conversion' : 'viator_performance_csv',
        'summary' => $summary,
        'error_log' => $errors !== [] ? implode("\n", array_slice($errors, 0, 100)) : null,
    ];
}
