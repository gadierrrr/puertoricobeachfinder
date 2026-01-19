<?php
/**
 * Beach vocabulary constants - central source of truth for controlled vocabularies
 * Ported from beachVocab.ts
 *
 * NOTE: Display label functions (getTagLabel, getAmenityLabel, getConditionLabel)
 * are defined in inc/helpers.php to avoid duplicates.
 */

// Include guard to prevent duplicate declarations
if (defined('CONSTANTS_PHP_INCLUDED')) {
    return;
}
define('CONSTANTS_PHP_INCLUDED', true);

const TAGS = [
    'calm-waters',
    'surfing',
    'snorkeling',
    'family-friendly',
    'accessible',
    'secluded',
    'popular',
    'scenic',
    'swimming',
    'diving',
    'fishing',
    'camping'
];

const AMENITIES = [
    'restrooms',
    'showers',
    'lifeguard',
    'parking',
    'food',
    'equipment-rental',
    'accessibility',
    'picnic-areas',
    'shade-structures',
    'water-sports'
];

const CONDITION_SCALES = [
    'sargassum' => ['none', 'light', 'moderate', 'heavy'],
    'surf' => ['calm', 'small', 'medium', 'large'],
    'wind' => ['calm', 'light', 'moderate', 'strong']
];

const MUNICIPALITIES = [
    'Adjuntas', 'Aguada', 'Aguadilla', 'Aguas Buenas', 'Aibonito', 'Arecibo',
    'Arroyo', 'Barceloneta', 'Barranquitas', 'Bayamon', 'Cabo Rojo', 'Caguas',
    'Camuy', 'Canovanas', 'Carolina', 'Catano', 'Cayey', 'Ceiba', 'Cidra',
    'Coamo', 'Comerio', 'Corozal', 'Culebra', 'Dorado', 'Fajardo', 'Florida',
    'Guanica', 'Guayama', 'Guayanilla', 'Guaynabo', 'Gurabo', 'Hatillo',
    'Hormigueros', 'Humacao', 'Isabela', 'Jayuya', 'Juana Diaz', 'Juncos',
    'Lajas', 'Lares', 'Las Marias', 'Las Piedras', 'Loiza', 'Luquillo',
    'Manati', 'Maricao', 'Maunabo', 'Mayaguez', 'Moca', 'Morovis', 'Naguabo',
    'Naranjito', 'Orocovis', 'Patillas', 'Penuelas', 'Ponce', 'Quebradillas',
    'Rincon', 'Rio Grande', 'Sabana Grande', 'Salinas', 'San German',
    'San Juan', 'San Lorenzo', 'San Sebastian', 'Santa Isabel', 'Toa Alta',
    'Toa Baja', 'Trujillo Alto', 'Utuado', 'Vega Alta', 'Vega Baja', 'Vieques',
    'Villalba', 'Yabucoa', 'Yauco'
];

// Puerto Rico coordinate boundaries (including Vieques and Culebra)
const PR_BOUNDS = [
    'lat' => ['min' => 17.8, 'max' => 18.6],
    'lng' => ['min' => -67.4, 'max' => -65.2]
];

// Parking difficulty levels
const PARKING_DIFFICULTY = ['easy', 'moderate', 'difficult', 'very-difficult'];

const PARKING_DIFFICULTY_LABELS = [
    'easy' => 'Easy Parking',
    'moderate' => 'Moderate',
    'difficult' => 'Difficult',
    'very-difficult' => 'Very Difficult'
];

const PARKING_DIFFICULTY_DESCRIPTIONS = [
    'easy' => 'Plenty of parking available, rarely fills up',
    'moderate' => 'Usually find parking, may fill on weekends',
    'difficult' => 'Limited spots, arrive early on busy days',
    'very-difficult' => 'Very limited parking, consider alternate transport'
];

// Display labels for tags
const TAG_LABELS = [
    'calm-waters' => 'Calm Waters',
    'surfing' => 'Surfing',
    'snorkeling' => 'Snorkeling',
    'family-friendly' => 'Family Friendly',
    'accessible' => 'Accessible',
    'secluded' => 'Secluded',
    'popular' => 'Popular',
    'scenic' => 'Scenic',
    'swimming' => 'Swimming',
    'diving' => 'Diving',
    'fishing' => 'Fishing',
    'camping' => 'Camping'
];

// Display labels for amenities
const AMENITY_LABELS = [
    'restrooms' => 'Restrooms',
    'showers' => 'Showers',
    'lifeguard' => 'Lifeguard',
    'parking' => 'Parking',
    'food' => 'Food & Drinks',
    'equipment-rental' => 'Equipment Rental',
    'accessibility' => 'Wheelchair Accessible',
    'picnic-areas' => 'Picnic Areas',
    'shade-structures' => 'Shade/Umbrellas',
    'water-sports' => 'Water Sports'
];

// Display labels for conditions
const CONDITION_LABELS = [
    'sargassum' => [
        'none' => 'No Sargassum',
        'light' => 'Light Sargassum',
        'moderate' => 'Moderate Sargassum',
        'heavy' => 'Heavy Sargassum'
    ],
    'surf' => [
        'calm' => 'Calm',
        'small' => 'Small Waves',
        'medium' => 'Medium Waves',
        'large' => 'Large Waves'
    ],
    'wind' => [
        'calm' => 'Calm',
        'light' => 'Light Breeze',
        'moderate' => 'Moderate Wind',
        'strong' => 'Strong Wind'
    ]
];

// Validation helper functions (keep these in constants.php as they only use constants)
function isValidTag($tag) {
    return in_array($tag, TAGS);
}

function isValidAmenity($amenity) {
    return in_array($amenity, AMENITIES);
}

function isValidMunicipality($municipality) {
    return in_array($municipality, MUNICIPALITIES);
}

function isWithinPRBounds($lat, $lng) {
    return $lat >= PR_BOUNDS['lat']['min'] &&
           $lat <= PR_BOUNDS['lat']['max'] &&
           $lng >= PR_BOUNDS['lng']['min'] &&
           $lng <= PR_BOUNDS['lng']['max'];
}

// NOTE: Display label functions (getTagLabel, getAmenityLabel, getConditionLabel)
// are defined in inc/helpers.php to avoid duplicate declarations.
// Include helpers.php if you need these functions.
