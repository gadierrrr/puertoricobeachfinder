<?php
/**
 * PromptBuilder - Constructs context-rich prompts for beach content generation
 */

class PromptBuilder {

    // Section configurations with word count targets
    private const SECTION_CONFIGS = [
        'history' => [
            'heading' => 'History & Background',
            'min_words' => 400,
            'max_words' => 600,
            'description' => 'Historical background, cultural significance, naming origin'
        ],
        'best_time' => [
            'heading' => 'Best Time to Visit',
            'min_words' => 200,
            'max_words' => 300,
            'description' => 'Seasonal patterns, weather considerations, crowd levels'
        ],
        'getting_there' => [
            'heading' => 'Getting There',
            'min_words' => 200,
            'max_words' => 300,
            'description' => 'Directions, parking, accessibility, transportation options'
        ],
        'what_to_bring' => [
            'heading' => 'What to Bring',
            'min_words' => 200,
            'max_words' => 300,
            'description' => 'Essential items, activity-specific gear, safety equipment'
        ],
        'nearby' => [
            'heading' => 'Nearby Attractions',
            'min_words' => 200,
            'max_words' => 300,
            'description' => 'Local restaurants, shops, other beaches, points of interest'
        ],
        'local_tips' => [
            'heading' => 'Local Tips',
            'min_words' => 200,
            'max_words' => 300,
            'description' => 'Insider knowledge, cultural etiquette, best practices'
        ]
    ];

    // Generic phrases to avoid (used in prompt instructions)
    private const GENERIC_PHRASES_TO_AVOID = [
        'crystal clear waters',
        'hidden gem',
        'paradise',
        'pristine',
        'untouched',
        'perfect destination',
        'breathtaking',
        'slice of heaven',
        'tropical paradise',
        'nature\'s beauty',
        'escape the crowds',
        'off the beaten path'
    ];

    // Municipality-specific context patterns
    private const MUNICIPALITY_CONTEXT = [
        'Aguadilla' => 'West coast region, near Rafael Hernández Airport, known for surfing culture and former Ramey Air Force Base',
        'Rincon' => 'Surfing capital, west coast sunset views, laid-back beach town atmosphere',
        'Culebra' => 'Small island municipality, accessible by ferry or small plane, pristine beaches',
        'Vieques' => 'Island municipality, former Navy land, bioluminescent bay, Spanish Virgin Islands',
        'San Juan' => 'Capital city beaches, urban setting, historic Old San Juan nearby, cruise port',
        'Luquillo' => 'East coast, famous for kiosks, near El Yunque rainforest',
        'Fajardo' => 'East coast, marina town, gateway to offshore cays, bioluminescent bay',
        'Cabo Rojo' => 'Southwest region, salt flats, lighthouse, known for spectacular sunsets',
        'Isabela' => 'Northwest coast, dramatic coastline, natural pools, surfing and diving',
        'Arecibo' => 'North coast, limestone karst region, near famous radio telescope',
        'Ponce' => 'South coast, second-largest city, calmer Caribbean waters',
        'Guanica' => 'South coast, dry forest reserve, calm Caribbean waters',
        'Mayaguez' => 'West coast, university town, third-largest city'
    ];

    /**
     * Build complete prompt for generating all 6 sections for a beach
     */
    public function buildPrompt(array $beachData, array $tags, array $amenities): string {
        $beach = $beachData;
        $name = $beach['name'];
        $municipality = $beach['municipality'];

        $municipalityContext = self::MUNICIPALITY_CONTEXT[$municipality] ??
            "{$municipality} municipality in Puerto Rico";

        $tagsList = !empty($tags) ? implode(', ', $tags) : 'none';
        $amenitiesList = !empty($amenities) ? implode(', ', $amenities) : 'none';

        // Build tag-specific emphasis
        $tagEmphasis = $this->buildTagEmphasis($tags);

        // Build structural variation guidance
        $variationGuidance = $this->buildVariationGuidance($beach);

        $prompt = <<<PROMPT
You are writing extended content for a Puerto Rico beach guide website. Generate unique, factual, and engaging content for {$name} in {$municipality}.

BEACH DATA:
- Name: {$name}
- Municipality: {$municipality}
- Context: {$municipalityContext}
- Tags: {$tagsList}
- Amenities: {$amenitiesList}

CRITICAL REQUIREMENTS:
1. Write in a natural, travel guide style (NOT marketing copy)
2. Be SPECIFIC to this exact beach - avoid generic beach descriptions
3. Use concrete details and factual information about Puerto Rico
4. Vary sentence structure and paragraph length naturally
5. NEVER use these phrases: crystal clear waters, hidden gem, paradise, pristine, untouched, breathtaking, slice of heaven
6. Each section must stand alone - don't reference other sections
7. Write for travelers who want practical, authentic information

{$tagEmphasis}

{$variationGuidance}

SECTION REQUIREMENTS:

Generate exactly 6 sections in valid JSON format:

{$this->buildSectionInstructions()}

OUTPUT FORMAT:
Return ONLY valid JSON (no markdown, no code blocks) in this exact structure:
{
  "sections": [
    {
      "section_type": "history",
      "heading": "History & Background",
      "content": "Full paragraph content here...",
      "word_count": 450
    },
    {
      "section_type": "best_time",
      "heading": "Best Time to Visit",
      "content": "Full paragraph content here...",
      "word_count": 250
    },
    {
      "section_type": "getting_there",
      "heading": "Getting There",
      "content": "Full paragraph content here...",
      "word_count": 250
    },
    {
      "section_type": "what_to_bring",
      "heading": "What to Bring",
      "content": "Full paragraph content here...",
      "word_count": 250
    },
    {
      "section_type": "nearby",
      "heading": "Nearby Attractions",
      "content": "Full paragraph content here...",
      "word_count": 250
    },
    {
      "section_type": "local_tips",
      "heading": "Local Tips",
      "content": "Full paragraph content here...",
      "word_count": 250
    }
  ]
}

Generate realistic, specific content that sounds like it was written by a local travel writer who knows this beach well.
PROMPT;

        return $prompt;
    }

