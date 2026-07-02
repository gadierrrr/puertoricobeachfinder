<?php
/**
 * Hydrographic island chart — projection + baked SVG geometry for the redesign.
 * Generated from US Census PR coastline (see scratchpad/build_island*.py).
 * viewBox is 0 0 560 360. projectToIslandChart(lat,lng) -> [x,y] in that space.
 */
if (defined('ISLAND_CHART_INCLUDED')) { return; }
define('ISLAND_CHART_INCLUDED', true);

const ISLAND_CHART_ISLAND_D  = 'M70.6 164.2 L81.4 160.9 L93.4 153.4 L94.4 151.6 L94.1 148.2 L91.5 142.3 L92.5 137.6 L97.9 133.1 L107.3 131.4 L122.7 132.5 L135.8 137.3 L138.5 136.7 L146.5 138.5 L154.7 136.9 L161.1 137.6 L173.0 136.8 L182.7 140.7 L187.6 140.9 L194.1 138.2 L205.7 136.1 L214.2 138.3 L219.5 137.1 L238.0 141.7 L242.5 141.3 L244.4 138.1 L250.6 137.1 L265.9 138.0 L270.5 140.4 L275.6 141.0 L280.1 139.2 L286.0 141.8 L294.0 142.3 L297.5 141.5 L302.9 145.1 L304.6 144.9 L307.8 140.6 L328.4 144.9 L331.8 147.3 L335.3 147.1 L338.6 143.6 L352.3 147.0 L356.6 145.7 L362.2 148.5 L370.5 149.9 L375.2 154.6 L377.4 154.6 L380.3 152.0 L381.9 154.1 L384.8 154.1 L384.6 155.6 L390.8 161.3 L393.3 160.0 L393.1 158.0 L395.7 158.7 L396.6 162.2 L399.8 164.0 L405.1 164.9 L409.3 164.2 L411.4 161.5 L416.9 159.6 L417.7 160.9 L416.6 164.2 L413.6 168.5 L415.1 172.0 L415.9 184.4 L419.7 187.9 L425.9 190.1 L420.8 198.2 L418.5 198.7 L417.0 195.1 L412.6 194.7 L414.7 200.0 L413.2 201.1 L407.2 199.5 L401.6 202.2 L400.8 204.0 L401.5 205.8 L395.9 203.3 L392.4 205.4 L390.5 209.1 L385.6 212.5 L383.4 216.7 L379.3 226.8 L379.7 229.0 L378.3 232.4 L376.7 232.7 L375.1 231.3 L372.1 234.1 L371.9 242.0 L363.9 243.8 L360.8 247.8 L358.5 247.4 L352.5 250.6 L341.6 252.4 L337.7 249.9 L333.1 249.8 L326.4 255.3 L322.3 252.7 L319.6 252.7 L304.1 260.9 L296.1 259.2 L289.6 264.6 L285.7 264.3 L283.4 262.9 L284.0 260.1 L281.3 257.5 L275.7 257.1 L274.3 254.3 L268.8 250.0 L260.7 252.3 L255.9 258.8 L239.9 247.4 L233.6 247.4 L214.4 253.8 L213.0 252.0 L209.8 252.1 L204.1 249.1 L195.6 252.7 L187.8 249.2 L186.3 247.4 L178.1 248.1 L176.3 243.9 L172.0 246.2 L171.6 251.3 L167.1 252.9 L164.2 255.3 L160.8 256.3 L157.0 254.9 L155.6 257.5 L151.4 255.8 L145.9 256.3 L142.9 261.4 L139.1 260.1 L136.2 260.4 L133.6 256.9 L131.0 256.0 L129.8 252.5 L127.6 252.3 L125.9 253.9 L121.8 251.7 L113.8 251.1 L110.9 252.4 L108.1 256.0 L103.9 257.2 L89.5 253.1 L87.3 256.1 L88.4 260.5 L86.1 260.1 L82.6 255.7 L81.8 248.9 L83.4 245.7 L90.4 242.8 L90.8 240.5 L82.9 237.5 L85.7 230.6 L85.1 225.2 L88.7 221.3 L89.9 211.9 L89.0 208.2 L94.4 202.2 L94.9 200.3 L93.8 197.4 L90.6 194.3 L90.1 190.5 L85.8 181.1 L77.6 179.0 L70.8 167.3 L70.0 165.3 L70.6 164.2 Z';
const ISLAND_CHART_CONTOUR_D = 'M70.6 164.2 L93.4 153.4 L92.5 137.6 L107.3 131.4 L182.7 140.7 L205.7 136.1 L302.9 145.1 L307.8 140.6 L356.6 145.7 L405.1 164.9 L416.9 159.6 L415.9 184.4 L425.9 190.1 L420.8 198.2 L412.6 194.7 L413.2 201.1 L392.4 205.4 L371.9 242.0 L289.6 264.6 L268.8 250.0 L255.9 258.8 L239.9 247.4 L214.4 253.8 L176.3 243.9 L164.2 255.3 L142.9 261.4 L113.8 251.1 L103.9 257.2 L89.5 253.1 L88.4 260.5 L81.8 248.9 L90.8 240.5 L82.9 237.5 L94.9 200.3 L70.6 164.2 Z';
const ISLAND_CHART_CUL_D     = 'M475.7 168.4 L483.9 172.3 L492.8 169.7 L500.0 174.4 L499.7 176.7 L487.1 183.4 L477.7 179.9 L475.7 177.2 L477.8 173.8 L474.7 169.0 L475.7 168.4 Z';
const ISLAND_CHART_VIE_D     = 'M425.7 219.7 L443.7 215.3 L454.8 210.5 L462.7 209.5 L483.1 214.1 L486.1 212.6 L488.8 215.6 L478.8 217.3 L474.7 220.7 L471.8 220.5 L472.9 218.0 L469.7 217.7 L468.1 218.5 L467.9 221.4 L460.1 221.8 L457.0 224.6 L451.8 226.3 L445.8 224.0 L432.8 227.4 L427.1 225.0 L425.5 222.5 L425.7 219.7 Z';
const ISLAND_CHART_LONMIN = -67.271350;
const ISLAND_CHART_LATMAX = 18.515757;
const ISLAND_CHART_KX     = 0.949895;
const ISLAND_CHART_SCALE  = 220.8437;
const ISLAND_CHART_X0     = 70.0;
const ISLAND_CHART_Y0     = 131.3791;

