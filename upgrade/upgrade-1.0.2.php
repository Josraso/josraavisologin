<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * 1.0.2: el CSS y el JS del aviso dejan de ir en la caché CCC. Se vacía esa caché una vez
 * para que deje de servirse la versión anterior.
 *
 * @param Josraavisologin $module
 */
function upgrade_module_1_0_2($module)
{
    if (method_exists('Media', 'clearCache')) {
        Media::clearCache();
    }

    return $module->getUpdater()->registerHooks();
}
