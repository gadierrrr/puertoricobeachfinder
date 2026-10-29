# Beach URL and biobay corrections — October 2026

Migration 060 adds 73 exact aliases listed in `config/beach-slug-aliases.php`.
Each target was checked against the published production catalog. Existing
published pages win; redirects point by beach ID, use one 301 hop, retain
English/Spanish routes and campaign query parameters, and never expose draft
records. Las Paldas / La Jungla is distinct from Las Pardas / Parda Beach.
Reference: https://www.descubrapuertorico.com/playa/playa-las-paldas/

These 25 observed URL variants still need verified catalog records or an
unambiguous destination. They retain a real, noindex 404 with a localized
link back to the directory; do not redirect them to an unrelated beach:

- Schoolyards: `schoolyards-beach`, `schoolyards`, `school-yards`.
- La Poza, Aguadilla: `la-poza-beach`, `la-poza`, `la-poza-aguadilla`,
  `la-poza-beach-aguadilla`, `playa-la-poza`, `la-poza-agua-dilla`,
  `la-poza-agudilla`, `playa-la-poza-aguadilla`. This is not Poza del Obispo.
- Atolladora: `atolladora-beach`, `atolladora`, `atolladora-ballena-bay`,
  `atolladora-beach-guanica`, `playa-atolladora`. Sources distinguish the
  Atolladora shore from nearby Playa Ballena; a similar name is insufficient.
- Tres Hermanos: `tres-hermanos`, `tres-hermanos-beach`, `balneario-de-anasco`.
  Verify the Añasco location against the catalog record labeled Rincón.
- Existing location review: `ojo-de-agua-beach`, `coco-beach-rio-grande`,
  `las-picuas`. Preserve the curation restrictions in migration 058.
- Ambiguous names: `blue-hole-pozo-de-jacinto`, `fuerte`, `puerto-real`.

Biobay body copy, visible FAQs, FAQ JSON-LD and article modification dates are
updated in English and Spanish. Mosquito Bay and Laguna Grande prohibit
swimming. La Parguera swimming depends on the authorized itinerary and current
restrictions. Removed blanket tour/transport prices, pier assumptions, fixed
brightness rankings, and the incorrect claim that summer has longer darkness.
The static guide is the active production source; no published CMS override was
present during the audit.

Reviewed sources:
- https://www.discoverpuertorico.com/protecting-puerto-ricos-bioluminescent-bays
- https://www.discoverpuertorico.com/bio-bays
- https://www.callejeandopr.com/callejea/playa-atolladora-yauco
- https://welcome.topuertorico.org/thingstodo/beaches-marinas.shtml

Validation: `php scripts/test-beach-aliases.php` uses a disposable dev database.
Follow deployment with HTTP checks of both locale prefixes and query retention.
