<?php

namespace VisitMarche\ThemeTail;

use VisitMarche\ThemeTail\Inc\AdminPage;
use VisitMarche\ThemeTail\Inc\Ajax;
use VisitMarche\ThemeTail\Inc\ApiRoutes;
use VisitMarche\ThemeTail\Inc\AssetsLoad;
use VisitMarche\ThemeTail\Inc\PivotMetaBox;
use VisitMarche\ThemeTail\Inc\SecurityConfig;
use VisitMarche\ThemeTail\Inc\Seo;
use VisitMarche\ThemeTail\Inc\SetupTheme;
use VisitMarche\ThemeTail\Inc\ShortCodes;
use VisitMarche\ThemeTail\Lib\RouterPivot;

/**
 * Initialisation du thème
 */
new SetupTheme();
/**
 * Chargement css, js
 */
new AssetsLoad();
/**
 * Un peu de sécurité
 */
new SecurityConfig();
/**
 * Enregistrement des routes api
 */
new ApiRoutes();
/**
 * Ajout de routage pour pivot
 */
new RouterPivot();
/**
 * Pour enregistrer filtres pivot
 */
new PivotMetaBox();
/**
 * Balises pour le référencement
 */
new Seo();
/**
 * Gpx viewer
 */
new ShortCodes();
/**
 * Admin pages
 */
new AdminPage();
/**
 * Ajax for admin
 */
new Ajax();

