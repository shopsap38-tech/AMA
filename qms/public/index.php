<?php

/**
 * Point d'entrée unique (Front Controller) de l'application QMS.
 * Toutes les requêtes HTTP sont routées à travers ce fichier.
 */

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/autoload.php';

(new App\Core\App())->run();