/** Project WGS84 lat/lng to the chart's 560x360 viewBox. */
function projectToIslandChart($lat, $lng): array {
    $x = ISLAND_CHART_X0 + (((float)$lng) - ISLAND_CHART_LONMIN) * ISLAND_CHART_KX * ISLAND_CHART_SCALE;
    $y = ISLAND_CHART_Y0 + (ISLAND_CHART_LATMAX - ((float)$lat)) * ISLAND_CHART_SCALE;
    return [round($x, 1), round($y, 1)];
}

/** Coast region key for a municipality name (null if unknown). */
function islandRegionForMunicipality(?string $muni): ?string {
    static $map = [
    "san juan" => "metro",
    "bayamon" => "metro",
    "bayam\u00f3n" => "metro",
    "guaynabo" => "metro",
    "catano" => "metro",
    "cata\u00f1o" => "metro",
    "carolina" => "metro",
    "trujillo alto" => "metro",
    "toa baja" => "metro",
    "toa alta" => "metro",
    "dorado" => "metro",
    "loiza" => "metro",
    "lo\u00edza" => "metro",
    "canovanas" => "metro",
    "can\u00f3vanas" => "metro",
    "vega baja" => "north",
    "vega alta" => "north",
    "manati" => "north",
    "manat\u00ed" => "north",
    "barceloneta" => "north",
    "arecibo" => "north",
    "hatillo" => "north",
    "camuy" => "north",
    "quebradillas" => "north",
    "morovis" => "north",
    "ciales" => "north",
    "florida" => "north",
    "isabela" => "west",
    "aguadilla" => "west",
    "aguada" => "west",
    "rincon" => "west",
    "rinc\u00f3n" => "west",
    "moca" => "west",
    "san sebastian" => "west",
    "san sebasti\u00e1n" => "west",
    "anasco" => "west",
    "a\u00f1asco" => "west",
    "mayaguez" => "west",
    "mayag\u00fcez" => "west",
    "las marias" => "west",
    "las mar\u00edas" => "west",
    "maricao" => "west",
    "hormigueros" => "west",
    "cabo rojo" => "west",
    "san german" => "west",
    "san germ\u00e1n" => "west",
    "lajas" => "west",
    "sabana grande" => "west",
    "guanica" => "west",
    "gu\u00e1nica" => "west",
    "yauco" => "south",
    "guayanilla" => "south",
    "penuelas" => "south",
    "pe\u00f1uelas" => "south",
    "ponce" => "south",
    "juana diaz" => "south",
    "juana d\u00edaz" => "south",
    "villalba" => "south",
    "coamo" => "south",
    "santa isabel" => "south",
    "salinas" => "south",
    "guayama" => "south",
    "arroyo" => "south",
    "patillas" => "south",
    "adjuntas" => "south",
    "jayuya" => "south",
    "utuado" => "south",
    "fajardo" => "east",
    "ceiba" => "east",
    "naguabo" => "east",
    "humacao" => "east",
    "yabucoa" => "east",
    "maunabo" => "east",
    "las piedras" => "east",
    "juncos" => "east",
    "gurabo" => "east",
    "caguas" => "east",
    "san lorenzo" => "east",
    "aguas buenas" => "east",
    "cidra" => "east",
    "cayey" => "east",
    "aibonito" => "east",
    "barranquitas" => "east",
    "comerio" => "east",
    "comer\u00edo" => "east",
    "naranjito" => "east",
    "rio grande" => "east",
    "r\u00edo grande" => "east",
    "luquillo" => "east",
    "culebra" => "cays",
    "vieques" => "cays"
    ];
    $key = strtolower(trim((string)$muni));
    return $map[$key] ?? null;
}

/** Render the sidebar locator SVG with a coral pin at the beach's coordinates. */
function renderIslandLocator($lat, $lng): string {
    [$px, $py] = projectToIslandChart($lat, $lng);
    $isl = ISLAND_CHART_ISLAND_D; $con = ISLAND_CHART_CONTOUR_D; $cul = ISLAND_CHART_CUL_D; $vie = ISLAND_CHART_VIE_D;
    return <<<SVG
<svg class="loc-svg" viewBox="0 0 560 360" role="img" aria-label="Location on Puerto Rico">
  <defs><linearGradient id="lg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#F0C452"/><stop offset="1" stop-color="#D0982A"/></linearGradient></defs>
  <path class="loc-contour" style="stroke:#8FC6EE;stroke-width:1.1;opacity:.85" transform="translate(247.5,201.7) scale(1.035) translate(-247.5,-201.7)" d="$con"/>
  <path class="loc-contour" style="stroke:#4093CE;stroke-width:.9;opacity:.55" transform="translate(247.5,201.7) scale(1.08) translate(-247.5,-201.7)" d="$con"/>
  <path class="loc-island" d="$isl"/>
  <path class="loc-cay" d="$cul"/><path class="loc-cay" d="$vie"/>
  <g class="loc-pin"><circle class="ring" cx="$px" cy="$py" r="5"/><circle class="dot" cx="$px" cy="$py" r="4.5"/></g>
</svg>
SVG;
}