    /**
     * Build tag-specific emphasis for content
     */
    private function buildTagEmphasis(array $tags): string {
        if (empty($tags)) {
            return '';
        }

        $emphasis = "TAG-SPECIFIC EMPHASIS:\n";

        if (in_array('surfing', $tags)) {
            $emphasis .= "- Mention surf conditions, wave patterns, best surf seasons\n";
        }
        if (in_array('snorkeling', $tags) || in_array('diving', $tags)) {
            $emphasis .= "- Describe marine life, underwater features, visibility conditions\n";
        }
        if (in_array('family-friendly', $tags)) {
            $emphasis .= "- Highlight shallow areas, safety features, kid-friendly amenities\n";
        }
        if (in_array('secluded', $tags)) {
            $emphasis .= "- Emphasize remote location, peace and quiet, fewer crowds\n";
        }
        if (in_array('scenic', $tags)) {
            $emphasis .= "- Describe landscape features, photo opportunities, natural beauty\n";
        }
        if (in_array('fishing', $tags)) {
            $emphasis .= "- Mention fishing opportunities, local species, fishing regulations\n";
        }
        if (in_array('camping', $tags)) {
            $emphasis .= "- Include camping facilities, overnight considerations\n";
        }

        return $emphasis;
    }

    /**
     * Build variation guidance to prevent repetitive structures
     */
    private function buildVariationGuidance(array $beach): string {
        $municipality = $beach['municipality'];
        $isIsland = in_array($municipality, ['Vieques', 'Culebra']);
        $isUrban = in_array($municipality, ['San Juan', 'Carolina', 'Mayaguez', 'Ponce']);

        $guidance = "VARIATION GUIDANCE:\n";

        if ($isIsland) {
            $guidance .= "- This is an island beach - include ferry/flight logistics where relevant\n";
            $guidance .= "- Mention island-specific considerations (supplies, limited services)\n";
        }

        if ($isUrban) {
            $guidance .= "- This is an urban beach - include public transport, nearby city amenities\n";
            $guidance .= "- Address noise/crowds if applicable\n";
        }

        $guidance .= "- Vary opening sentences - don't always start with the beach name\n";
        $guidance .= "- Mix paragraph lengths (3-6 sentences, not always the same)\n";
        $guidance .= "- Use active voice and concrete nouns\n";

        return $guidance;
    }

    /**
     * Build detailed section instructions
     */
    private function buildSectionInstructions(): string {
        $instructions = "";

        foreach (self::SECTION_CONFIGS as $type => $config) {
            $instructions .= "\n{$config['heading']} ({$config['min_words']}-{$config['max_words']} words):\n";
            $instructions .= "  Focus: {$config['description']}\n";

            // Section-specific guidance
            switch ($type) {
                case 'history':
                    $instructions .= "  - Research historical context if beach name has Spanish/Taíno origins\n";
                    $instructions .= "  - Include municipal history, geological formation, or cultural significance\n";
                    $instructions .= "  - Make educated inferences based on Puerto Rican history and geography\n";
                    break;
                case 'best_time':
                    $instructions .= "  - PR high season: Dec-Apr (dry, less humid). Hurricane season: Jun-Nov\n";
                    $instructions .= "  - Weekends are busier; weekdays quieter for popular beaches\n";
                    $instructions .= "  - Consider tag-specific seasons (winter surf, summer calm waters)\n";
                    break;
                case 'getting_there':
                    $instructions .= "  - Include realistic directions from major highways (PR-2, PR-52, PR-3, etc.)\n";
                    $instructions .= "  - Mention parking situation, walking distance, road conditions\n";
                    $instructions .= "  - For remote beaches, note 4WD needs or hiking requirements\n";
                    break;
                case 'what_to_bring':
                    $instructions .= "  - Tailor to beach tags (surf gear, snorkel equipment, etc.)\n";
                    $instructions .= "  - Mention sun protection, water, snacks if no nearby food\n";
                    $instructions .= "  - Include Puerto Rico specifics (reef-safe sunscreen if snorkeling)\n";
                    break;
                case 'nearby':
                    $instructions .= "  - Mention realistic nearby attractions in the municipality\n";
                    $instructions .= "  - Include local food options (kiosks, restaurants)\n";
                    $instructions .= "  - Other beaches within 10-15 minutes if applicable\n";
                    break;
                case 'local_tips':
                    $instructions .= "  - Include practical advice (arrive early for parking, bring cash, etc.)\n";
                    $instructions .= "  - Mention local customs or etiquette\n";
                    $instructions .= "  - Safety tips specific to beach conditions\n";
                    break;
            }
        }

        return $instructions;
    }

    /**
     * Get section configurations
     */
    public static function getSectionConfigs(): array {
        return self::SECTION_CONFIGS;
    }
}
