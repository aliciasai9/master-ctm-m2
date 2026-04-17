# Reporting methodologique — Projet M2 CTM

## 1) Corpus, objectifs et sources

Le projet vise a produire une page culturelle web dynamique, alimentee par une base de donnees relationnelle, puis enrichie semantiquement pour une lecture humaine et machine.  
Le corpus retenu porte sur des sites inscrits au patrimoine mondial de l'UNESCO en Afrique du Nord (Algerie, Tunisie, Maroc, Libye, Mauritanie), avec un minimum de 10 entites.

Source de travail :
- Jeu de donnees structure en CSV : `unesco_na.csv`
- Donnees descriptives : nom du site, description, date, localisation geographique, categorie UNESCO, pays, image

Le corpus final integre 23 entites exploitables apres nettoyage.

## 2) Preparation des donnees (OpenRefine)

### 2.1 Nettoyage documentaire

Le fichier CSV presentait plusieurs problemes :
- lignes vides ou incompletes
- identifiants manquants
- descriptions en HTML avec entites
- heterogeneite de certains champs textuels

Traitements realises dans OpenRefine (ou equivalents de nettoyage) :
- suppression des lignes sans identifiant ou sans nom
- normalisation des valeurs textuelles
- elimination des balises HTML dans les descriptions
- verification des doublons evidents
- controle de coherence des champs `country`, `category`, `date`, `location`

### 2.2 Structuration des champs

Les champs ont ete stabilises pour etre compatibles avec un import SQL simple :
- `id` : identifiant UNESCO
- `nom` : intitule du site
- `image` : URL d'illustration
- `description` : resume textuel nettoye
- `date` : date ISO
- `location` : latitude,longitude
- `category` : Cultural / Natural / Mixed
- `country` : pays

## 3) Modelisation relationnelle et base SQL

### 3.1 Choix de modelisation

Conformement a la consigne, le modele repose sur **une seule table**.

Base :
- `corpus_ctm`

Table :
- `sites_unesco`

Colonnes :
- `id` (INT UNSIGNED, cle primaire)
- `nom` (VARCHAR)
- `image` (VARCHAR)
- `description` (TEXT)
- `date_inscription` (DATE)
- `geo` (VARCHAR, format lat,lon)
- `categorie` (VARCHAR)
- `pays` (VARCHAR)

Index complementaires :
- index sur `pays`
- index sur `categorie`

Ce schema permet :
- un affichage dynamique direct en PHP
- un tri/filtrage rapide par pays et categorie
- une extension future (ajout de tables si besoin)

### 3.2 Export SQL et reproductibilite

Le fichier d'export livre est :
- `sql/corpus_unesco.sql`

Il contient :
- creation de la base
- creation de la table
- insertion des 23 enregistrements

Commande d'import :

```bash
mysql -u root -p < sql/corpus_unesco.sql
```

Le choix d'un export complet garantit la reproductibilite du projet sur un autre poste.

## 4) Chargement PHP et affichage dynamique

La page unique `index.php` utilise :
- connexion MySQLi
- requete SQL de selection
- parcours des resultats avec `foreach`

Fonctionnalites integrees :
- affichage des cartes de sites UNESCO
- tri par pays puis nom
- filtre par pays (parametre GET)
- extraction des coordonnees a partir du champ `geo`

Le rendu repose sur une structure HTML5 lisible (`header`, `main`, `article`, `footer`), avec separation claire entre metadonnees et contenu principal.

## 5) Enrichissement semantique (schema.org)

Deux niveaux d'annotation sont proposes :

1. **Microdonnees inline** dans chaque fiche de site :
- `TouristAttraction`
- `name`, `description`, `image`, `datePublished`
- `addressCountry` (via `PostalAddress`)
- `GeoCoordinates` si coordonnees disponibles

2. **JSON-LD global** :
- `CollectionPage`
- `ItemList` avec la liste des sites

Cette double approche facilite l'interpretabilite machine par des outils de validation semantique.

## 6) Tests et validation

Controles realises :
- import SQL sans erreur
- presence de la table `sites_unesco`
- verification du nombre de lignes (23)
- affichage dynamique operationnel en PHP
- coherence des microdonnees avec les champs de la base

Verification conseillee pour le rendu final :
- validation HTML5 (W3C)
- test semantique via plugin OSDS et capture d'ecran

## 7) Difficultes rencontrees

- Qualite inegale du CSV initial (lignes incompletes)
- Presence de balises HTML et d'entites dans les descriptions
- Uniformisation des formats (date, coordonnees)
- Gestion de certains caracteres speciaux dans les textes

## 8) Limites et pistes d'amelioration

Limites :
- table unique (choix impose par la consigne), peu normalisee
- champ geographique stocke en texte (`lat,lon`) au lieu d'un type spatial
- source image externe parfois instable

Ameliorations possibles :
- decomposition en plusieurs tables (`sites`, `pays`, `categories`)
- geocodage en colonnes numeriques dediees
- enrichissement par identifiants externes (Wikidata)
- ajout d'un filtrage multi-criteres (categorie + periode)

## 9) Conclusion

Le projet repond aux objectifs de la chaine de publication orientee donnees :
- corpus structure et nettoye
- base relationnelle MySQL exploitable
- generation dynamique PHP
- structuration semantique HTML5 + schema.org

Le livrable est directement reutilisable par l'ensemble du groupe pour finaliser la presentation web et la remise finale.
