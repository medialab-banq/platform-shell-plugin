[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html)

# Extension principale de la plateforme

## Contenu

Une extension compatible WordPress offrant des fonctionnalités complémentaires au **[thème principal](https://github.com/medialab-banq/platform-shell-theme)** et permettant de créer une plateforme de médialab collaborative.

## Notes importantes

* Cette extension n’est pas disponible dans l’écosystème WordPress et doit être installée manuellement.
* Cette extension doit obligatoirement être installée avec le thème principal.
* Cette extension ne doit pas être installée sur un site existant sauf pour la mise à jour d’une installation précédente de la plateforme.

## Fonctionnalités

* Gestion de l’activation de l’extension et configuration initiale.
* Définitions des entités spécifiques à la plateforme (projets, concours, équipements, activités, outils numériques, profils d’utilisateurs).
* Gestion des entités (création, validation, affichage, etc.).
* Définition des rôles « utilisateur » et « gestionnaire » ainsi que des opérations permises pour ces rôles.
* Création de menus et de pages secondaires pour faciliter la mise en place d’un site complet.
* Signalement de contenu.
* Gestion de configurations permettant de personnaliser la plateforme.
* Gestion de restrictions.
* Notification de la plateforme pour certains processus.
* Fonctionnalités communes pour le fonctionnement d’ensemble de la plateforme.

## Installation simple et installation complète de la plateforme

Voir le [Wiki](https://github.com/medialab-banq/platform-shell-plugin/wiki) du projet.

## Développement

### Environnement et outils

* Serveur Wamp ou Lamp.
    * Par exemple : [WampServer](http://www.wampserver.com/), [VVV](https://github.com/Varying-Vagrant-Vagrants/VVV).
* PHP 5.6 à 7.x avec extension Xdebug.
* Environnement de développement au choix : Eclipse, Netbeans.
* (optionnel) Utilitaire gratuit pouvant faciliter la traduction et l’adaptation des textes [Poedit](https://poedit.net/).

### Installation des sources

* 1) Suivez les étapes d’une installation simple (voir le [Wiki](https://github.com/medialab-banq/platform-shell-plugin/wiki/Installation-simple)). Au lieu du « Release », téléchargez et installez les sources de l’extension principale et du thème principal dans les dossiers correspondants de WordPress :
     - Ex. (extension) : wp-content\plugins\platform-shell-plugin.
     - Ex. (thème) : wp-content\themes\platform-shell-theme.
* 2) Installez Composer (https://getcomposer.org/).
* 3) Installez les dépendances Composer de platform-shell-plugin (composer install ou composer install --no-dev).
* 4) (optionnel / fortement recommandé) Installez Phpcs https://github.com/squizlabs/PHP_CodeSniffer (procédure à ajuster selon votre environnement).
     Les configurations utilisées (WordPress avec quelques ajustements) se trouvent dans le dossier wpcs des sources de l’extension.
* 5) (optionnel / fortement recommandé) Installez les outils de localisation. Voir [grunt-wp-i18n](https://github.com/cedaro/grunt-wp-i18n).

### Autres informations

* Voir la documentation de WordPress sur le développement de thème et d’extension.
    * https://codex.wordpress.org/Writing_a_Plugin
    * https://codex.wordpress.org/Theme_Development
* Phpcs doit être exécuté dans platform-shell-plugin et dans platform-shell-theme séparément.
* « grunt makepot » doit être exécuté dans platform-shell-plugin et dans platform-shell-theme séparément.

## Licence

De manière générale, les sources de l’extension sont distribuées sous GPL V2.
Une copie de la licence est disponible au premier niveau du dossier de l’extension. Le fichier est nommé `LICENSE` (en anglais).

L’extension étant un assemblage de sources originales et de sources existantes ou modifiées, certaines parties des sources ont des licences différentes mais il a été vérifié qu’elles sont compatibles avec la licence principale. Voici les détails :

* JQuery : licence [MIT](https://tldrlegal.com/license/mit-license).
* Dossier /src/lib/autoloader. Version modifiée de namespaces-and-autoloading-in-wordpress : licence [GPL v3](https://www.gnu.org/licenses/gpl-3.0.en.html.
* Dossier /src/lib/wordpress-settings-api-class-master. Version modifiée de [wordpress-settings-api-class-master](https://github.com/tareq1988/wordpress-settings-api-class) : licence non spécifiée mais étant donné l’intégration WordPress, nécessairement GPL.
* Dossier /src/lib/plugin-update-checker-4.4. [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker). ([MIT](https://tldrlegal.com/license/mit-license)). Copyright (c) 2017 Jānis Elsts.
* Dossier /vendor/ (si installé) : Dépendances [Composer](https://getcomposer.org/). Voir les documents `LICENCE.MD` dans les sous-dossiers respectifs. : différentes licences ([MIT](https://tldrlegal.com/license/mit-license) et/ou licence personnalisée (ex. : obligation de conserver la mention de copyright)).
* Dossier /css/images/. Ressources graphiques provenant de [JQuery-UI](https://github.com/jquery/jquery-ui). [CC0](http://creativecommons.org/publicdomain/zero/1.0/). Voir aussi [LICENCE.txt](https://github.com/jquery/jquery-ui/blob/master/LICENSE.txt) sur Github.
* Dossier /js/lib/leaflet. [Leaflet](http://leafletjs.com/) : [BDS-2](https://opensource.org/licenses/BSD-2-Clause). Copyright : (c) 2010-2017 Vladimir Agafonkin, (c) 2010-2011 CloudMade.
* Dossier js/lib/TileLayer.Grayscale.js. Extension Leaflet [Leaflet.TileLayer.Grayscale](https://github.com/Zverik/leaflet-grayscale). Licence libérale [WTFPL](http://www.wtfpl.net/).
* Dossier js/lib/Select2. Librairie [Select2](https://github.com/select2/select2). Licence ([MIT](https://tldrlegal.com/license/mit-license)). Copyright (c) 2012-2017 Kevin Brown, Igor Vaynberg et contributeurs.

## Documentation

Voir le [Wiki](https://github.com/medialab-banq/platform-shell-plugin/wiki) du projet.

## Remerciements

Ce projet s’inscrit dans le contexte de la mise en œuvre d’une mesure du [Plan culturel numérique du Québec](http://culturenumerique.mcc.gouv.qc.ca/), en collaboration avec la [Banque nationale](https://www.bnc.ca) et la [Fondation de BAnQ](https://fondation.banq.qc.ca/).
