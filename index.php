<?php
declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$dbHost = '127.0.0.1';
$dbUser = 'root';
$dbPass = '';
$dbName = 'corpus_ctm';

$selectedCountry = isset($_GET['pays']) ? trim((string) $_GET['pays']) : '';
$sites = [];
$countries = [];
$error = null;

try {
    $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    $mysqli->set_charset('utf8mb4');

    $countryQuery = $mysqli->query("SELECT DISTINCT pays FROM sites_unesco WHERE pays IS NOT NULL AND pays <> '' ORDER BY pays");
    while ($row = $countryQuery->fetch_assoc()) {
        $countries[] = $row['pays'];
    }

    if ($selectedCountry !== '') {
        $stmt = $mysqli->prepare(
            "SELECT id, nom, image, description, date_inscription, geo, categorie, pays
             FROM sites_unesco
             WHERE pays = ?
             ORDER BY nom"
        );
        $stmt->bind_param('s', $selectedCountry);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $mysqli->query(
            "SELECT id, nom, image, description, date_inscription, geo, categorie, pays
             FROM sites_unesco
             ORDER BY pays, nom"
        );
    }

    while ($row = $result->fetch_assoc()) {
        $latitude = null;
        $longitude = null;
        if (!empty($row['geo']) && strpos($row['geo'], ',') !== false) {
            [$lat, $lng] = array_map('trim', explode(',', (string) $row['geo'], 2));
            if (is_numeric($lat) && is_numeric($lng)) {
                $latitude = (float) $lat;
                $longitude = (float) $lng;
            }
        }

        $row['latitude'] = $latitude;
        $row['longitude'] = $longitude;
        $sites[] = $row;
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$siteCount = count($sites);

// JSON-LD global de la page + éléments principaux pour faciliter l'extraction machine.
$jsonLdItems = [];
foreach ($sites as $site) {
    $item = [
        '@type' => 'TouristAttraction',
        'identifier' => (string) $site['id'],
        'name' => $site['nom'],
        'description' => $site['description'],
        'datePublished' => $site['date_inscription'],
        'address' => [
            '@type' => 'PostalAddress',
            'addressCountry' => $site['pays'],
        ],
    ];

    if (!empty($site['image'])) {
        $item['image'] = $site['image'];
    }
    if ($site['latitude'] !== null && $site['longitude'] !== null) {
        $item['geo'] = [
            '@type' => 'GeoCoordinates',
            'latitude' => $site['latitude'],
            'longitude' => $site['longitude'],
        ];
    }

    $jsonLdItems[] = $item;
}

$pageGraph = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'CollectionPage',
            'name' => 'Patrimoine mondial UNESCO en Afrique du Nord',
            'description' => 'Sélection de sites UNESCO issue d’un corpus structuré et affichée dynamiquement depuis MySQL.',
            'inLanguage' => 'fr',
            'about' => ['@id' => '#collection-unesco'],
        ],
        [
            '@id' => '#collection-unesco',
            '@type' => 'ItemList',
            'name' => 'Liste des sites UNESCO',
            'numberOfItems' => $siteCount,
            'itemListElement' => $jsonLdItems,
        ],
    ],
];
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Patrimoine mondial UNESCO — Afrique du Nord</title>
  <meta name="description" content="Page culturelle HTML5 sémantique alimentée en PHP/MySQL à partir d’un corpus UNESCO.">
  <style>
    :root {
      --bg: #f8fafc;
      --panel: #ffffff;
      --text: #1f2937;
      --muted: #6b7280;
      --line: #d1d5db;
      --accent: #0f766e;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
      line-height: 1.55;
      color: var(--text);
      background: var(--bg);
    }
    header, main, footer {
      width: min(1080px, 92vw);
      margin: 0 auto;
    }
    header {
      padding: 2.25rem 0 1.5rem;
    }
    h1 {
      margin: 0 0 0.4rem;
      font-size: clamp(1.55rem, 3vw, 2.2rem);
    }
    .subtitle {
      margin: 0;
      color: var(--muted);
    }
    .toolbar {
      margin: 1rem 0 0;
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 0.6rem;
    }
    label { font-weight: 600; }
    select, button, .reset-link {
      border: 1px solid var(--line);
      border-radius: 0.45rem;
      padding: 0.48rem 0.65rem;
      background: #fff;
      font-size: 0.95rem;
      color: var(--text);
      text-decoration: none;
    }
    button {
      background: var(--accent);
      color: #fff;
      border-color: var(--accent);
      cursor: pointer;
    }
    .meta {
      margin: 0 0 1rem;
      color: var(--muted);
    }
    .list {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
      gap: 1rem;
      padding: 0;
      list-style: none;
    }
    .card {
      background: var(--panel);
      border: 1px solid var(--line);
      border-radius: 0.75rem;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      min-height: 100%;
    }
    .thumb {
      display: block;
      width: 100%;
      height: 190px;
      object-fit: cover;
      background: #e5e7eb;
    }
    .card-body {
      padding: 0.95rem;
      display: flex;
      flex-direction: column;
      gap: 0.6rem;
    }
    .card h2 {
      margin: 0;
      font-size: 1.1rem;
      line-height: 1.3;
    }
    .chips {
      display: flex;
      flex-wrap: wrap;
      gap: 0.4rem;
    }
    .chip {
      display: inline-block;
      border: 1px solid var(--line);
      background: #f1f5f9;
      border-radius: 999px;
      padding: 0.1rem 0.5rem;
      font-size: 0.8rem;
      color: #334155;
    }
    .desc {
      margin: 0;
      font-size: 0.95rem;
    }
    .coords {
      margin-top: auto;
      color: var(--muted);
      font-size: 0.85rem;
    }
    .error {
      border: 1px solid #fecaca;
      color: #b91c1c;
      background: #fef2f2;
      padding: 0.75rem;
      border-radius: 0.5rem;
    }
    footer {
      padding: 2rem 0 2.5rem;
      color: var(--muted);
      font-size: 0.92rem;
    }
  </style>
  <script type="application/ld+json"><?= json_encode($pageGraph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?></script>
</head>
<body>
  <header>
    <h1>Patrimoine mondial UNESCO en Afrique du Nord</h1>
    <p class="subtitle">Page dynamique générée en PHP avec MySQLi à partir d’un corpus structuré.</p>
    <form class="toolbar" method="get" action="">
      <label for="pays">Filtrer par pays :</label>
      <select id="pays" name="pays">
        <option value="">Tous les pays</option>
        <?php foreach ($countries as $country): ?>
          <option value="<?= htmlspecialchars($country, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedCountry === $country ? 'selected' : '' ?>>
            <?= htmlspecialchars($country, ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit">Appliquer</button>
      <a class="reset-link" href="index.php">Réinitialiser</a>
    </form>
  </header>

  <main>
    <?php if ($error !== null): ?>
      <p class="error"><strong>Erreur de connexion/lecture SQL :</strong> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php else: ?>
      <p class="meta"><?= $siteCount ?> site(s) affiché(s)<?= $selectedCountry !== '' ? ' pour ' . htmlspecialchars($selectedCountry, ENT_QUOTES, 'UTF-8') : '' ?>.</p>
      <ul class="list">
        <?php foreach ($sites as $site): ?>
          <li>
            <article
              class="card"
              itemscope
              itemtype="https://schema.org/TouristAttraction"
            >
              <?php if (!empty($site['image'])): ?>
                <img
                  class="thumb"
                  src="<?= htmlspecialchars($site['image'], ENT_QUOTES, 'UTF-8') ?>"
                  alt="Illustration du site <?= htmlspecialchars($site['nom'], ENT_QUOTES, 'UTF-8') ?>"
                  itemprop="image"
                >
              <?php endif; ?>
              <div class="card-body">
                <h2 itemprop="name"><?= htmlspecialchars($site['nom'], ENT_QUOTES, 'UTF-8') ?></h2>
                <div class="chips">
                  <span class="chip">UNESCO #<?= (int) $site['id'] ?></span>
                  <span class="chip" itemprop="additionalType"><?= htmlspecialchars((string) $site['categorie'], ENT_QUOTES, 'UTF-8') ?></span>
                  <time class="chip" datetime="<?= htmlspecialchars((string) $site['date_inscription'], ENT_QUOTES, 'UTF-8') ?>" itemprop="datePublished">
                    Inscription : <?= htmlspecialchars((string) $site['date_inscription'], ENT_QUOTES, 'UTF-8') ?>
                  </time>
                </div>
                <p class="desc" itemprop="description"><?= htmlspecialchars((string) $site['description'], ENT_QUOTES, 'UTF-8') ?></p>
                <p itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">
                  <strong>Pays :</strong> <span itemprop="addressCountry"><?= htmlspecialchars((string) $site['pays'], ENT_QUOTES, 'UTF-8') ?></span>
                </p>
                <?php if ($site['latitude'] !== null && $site['longitude'] !== null): ?>
                  <p class="coords" itemprop="geo" itemscope itemtype="https://schema.org/GeoCoordinates">
                    Coordonnées :
                    <span itemprop="latitude"><?= htmlspecialchars((string) $site['latitude'], ENT_QUOTES, 'UTF-8') ?></span>,
                    <span itemprop="longitude"><?= htmlspecialchars((string) $site['longitude'], ENT_QUOTES, 'UTF-8') ?></span>
                  </p>
                <?php endif; ?>
              </div>
            </article>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </main>

  <footer>
    Données : corpus UNESCO nettoyé (OpenRefine) et stocké en base MySQL. Affichage : PHP (MySQLi), HTML5 sémantique, microdonnées schema.org.
  </footer>
</body>
</html>
