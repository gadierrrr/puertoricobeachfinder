<?php
/**
 * EnrichmentPromptBuilder - Builds classification prompts for bare beach enrichment
 *
 * Uses controlled vocabularies from inc/constants.php to ensure valid tags/amenities.
 */

class EnrichmentPromptBuilder {

    // Municipality-specific context for geographic inference
    private const MUNICIPALITY_CONTEXT = [
        'Aguadilla' => 'West coast, near Rafael Hernandez Airport, known for surfing, Atlantic-facing',
        'Aguada' => 'West coast, transition between Atlantic and Caribbean, moderate surf',
        'Arecibo' => 'North coast, limestone karst region, Atlantic-facing, stronger currents',
        'Barceloneta' => 'North coast, Atlantic-facing, industrial area nearby',
        'Cabo Rojo' => 'Southwest coast, salt flats, lighthouse, Caribbean-facing, calmer waters',
        'Camuy' => 'North coast, river caves nearby, Atlantic-facing',
        'Carolina' => 'North coast, urban, near SJU airport, popular beaches',
        'Catano' => 'North coast, urban, across San Juan Bay',
        'Ceiba' => 'East coast, former Roosevelt Roads naval base, calm Caribbean side',
        'Culebra' => 'Island municipality, ferry/plane access, Caribbean, famous for Flamenco Beach',
        'Dorado' => 'North coast, resort area, Atlantic-facing',
        'Fajardo' => 'East coast, marina town, gateway to offshore cays, bioluminescent bay',
        'Guanica' => 'South coast, dry forest reserve, Caribbean-facing, very calm waters',
        'Guayama' => 'South coast, Caribbean-facing, calm waters',
        'Guayanilla' => 'South coast, Caribbean-facing, industrial port area',
        'Hatillo' => 'North coast, Atlantic-facing, agricultural area',
        'Humacao' => 'East coast, marina, resort area',
        'Isabela' => 'Northwest coast, dramatic coastline, natural pools, Atlantic surf',
        'Jayuya' => 'Interior mountain municipality, no direct coast',
        'Juana Diaz' => 'South coast, Caribbean-facing, calm',
        'Lajas' => 'Southwest coast, La Parguera bioluminescent bay, Caribbean mangroves',
        'Loiza' => 'North coast, Afro-Puerto Rican culture, Atlantic-facing',
        'Luquillo' => 'East coast, famous kiosks, near El Yunque, sheltered from Atlantic',
        'Manati' => 'North coast, Atlantic-facing, limestone cliffs',
        'Maunabo' => 'Southeast coast, lighthouse, transition between Atlantic and Caribbean',
        'Mayaguez' => 'West coast, university town, Caribbean-facing',
        'Naguabo' => 'East coast, fishing village, calm waters',
        'Patillas' => 'Southeast coast, Caribbean-facing, calm',
        'Penuelas' => 'South coast, Caribbean-facing, industrial area',
        'Ponce' => 'South coast, second-largest city, Caribbean, very calm waters',
        'Quebradillas' => 'North coast, dramatic cliffs, Atlantic-facing',
        'Rincon' => 'West coast, surfing capital, sunset views, laid-back atmosphere',
        'Rio Grande' => 'Northeast coast, near El Yunque, resort area',
        'Salinas' => 'South coast, Caribbean-facing, fishing culture',
        'San German' => 'Southwest interior/coast, historic colonial town',
        'San Juan' => 'North coast capital, urban beaches, historic Old San Juan, cruise port',
        'Santa Isabel' => 'South coast, Caribbean-facing, agricultural area',
        'Toa Baja' => 'North coast, Atlantic-facing, urban area',
        'Vega Alta' => 'North coast, Atlantic-facing',
        'Vega Baja' => 'North coast, Atlantic-facing, popular local beaches',
        'Vieques' => 'Island municipality, ferry/plane access, former Navy land, bioluminescent bay',
        'Yabucoa' => 'Southeast coast, transition zone between Atlantic and Caribbean',
        'Yauco' => 'Southwest coast, Caribbean-facing, coffee region nearby',
        'Arroyo' => 'South coast, Caribbean-facing, calm waters, boardwalk area'
    ];

