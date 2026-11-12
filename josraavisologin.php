<?php
/**
 * Aviso en el inicio de sesión
 *
 * Pone un aviso junto a todos los formularios de acceso de la tienda (página de inicio de sesión,
 * login del checkout, ventanas de login de otros módulos y el checkout de un paso de josra) para que
 * el cliente que no recuerda su contraseña, o al que no se la acepta, vaya directamente a restablecerla.
 * Si el intento de acceso falla, el aviso se resalta.
 *
 * Sin overrides: hook actionFrontControllerSetMedia y JavaScript. PrestaShop 1.7 a 9.
 *
 * @author josra
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/classes/JosraavisologinUpdater.php';

class Josraavisologin extends Module
{
    const C_ENABLED = 'JOSRAAVISOLOGIN_ENABLED';
    const C_TEXT = 'JOSRAAVISOLOGIN_TEXT';
    const C_LINK = 'JOSRAAVISOLOGIN_LINK';
    const C_WHEN = 'JOSRAAVISOLOGIN_WHEN';

    public function __construct()
    {
        $this->name = 'josraavisologin';
        $this->tab = 'front_office_features';
        $this->version = '1.0.4';
        $this->author = 'josra';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->l('Aviso en el inicio de sesión');
        $this->description = $this->l('Avisa en todos los formularios de acceso de que, si no recuerdas la contraseña o no te la acepta, la restablezcas, con el enlace directo.');
    }

    /* ------------------------------------------------------------------ */
    /*  Instalación                                                        */
    /* ------------------------------------------------------------------ */

    public static function defaultTexts()
    {
        return [
            'es' => [
                'text' => '¿No puedes entrar? Si no recuerdas tu contraseña o no te la acepta, es que no es la correcta (a veces el navegador tiene guardada una antigua).',
                'link' => 'Crea una contraseña nueva aquí',
            ],
            'en' => [
                'text' => 'Can\'t sign in? If you don\'t remember your password or it isn\'t accepted, it isn\'t the right one (sometimes the browser has an old one saved).',
                'link' => 'Create a new password here',
            ],
        ];
    }

    public function install()
    {
        $defaults = self::defaultTexts();
        $text = [];
        $link = [];
        foreach (Language::getLanguages(false) as $lang) {
            $d = isset($defaults[$lang['iso_code']]) ? $defaults[$lang['iso_code']] : $defaults['en'];
            $text[(int) $lang['id_lang']] = $d['text'];
            $link[(int) $lang['id_lang']] = $d['link'];
        }

        return parent::install()
            && Configuration::updateValue(self::C_ENABLED, 1)
            && Configuration::updateValue(self::C_TEXT, $text)
            && Configuration::updateValue(self::C_LINK, $link)
            && Configuration::updateValue(self::C_WHEN, 'always')
            && $this->registerHook('actionFrontControllerSetMedia')
            && $this->getUpdater()->registerHooks();
    }

    public function uninstall()
    {
        // Aviso al servidor de actualizaciones (no impide desinstalar si no responde)
        $this->getUpdater()->notifyUninstall();

        foreach ([self::C_ENABLED, self::C_TEXT, self::C_LINK, self::C_WHEN, 'JOSRAAVISOLOGIN_UPD_TS', 'JOSRAAVISOLOGIN_UPD_INFO', 'JOSRAAVISOLOGIN_UPD_SU', 'JOSRAAVISOLOGIN_UPD_LT'] as $key) {
            Configuration::deleteByName($key);
        }

        return parent::uninstall();
    }

    /* ------------------------------------------------------------------ */
    /*  Actualizaciones                                                    */
    /* ------------------------------------------------------------------ */

    public function getUpdater()
    {
        return new JosraavisologinUpdater($this);
    }

    /** Aviso periódico al servidor de actualizaciones (solo en el back-office) */
    public function hookActionAdminControllerSetMedia($params)
    {
        $this->getUpdater()->maybePing();
    }

    /** PS 8.1+: la versión nueva aparece en Gestor de módulos > Actualizaciones */
    public function hookActionListModules($params)
    {
        return $this->getUpdater()->getNativeModuleList();
    }

    /* ------------------------------------------------------------------ */
    /*  Tienda                                                             */
    /* ------------------------------------------------------------------ */

    public function hookActionFrontControllerSetMedia($params)
    {
        if (!Configuration::get(self::C_ENABLED)) {
            return;
        }
        // Con la sesión iniciada no hay ningún login que avisar
        if (isset($this->context->customer) && $this->context->customer->isLogged()) {
            return;
        }
        $idLang = (int) $this->context->language->id;
        $text = trim((string) Configuration::get(self::C_TEXT, $idLang));
        if ($text === '') {
            return;
        }

        Media::addJsDef(['josraAvisoLogin' => [
            'text' => $text,
            'link' => trim((string) Configuration::get(self::C_LINK, $idLang)),
            'url' => $this->context->link->getPageLink('password', true),
            'when' => (string) Configuration::get(self::C_WHEN) === 'error' ? 'error' : 'always',
        ]]);
        // Fuera de la caché CCC de PrestaShop y con la versión en la dirección: al actualizar el módulo,
        // el navegador y la tienda cargan siempre el aviso nuevo sin tener que borrar cachés
        $base = $this->context->link->getBaseLink() . 'modules/' . $this->name . '/views/';
        $v = '?v=' . rawurlencode($this->version);
        $this->context->controller->registerJavascript('josraavisologin', $base . 'js/aviso.js' . $v, ['server' => 'remote', 'position' => 'bottom', 'priority' => 200]);
        $this->context->controller->registerStylesheet('josraavisologin', $base . 'css/aviso.css' . $v, ['server' => 'remote', 'media' => 'all', 'priority' => 200]);
    }

    /* ------------------------------------------------------------------ */
    /*  Configuración                                                      */
    /* ------------------------------------------------------------------ */

    public function getContent()
    {
        $updater = $this->getUpdater();
        $output = $updater->processRequest();

        if (Tools::isSubmit('submitJosraAvisoLogin')) {
            $text = [];
            $link = [];
            foreach (Language::getLanguages(false) as $lang) {
                $id = (int) $lang['id_lang'];
                $text[$id] = trim(strip_tags((string) Tools::getValue(self::C_TEXT . '_' . $id)));
                $link[$id] = trim(strip_tags((string) Tools::getValue(self::C_LINK . '_' . $id)));
            }
            Configuration::updateValue(self::C_ENABLED, (int) (bool) Tools::getValue(self::C_ENABLED));
            Configuration::updateValue(self::C_TEXT, $text);
            Configuration::updateValue(self::C_LINK, $link);
            Configuration::updateValue(self::C_WHEN, Tools::getValue(self::C_WHEN) === 'error' ? 'error' : 'always');
            $output .= $this->displayConfirmation($this->l('Configuración guardada.'));
        }

        $info = $updater->getInfo();
        $panel = $updater->renderPanel();
        $form = $this->renderForm();

        return !empty($info['update_available']) ? $output . $panel . $form : $output . $form . $panel;
    }

    private function renderForm()
    {
        $form = ['form' => [
            'legend' => ['title' => $this->l('Aviso en el inicio de sesión'), 'icon' => 'icon-key'],
            'description' => $this->l('El aviso sale junto a todos los formularios de acceso de la tienda: la página de inicio de sesión, el login del checkout, las ventanas de login de otros módulos y el checkout de un paso. El enlace lleva a la página «¿Olvidó su contraseña?».'),
            'input' => [
                [
                    'type' => 'switch',
                    'label' => $this->l('Mostrar el aviso'),
                    'name' => self::C_ENABLED,
                    'is_bool' => true,
                    'values' => [
                        ['id' => 'on', 'value' => 1, 'label' => $this->l('Sí')],
                        ['id' => 'off', 'value' => 0, 'label' => $this->l('No')],
                    ],
                ],
                [
                    'type' => 'radio',
                    'label' => $this->l('Cuándo se muestra'),
                    'name' => self::C_WHEN,
                    'values' => [
                        ['id' => 'when_always', 'value' => 'always', 'label' => $this->l('Siempre (y se resalta si falla el acceso)')],
                        ['id' => 'when_error', 'value' => 'error', 'label' => $this->l('Solo después de un intento fallido')],
                    ],
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->l('Texto del aviso'),
                    'name' => self::C_TEXT,
                    'lang' => true,
                    'rows' => 3,
                    'desc' => $this->l('Texto sin formato. Si lo dejas vacío en un idioma, en ese idioma no sale el aviso.'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Texto del enlace'),
                    'name' => self::C_LINK,
                    'lang' => true,
                ],
            ],
            'submit' => ['title' => $this->l('Guardar'), 'name' => 'submitJosraAvisoLogin'],
        ]];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->module = $this;
        $helper->table = $this->table;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->submit_action = 'submitJosraAvisoLogin';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $values = [
            self::C_ENABLED => (int) Configuration::get(self::C_ENABLED),
            self::C_WHEN => Configuration::get(self::C_WHEN) ?: 'always',
        ];
        foreach (Language::getLanguages(false) as $lang) {
            $id = (int) $lang['id_lang'];
            $values[self::C_TEXT][$id] = Configuration::get(self::C_TEXT, $id);
            $values[self::C_LINK][$id] = Configuration::get(self::C_LINK, $id);
        }
        $helper->tpl_vars = [
            'fields_value' => $values,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => (int) $this->context->language->id,
        ];

        return $helper->generateForm([$form]);
    }
}
