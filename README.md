# Advanced CSV Exporter Wordpress Plugin

Exportez facilement vos utilisateurs, produits, catégories et commandes WooCommerce au format CSV depuis l'administration WordPress.

**Version:** 1.0.0

**Requis:** WordPress >= 5.8, PHP >= 7.4. Pour l'export des commandes, WooCommerce >= 6.0.

## Résumé

Advanced CSV Exporter ajoute des boutons d'export CSV sur les pages de listes de l'administration :
- Utilisateurs
- Produits
- Catégories de produits
- Commandes (si WooCommerce actif)

Le plugin applique des vérifications de capacité et de nonce, filtre les paramètres GET reçus et produit des CSV compatibles avec Excel (BOM UTF-8). Vous pouvez choisir le séparateur et activer l'export en streaming pour les gros volumes.

## Fichiers principaux

- `advanced-csv-exporter.php` : bootstrap du plugin.
- `includes/class-exporter-base.php` : classe abstraite commune (gestion des filtres, génération CSV, sécurité, en-têtes HTTP, streaming).
- `includes/class-admin.php` : enregistrement des exporteurs, hooks d'admin et page de réglages.
- `includes/class-users-exporter.php` : export des utilisateurs.
- `includes/class-products-exporter.php` : export des produits.
- `includes/class-categories-exporter.php` : export des catégories.
- `includes/class-orders-exporter.php` : export des commandes (chargé si WooCommerce actif).
- `assets/css/admin.css` : styles du bouton d'export.

## Installation

1. Copier le dossier `advanced-csv-exporter` dans `wp-content/plugins/`.
2. Activer le plugin depuis le menu Extensions de WordPress.
3. Si vous souhaitez exporter les commandes, assurez-vous que WooCommerce est installé et activé.

## Réglages

Une page de réglages est disponible via `Réglages > Export CSV` (ou depuis la page d'options du plugin). Options disponibles :
- **Séparateur CSV** : `,`, `;` (par défaut) ou `Tab`.
- **Streaming** : activer/désactiver l'export en streaming (`fputcsv` vers `php://output`). Le streaming est recommandé pour de très grands jeux de données car il évite de tout charger en mémoire.

Les options sont sauvegardées via l'API Settings de WordPress.

## Utilisation

- Aller sur la page de listing correspondante (ex: Utilisateurs, Produits, Catégories ou Commandes).
- Repérer le bouton "Exporter en CSV" en haut à droite des filtres.
- Cliquer : un fichier CSV sera téléchargé en respectant les filtres de la page et les réglages choisis.

## Sécurité & permissions

Chaque export vérifie :
- la présence d'un nonce (`_wpnonce`) et sa validité ;
- que l'utilisateur courant possède la capacité nécessaire (`list_users`, `edit_products`, `manage_categories`, `edit_shop_orders`).

## Format CSV

- Encodage : UTF-8 avec BOM (compatible Excel).
- Délimiteur : configurable (`,`, `;`, `tab`).
- Les valeurs contenant le séparateur, des guillemets ou des retours à la ligne sont échappées correctement.

## Implémentation et internationalisation

- Les en-têtes CSV utilisent les fonctions d'i18n (`__()`) pour pouvoir être traduites.
- Les exports disposent d'une implémentation en streaming (`generate_csv_stream`) qui écrit directement sur `php://output` via `fputcsv()` quand le streaming est activé.
- Les exports conservent une implémentation mémoire (ancienne méthode) en cas de désactivation du streaming.

## Personnalisation / développement

- Pour ajouter un nouvel exporteur, créez une classe héritièr(e) de `Adv_CSV_Exporter_Base`, définissez `$id`, `$title`, `capability`, implémentez `add_export_button()` et `generate_csv()`; pour de gros volumes, implémentez `generate_csv_stream( $filters, $out, $delimiter )`.
- Enregistrez votre exporteur dans `includes/class-admin.php` (méthode `register_exporters`).

## Tests locaux rapides

- Vérifier la syntaxe PHP du plugin :

```bash
php -l advanced-csv-exporter.php
php -l includes/class-exporter-base.php
php -l includes/class-admin.php
```

- Activer `WP_DEBUG` pour afficher les erreurs lors des tests.

## Changelog

- 1.0.0 — Première version : export utilisateurs, produits, catégories, commandes (si WooCommerce présent). Ajout : options (séparateur, streaming), streaming via `fputcsv`, i18n des en-têtes.

---