    // Geographic inference rules for tag classification
    private const COAST_TAG_HINTS = [
        'north' => ['surfing', 'swimming'],
        'south' => ['calm-waters', 'swimming', 'snorkeling'],
        'east' => ['snorkeling', 'swimming', 'calm-waters'],
        'west' => ['surfing', 'scenic'],
        'island' => ['snorkeling', 'scenic', 'secluded', 'calm-waters']
    ];

    // Municipalities mapped to coasts
    private const COAST_MAP = [
        'north' => ['Aguadilla', 'Arecibo', 'Barceloneta', 'Camuy', 'Carolina', 'Catano', 'Dorado', 'Hatillo', 'Isabela', 'Loiza', 'Manati', 'Quebradillas', 'San Juan', 'Toa Baja', 'Vega Alta', 'Vega Baja'],
        'south' => ['Arroyo', 'Cabo Rojo', 'Guanica', 'Guayama', 'Guayanilla', 'Juana Diaz', 'Lajas', 'Patillas', 'Penuelas', 'Ponce', 'Salinas', 'Santa Isabel', 'Yauco'],
        'east' => ['Ceiba', 'Fajardo', 'Humacao', 'Luquillo', 'Maunabo', 'Naguabo', 'Rio Grande', 'Yabucoa'],
        'west' => ['Aguada', 'Mayaguez', 'Rincon', 'San German'],
        'island' => ['Culebra', 'Vieques']
    ];

