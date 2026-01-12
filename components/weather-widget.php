<?php
/**
 * Weather Widget Component
 *
 * @param array $weather - Weather data from getWeatherForLocation()
 * @param string $size - 'compact', 'medium', 'full'
 */

require_once __DIR__ . '/../inc/weather.php';

$weather = $weather ?? null;
$size = $size ?? 'compact';

if (!$weather || !isset($weather['current'])) {
    // Show placeholder if no weather data
    if ($size !== 'compact') {
        echo '<div class="weather-widget weather-error text-sm text-gray-400">Weather unavailable</div>';
    }
    return;
}

$current = $weather['current'];
$recommendation = getBeachRecommendation($weather);
$uvLevel = getUVLevel($current['uv_index'] ?? 0);
$windDir = getWindDirection($current['wind_direction'] ?? 0);
?>

<?php if ($size === 'compact'): ?>
<!-- Compact: Just temp and icon for beach cards -->
<div class="weather-widget weather-compact flex items-center gap-1.5 text-sm">
    <span class="weather-icon" aria-hidden="true"><?= $current['icon'] ?></span>
    <span class="weather-temp font-medium"><?= round($current['temperature']) ?>°C</span>
</div>

<?php elseif ($size === 'medium'): ?>
<!-- Medium: For beach card expanded view -->
<div class="weather-widget weather-medium bg-gradient-to-r from-blue-50 to-sky-50 rounded-lg p-3">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="text-2xl"><?= $current['icon'] ?></span>
            <div>
                <div class="font-semibold text-lg"><?= round($current['temperature']) ?>°C</div>
                <div class="text-xs text-gray-500"><?= h($current['description']) ?></div>
            </div>
        </div>
        <div class="text-right text-sm">
            <div class="flex items-center gap-1 text-gray-600">
                <span>💨</span>
                <span><?= round($current['wind_speed']) ?> km/h <?= $windDir ?></span>
            </div>
            <div class="flex items-center gap-1 text-gray-600">
                <span>☀️</span>
                <span>UV <?= round($current['uv_index']) ?></span>
            </div>
        </div>
    </div>

    <!-- Beach Score Bar -->
    <div class="mt-2 pt-2 border-t border-blue-100">
        <div class="flex items-center justify-between text-xs mb-1">
            <span class="text-gray-600">Beach Score</span>
            <span class="font-medium text-<?= $recommendation['color'] ?>-600"><?= $current['beach_score'] ?>%</span>
        </div>
        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
            <div class="h-full bg-<?= $recommendation['color'] ?>-500 rounded-full transition-all"
                 style="width: <?= $current['beach_score'] ?>%"></div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Full: For beach detail page -->
<div class="weather-widget weather-full bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <!-- Header with recommendation -->
    <div class="bg-gradient-to-r from-<?= $recommendation['color'] ?>-500 to-<?= $recommendation['color'] ?>-600 text-white p-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2 text-lg font-semibold">
                    <span><?= $recommendation['icon'] ?></span>
                    <span><?= h($recommendation['verdict']) ?></span>
                </div>
                <p class="text-sm opacity-90 mt-1"><?= h($recommendation['message']) ?></p>
            </div>
            <div class="text-right">
                <div class="text-3xl font-bold"><?= round($current['temperature']) ?>°</div>
                <div class="text-sm opacity-90">Feels <?= round($current['feels_like']) ?>°</div>
            </div>
        </div>
    </div>

    <!-- Current conditions grid -->
    <div class="p-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <!-- Weather -->
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <div class="text-2xl mb-1"><?= $current['icon'] ?></div>
                <div class="text-sm font-medium text-gray-900"><?= h($current['description']) ?></div>
                <div class="text-xs text-gray-500">Current</div>
            </div>

            <!-- Wind -->
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <div class="text-2xl mb-1">💨</div>
                <div class="text-sm font-medium text-gray-900"><?= round($current['wind_speed']) ?> km/h</div>
                <div class="text-xs text-gray-500"><?= $windDir ?> wind</div>
            </div>

            <!-- UV Index -->
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <div class="text-2xl mb-1">☀️</div>
                <div class="text-sm font-medium text-<?= $uvLevel['color'] ?>-600"><?= $uvLevel['level'] ?></div>
                <div class="text-xs text-gray-500">UV <?= round($current['uv_index']) ?></div>
            </div>

            <!-- Humidity -->
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <div class="text-2xl mb-1">💧</div>
                <div class="text-sm font-medium text-gray-900"><?= round($current['humidity']) ?>%</div>
                <div class="text-xs text-gray-500">Humidity</div>
            </div>
        </div>

        <!-- UV Safety Message -->
        <?php if ($current['uv_index'] >= 6): ?>
        <div class="mt-4 p-3 bg-<?= $uvLevel['color'] ?>-50 border border-<?= $uvLevel['color'] ?>-200 rounded-lg">
            <div class="flex items-center gap-2 text-sm text-<?= $uvLevel['color'] ?>-800">
                <span>⚠️</span>
                <span><?= h($uvLevel['message']) ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Sun times -->
        <?php if (!empty($weather['sunrise']) && !empty($weather['sunset'])): ?>
        <div class="mt-4 flex items-center justify-center gap-6 text-sm text-gray-600">
            <div class="flex items-center gap-1">
                <span>🌅</span>
                <span>Sunrise <?= formatSunTime($weather['sunrise']) ?></span>
            </div>
            <div class="flex items-center gap-1">
                <span>🌇</span>
                <span>Sunset <?= formatSunTime($weather['sunset']) ?></span>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- 3-day forecast -->
    <?php if (!empty($weather['daily'])): ?>
    <div class="border-t border-gray-100 p-4">
        <h4 class="text-sm font-semibold text-gray-700 mb-3">3-Day Forecast</h4>
        <div class="grid grid-cols-3 gap-3">
            <?php foreach (array_slice($weather['daily'], 0, 3) as $i => $day): ?>
            <div class="text-center p-2 <?= $i === 0 ? 'bg-blue-50 rounded-lg' : '' ?>">
                <div class="text-xs font-medium text-gray-500">
                    <?= $i === 0 ? 'Today' : date('D', strtotime($day['date'])) ?>
                </div>
                <div class="text-xl my-1"><?= getWeatherIcon($day['weather_code']) ?></div>
                <div class="text-sm font-medium">
                    <?= round($day['temp_max']) ?>° / <?= round($day['temp_min']) ?>°
                </div>
                <?php if ($day['precipitation_probability'] > 30): ?>
                <div class="text-xs text-blue-600 mt-1">
                    🌧 <?= $day['precipitation_probability'] ?>%
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