    /**
     * Build the enrichment prompt for a single beach
     */
    public function buildPrompt(array $beach): string {
        $name = $beach['name'];
        $municipality = $beach['municipality'];
        $lat = $beach['lat'];
        $lng = $beach['lng'];
        $description = $beach['description'] ?? '';

        $municipalityContext = self::MUNICIPALITY_CONTEXT[$municipality]
            ?? "{$municipality} municipality in Puerto Rico";

        $coast = $this->getCoast($municipality);
        $coastHints = self::COAST_TAG_HINTS[$coast] ?? [];
        $coastHintStr = !empty($coastHints) ? implode(', ', $coastHints) : 'none';

        $prompt = <<<PROMPT
You are classifying a Puerto Rico beach for a travel guide database. Based on the beach name, municipality, coordinates, geographic context, and description, determine appropriate tags, amenities, features, tips, and field data.

BEACH DATA:
- Name: {$name}
- Municipality: {$municipality}
- Coordinates: {$lat}, {$lng}
- Context: {$municipalityContext}
- Coast: {$coast}
- Geographic tag hints: {$coastHintStr}
- Description: {$description}

CONTROLLED VOCABULARIES:

TAGS (select 3-6 that apply):
- calm-waters: Protected bays, Caribbean-facing, shallow reefs, south/east coast typically
- surfing: Atlantic-facing, north/west coast, known breaks, wave activity
- snorkeling: Coral reefs nearby, clear visibility, calm conditions, marine life
- family-friendly: Shallow entry, calm waters, nearby facilities, safe conditions
- accessible: Easy road access, paved paths, wheelchair-friendly infrastructure
- secluded: Remote location, difficult access, few visitors, off main roads
- popular: Well-known, frequently visited, tourist destination, appears in guides
- scenic: Notable views, dramatic coastline, photo-worthy, natural beauty
- swimming: Safe swimming conditions, reasonable depth, manageable currents
- diving: Deep water access, dive sites, coral formations, wall dives
- fishing: Shore fishing, boat fishing, local fishing culture, piers
- camping: Camping allowed or nearby, overnight stays, tent areas

TAG CLASSIFICATION RULES:
- Use geographic hints as starting suggestions, but override based on description
- North coast = generally Atlantic/surf conditions. South coast = generally Caribbean/calm
- Island municipalities (Culebra, Vieques) tend toward snorkeling, scenic, calm-waters
- "calm-waters" and "surfing" rarely coexist for the same beach
- "secluded" and "popular" should not both be assigned
- "family-friendly" implies calm-waters or swimming usually
- Every beach should get "swimming" unless explicitly dangerous or rocky shore
- Be conservative: only assign tags you are reasonably confident about

AMENITIES (select 2-5 that likely exist):
- restrooms: Public or nearby restroom facilities
- showers: Outdoor rinse showers
- lifeguard: Lifeguard on duty (mainly DRNA-managed balnearios)
- parking: Designated parking area or street parking
- food: Restaurants, kiosks, food vendors nearby
- equipment-rental: Rental shops for water sports or beach gear
- accessibility: ADA-compliant or wheelchair-accessible paths
- picnic-areas: Tables, pavilions, BBQ grills
- shade-structures: Built shade structures, palapas, gazebos
- water-sports: Organized water sports operations (kayak, jet ski, etc.)

AMENITY INFERENCE RULES:
- "Balneario" in name = likely has restrooms, showers, lifeguard, parking, picnic-areas
- Popular urban beaches = likely parking, food nearby
- Secluded/remote beaches = minimal amenities (maybe just parking)
- Only assign amenities you can reasonably infer from context
- "parking" is safe to assume for most beaches (even informal roadside)

RESPOND IN VALID JSON:
{
  "tags": ["tag1", "tag2", "tag3"],
  "amenities": ["amenity1", "amenity2"],
  "features": [
    {"title": "Short distinctive title", "description": "50-200 chars describing what makes this specific to THIS beach"},
    {"title": "Another feature", "description": "Specific, factual description"}
  ],
  "tips": [
    {"category": "Timing", "tip": "Actionable advice 20-150 chars"},
    {"category": "Safety", "tip": "Specific safety consideration"},
    {"category": "Equipment", "tip": "What to bring or rent"}
  ],
  "field_data": {
    "best_time": "50-100 words about best seasons, times of day, weather patterns for this beach",
    "parking_details": "30-60 words about parking situation, cost, availability",
    "safety_info": "50-80 words about water conditions, currents, hazards specific to this location",
    "access_label": "short path|10-min walk|moderate hike|difficult hike"
  }
}

FEATURE REQUIREMENTS:
- 2-4 features, each unique to THIS beach (not generic beach features)
- Title: 5-50 characters, specific (e.g., "Natural Rock Pools" not "Beautiful Scenery")
- Description: 50-200 characters, factual and distinctive

TIP REQUIREMENTS:
- 3-5 tips with valid categories: Timing, Safety, Equipment, Parking, Food, Local Custom, Photography, Budget
- Each tip: 20-150 characters, actionable and specific
- No generic advice like "bring sunscreen" unless there's a specific reason

FIELD DATA REQUIREMENTS:
- best_time: Mention specific months/seasons, crowd patterns, weather
- parking_details: Type of parking, approximate cost if applicable, tips
- safety_info: Specific water conditions, currents, bottom type, hazards
- access_label: One of exactly: "short path", "10-min walk", "moderate hike", "difficult hike"

QUALITY RULES:
- Be specific to THIS beach, not generic advice
- Use facts inferable from location, municipality, and description
- Never use: "crystal clear waters", "hidden gem", "paradise", "pristine", "breathtaking"
- Features must describe something distinctive about this specific beach
- Tips must be actionable (tell the visitor what to DO)

Return ONLY valid JSON. No markdown, no code blocks, no explanation.
PROMPT;

        return $prompt;
    }

    /**
     * Determine which coast a municipality is on
     */
    private function getCoast(string $municipality): string {
        foreach (self::COAST_MAP as $coast => $municipalities) {
            if (in_array($municipality, $municipalities)) {
                return $coast;
            }
        }
        return 'unknown';
    }
}
