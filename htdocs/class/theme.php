<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

use Xoops\Core\FixedGroups;

/**
 * XoopsTheme component class file
 *
 * @copyright The XOOPS project http://sourceforge.net/projects/xoops/
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @author    Skalpa Keo <skalpa@xoops.org>
 * @author    Taiwen Jiang <phppp@users.sourceforge.net>
 * @since     2.3.0
 * @package   class
 * @version   $Id$
 */

/**
 * XoopsThemeFactory
 *
 * @author Skalpa Keo
 * @since  2.3.0
 */
class XoopsThemeFactory
{
    /**
     * @var string
     */
    public $xoBundleIdentifier = 'XoopsThemeFactory';

    /**
     * Currently enabled themes (if empty, all the themes in themes/ are allowed)
     *
     * @var array
     */
    public $allowedThemes = array();

    /**
     * Default theme to instantiate if none specified
     *
     * @var string
     */
    public $defaultTheme = 'default';

    /**
     * If users are allowed to choose a custom theme
     *
     * @var bool
     */
    public $allowUserSelection = true;

    /**
     * Instantiate the specified theme
     *
     * @param array $options options array
     *
     * @return XoopsTheme
     */
    public function createInstance($options = array())
    {
        $xoops = Xoops::getInstance();
        // Grab the theme folder from request vars if present
        if (empty($options['folderName'])) {
            if (($req = @$_REQUEST['xoops_theme_select']) && $this->isThemeAllowed($req)) {
                $options['folderName'] = $req;
                if (isset($_SESSION) && $this->allowUserSelection) {
                    $_SESSION[$this->xoBundleIdentifier]['defaultTheme'] = $req;
                }
            } else {
                if (isset($_SESSION[$this->xoBundleIdentifier]['defaultTheme'])) {
                    $options['folderName'] = $_SESSION[$this->xoBundleIdentifier]['defaultTheme'];
                } else {
                    if (empty($options['folderName']) || !$this->isThemeAllowed($options['folderName'])) {
                        $options['folderName'] = $this->defaultTheme;
                    }
                }
            }
            $xoops->setConfig('theme_set', $options['folderName']);
        }
        $options['path'] = \XoopsBaseConfig::get('themes-path') . '/' . $options['folderName'];
        $inst = new XoopsTheme();
        foreach ($options as $k => $v) {
            $inst->$k = $v;
        }
        $inst->xoInit();
        return $inst;
    }

    /**
     * Checks if the specified theme is enabled or not
     *
     * @param string $name theme name
     *
     * @return bool
     */
    public function isThemeAllowed($name)
    {
        return (empty($this->allowedThemes) || in_array($name, $this->allowedThemes));
    }
}

/**
 * XoopsAdminThemeFactory
 *
 * @author Andricq Nicolas (AKA MusS)
 * @author trabis
 * @since  2.4.0
 */
class XoopsAdminThemeFactory extends XoopsThemeFactory
{
    public function createInstance($options = array())
    {
		$xoops = \Xoops::getInstance();
        $options["plugins"] = array();
        $options['renderBanner'] = false;
        $inst = parent::createInstance($options);
        $inst->path = \XoopsBaseConfig::get('adminthemes-path') . '/' . $inst->folderName;
        $inst->url = \XoopsBaseConfig::get('adminthemes-url') . '/' . $inst->folderName;
        $inst->template->assign(array(
            'theme_path' => $inst->path, 'theme_tpl' => $inst->path . '/xotpl', 'theme_url' => $inst->url,
            'theme_img'  => $inst->url . '/img', 'theme_icons' => $inst->url . '/icons',
            'theme_css'  => $inst->url . '/css', 'theme_js' => $inst->url . '/js',
            'theme_lang' => $inst->url . '/language',
        ));

        return $inst;
    }
}

class XoopsTheme
{
    /**
     * Should we render banner? Not for redirect pages or admin side
     *
     * @var bool
     */
    public $renderBanner = true;

    /**
     * The name of this theme
     *
     * @var string
     */
    public $folderName = '';

    /**
     * Physical path of this theme folder
     *
     * @var string
     */
    public $path = '';

    /**
     * @var string
     */
    public $url = '';

    /**
     * Whether or not the theme engine should include the output generated by php
     *
     * @var string
     */
    public $bufferOutput = true;

    /**
     * Canvas-level template to use
     *
     * @var string
     */
    public $canvasTemplate = 'theme.html';

    /**
     * Theme folder path
     *
     * @var string
     */
    public $themesPath = 'themes';

    /**
     * Content-level template to use
     *
     * @var string
     */
    public $contentTemplate = '';

    /**
     * @var int
     */
    public $contentCacheLifetime = 0;

    /**
     * @var string
     */
    public $contentCacheId = null;

    /**
     * Text content to display right after the contentTemplate output
     *
     * @var string
     */
    public $content = '';

    /**
     * Page construction plug-ins to use
     *
     * @var array
     * @access public
     */
    public $plugins = array('XoopsThemeBlocksPlugin');

    /**
     * @var int
     */
    public $renderCount = 0;

    /**
     * Pointer to the theme template engine
     *
     * @var XoopsTpl
     */
    public $template = false;

    /**
     * Array containing the document meta-information
     *
     * @var array
     */
    public $metas = array(
        'meta' => array(), 'link' => array(), 'script' => array()
    );

    /**
     * Asset manager instance
     *
     * @var object
     */
    public $assets = null;

    /**
     * Array containing base assets for the document
     *
     * @var array
     */
    public $baseAssets = array(
        'js' => array(),
        'css' => array(),
    );

    /**
     * Array of strings to be inserted in the head tag of HTML documents
     *
     * @var array
     */
    public $htmlHeadStrings = array();

    /**
     * Custom variables that will always be assigned to the template
     *
     * @var array
     */
    public $templateVars = array();

    /**
     * User extra information for cache id, like language, user groups
     *
     * @var boolean
     */
    public $use_extra_cache_id = true;

    /**
     * Engine used for caching headers information
     * Default is 'file', you can choose 'model' for database storage
     * or any other cache engine available in the class/cache folder
     *
     * @var boolean
     */
    public $headersCacheEngine = 'default';

    /**
     * *#@-
     */

    /**
     * *#@+
     * @tasktype 10 Initialization
     */
    /**
     * Initializes this theme
     * Upon initialization, the theme creates its template engine and instanciates the
     * plug-ins from the specified {@link $plugins} list. If the theme is a 2.0 theme, that does not
     * display redirection messages, the HTTP redirections system is disabled to ensure users will
     * see the redirection screen.
     *
     * @return bool
     */
    public function xoInit()
    {
        $xoops = Xoops::getInstance();
        $this->assets = $xoops->assets();
        $this->path = \XoopsBaseConfig::get('themes-path') . '/' . $this->folderName;
        $this->url = \XoopsBaseConfig::get('themes-url') . '/' . $this->folderName;
        $this->template = null;
        $this->template = new XoopsTpl();
        //$this->template->currentTheme = $this;
        $this->template->assignByRef('xoTheme', $this);
        $this->template->assign(array(
            'xoops_theme'      => $xoops->getConfig('theme_set'),
            'xoops_imageurl'   => \XoopsBaseConfig::get('themes-url') . '/' . $xoops->getConfig('theme_set') . '/',
            'xoops_themecss'   => $xoops->getCss($xoops->getConfig('theme_set')),
            'xoops_requesturi' => htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES),
            'xoops_sitename'   => htmlspecialchars($xoops->getConfig('sitename'), ENT_QUOTES),
            'xoops_slogan'     => htmlspecialchars($xoops->getConfig('slogan'), ENT_QUOTES),
            'xoops_dirname'    => $xoops->moduleDirname,
            'xoops_banner'     => $this->renderBanner ? $xoops->getBanner() : '&nbsp;',
            'xoops_pagetitle'  => $xoops->isModule() ? $xoops->module->getVar('name') : htmlspecialchars($xoops->getConfig('slogan'), ENT_QUOTES)
        ));
        $this->template->assign(array(
            'theme_path' => $this->path, 'theme_tpl' => $this->path . '/xotpl', 'theme_url' => $this->url,
            'theme_img'  => $this->url . '/img', 'theme_icons' => $this->url . '/icons',
            'theme_css'  => $this->url . '/css', 'theme_js' => $this->url . '/js',
            'theme_lang' => $this->url . '/language',
        ));

        if ($xoops->isUser()) {
            $response = $xoops->service("Avatar")->getAvatarUrl($xoops->user);
            $avatar = $response->getValue();
            $avatar = empty($avatar) ? '' : $avatar;

            $this->template->assign(array(
                'xoops_isuser'     => true,
                'xoops_avatar'     => $avatar,
                'xoops_userid'     => $xoops->user->getVar('uid'), 'xoops_uname' => $xoops->user->getVar('uname'),
                'xoops_name'       => $xoops->user->getVar('name'), 'xoops_isadmin' => $xoops->isAdmin(),
                'xoops_usergroups' => $xoops->user->getGroups()
            ));
        } else {
            $this->template->assign(array(
                'xoops_isuser' => false,
				'xoops_isadmin' => false,
				'xoops_usergroups' => array(XOOPS_GROUP_ANONYMOUS)
            ));
        }

        // Meta tags
        $metas = array(
            'description', 'keywords', 'robots', 'rating', 'author', 'copyright'
        );
        foreach ($metas as $name) {
            $this->addMeta('meta', $name, $xoops->getConfig('meta_' . $name));
        }

        // Other assigns
        $assigns = array(
            'title', 'slogan', 'locale', 'footer', 'jquery_theme', 'startpage'
        );
        foreach ($assigns as $name) {
            // prefix each tag with 'xoops_'
            $this->template->assign("xoops_$name", $xoops->getConfig($name));
        }

        // Load global javascript
        //$this->addScript('include/xoops.js');
        //$this->loadLocalization();
        list($cssAssets, $jsAssets) = $this->getLocalizationAssets();
        if (!empty($cssAssets)) {
            $this->addBaseStylesheetAssets($cssAssets);
        }
        $this->addBaseScriptAssets('include/xoops.js');
        $this->addBaseScriptAssets('@jquery');
        //$this->addBaseScriptAssets('media/bootstrap/js/bootstrap.min.js');
        if (!empty($jsAssets)) {
            $this->addBaseScriptAssets($jsAssets);
        }

        if ($this->bufferOutput) {
            ob_start();
        }
        $xoops->setTheme($this);
        $xoops->setTpl($this->template);

        //For legacy only, never use this Globals
        $GLOBALS['xoTheme'] = $xoops->theme();
        $GLOBALS['xoopsTpl'] = $xoops->tpl();

        //to control order of loading JS and CSS
        // TODO - this should be done in such a way it can join the base asset
        //        load above.
        if (XoopsLoad::fileExists($this->path . "/theme_onload.php")) {
            include_once($this->path . "/theme_onload.php");
        }

        // Instanciate and initialize all the theme plugins
        foreach ($this->plugins as $k => $bundleId) {
            if (!is_object($bundleId)) {
                /* @var $plugin XoopsThemePlugin */
                $plugin = new $bundleId();
                $plugin->theme = $this;
                $plugin->xoInit();

                $this->plugins[$bundleId] = null;
                $this->plugins[$bundleId] = $plugin;
                unset($this->plugins[$k]);
            }
        }
        return true;
    }

    /**
     * Generate cache id based on extra information of language and user groups
     * User groups other than anonymous should be detected to avoid disclosing group sensitive contents
     *
     * @param string $cache_id    raw cache id
     * @param string $extraString extra string
     *
     * @return string complete cache id
     */
    public function generateCacheId($cache_id, $extraString = '')
    {
        $xoops = Xoops::getInstance();
        static $extra_string;
        if (!$this->use_extra_cache_id) {
            return $cache_id;
        }

        if (empty($extraString)) {
            if (empty($extra_string)) {
                // Generate language section
                $extra_string = $xoops->getConfig('locale');
                // Generate group section
                if (!$xoops->isUser()) {
                    $extra_string .= '-' . FixedGroups::ANONYMOUS;
                } else {
                    $groups = $xoops->user->getGroups();
                    sort($groups);
                    // Generate group string for non-anonymous groups,
                    // XOOPS_DB_PASS and XOOPS_DB_NAME (before we find better variables) are used to protect group sensitive contents
                    $extra_string .= '-' . substr(md5(implode('-', $groups)), 0, 8) . '-' . substr(md5(\XoopsBaseConfig::get('db-pass') . \XoopsBaseConfig::get('db-name') . \XoopsBaseConfig::get('db-user')), 0, 8);
                }
            }
            $extraString = $extra_string;
        }
        $cache_id .= '-' . $extraString;
        return $cache_id;
    }

    /**
     * XoopsTheme::checkCache()
     *
     * @return bool
     */
    public function checkCache()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST' && $this->contentCacheLifetime) {
            $template = $this->contentTemplate ? $this->contentTemplate : 'module:system/system_dummy.tpl';
            $this->template->caching = 2;
            $this->template->cache_lifetime = $this->contentCacheLifetime;
            $uri = str_replace(\XoopsBaseConfig::get('url'), '', $_SERVER['REQUEST_URI']);
            // Clean uri by removing session id
            if (defined('SID') && SID && strpos($uri, SID)) {
                $uri = preg_replace("/([\?&])(" . SID . "$|" . SID . "&)/", "\\1", $uri);
            }
            $this->contentCacheId = $this->generateCacheId('page_' . substr(md5($uri), 0, 8));
            if ($this->template->isCached($template, $this->contentCacheId)) {
                Xoops::getInstance()->events()->triggerEvent('core.theme.checkcache.success', array($template, $this));
                $this->render(null, null, $template);
                return true;
            }
        }
        return false;
    }

    /**
     * Render the page
     * The theme engine builds pages from 2 templates: canvas and content.
     * A module can call this method directly and specify what templates the theme engine must use.
     * If render() hasn't been called before, the theme defaults will be used for the canvas and
     * page template (and xoopsOption['template_main'] for the content).
     *
     * @param string $canvasTpl  The canvas template, if different from the theme default
     * @param string $pageTpl    The page template, if different from the theme default (unsupported, 2.3+ only)
     * @param string $contentTpl The content template
     * @param array  $vars       Template variables to send to the template engine
     *
     * @return bool
     */
    public function render($canvasTpl = null, $pageTpl = null, $contentTpl = null, $vars = array())
    {
        if ($this->renderCount) {
            return false;
        }
        $xoops = Xoops::getInstance();
        $xoops->events()->triggerEvent('core.theme.render.start', array($this));
        $cache = $xoops->cache($this->headersCacheEngine);

        //Get meta information for cached pages
        if ($this->contentCacheLifetime && $this->contentCacheId && $content = $cache->read($this->contentCacheId)) {
            //we need to merge metas set by blocks with the module cached meta
            $this->htmlHeadStrings = array_merge($this->htmlHeadStrings, $content['htmlHeadStrings']);
            foreach ($content['metas'] as $type => $value) {
                $this->metas[$type] = array_merge($this->metas[$type], $content['metas'][$type]);
            }
            $xoops->setOption('xoops_pagetitle', $content['xoops_pagetitle']);
            $xoops->setOption('xoops_module_header', $content['header']);
        }

        if ($xoops->getOption('xoops_pagetitle')) {
            $this->template->assign('xoops_pagetitle', $xoops->getOption('xoops_pagetitle'));
        }
        $header = !$xoops->getOption('xoops_module_header') ? $this->template->getTemplateVars('xoops_module_header') : $xoops->getOption('xoops_module_header');

        //save meta information of cached pages
        if ($this->contentCacheLifetime && $this->contentCacheId && !$contentTpl) {
            $content['htmlHeadStrings'] = (array)$this->htmlHeadStrings;
            $content['metas'] = (array)$this->metas;
            $content['xoops_pagetitle'] = $this->template->getTemplateVars('xoops_pagetitle');
            $content['header'] = $header;
            $cache->write($this->contentCacheId, $content);
        }

        //  @internal : Lame fix to ensure the metas specified in the xoops config page don't appear twice
        $old = array('robots', 'keywords', 'description', 'rating', 'author', 'copyright');
        foreach ($this->metas['meta'] as $name => $value) {
            if (in_array($name, $old)) {
                $this->template->assign("xoops_meta_$name", htmlspecialchars($value, ENT_QUOTES));
                unset($this->metas['meta'][$name]);
            }
        }

        // We assume no overlap between $GLOBALS['xoopsOption']['xoops_module_header'] and $this->template->getTemplateVars( 'xoops_module_header' ) ?
        $this->template->assign('xoops_module_header', $this->renderMetas(true) . "\n" . $header);

        if ($canvasTpl) {
            $this->canvasTemplate = $canvasTpl;
        }
        if ($contentTpl) {
            $this->contentTemplate = $contentTpl;
        }
        if (!empty($vars)) {
            $this->template->assign($vars);
        }
        if ($this->contentTemplate) {
            $this->content = $this->template->fetch($this->contentTemplate, $this->contentCacheId);
        }
        if ($this->bufferOutput) {
            $this->content .= ob_get_contents();
            ob_end_clean();
        }

        $this->template->assignByRef('xoops_contents', $this->content);

        // Do not cache the main (theme.html) template output
        $this->template->caching = 0;
        $this->template->display($this->path . '/' . $this->canvasTemplate);
        $this->renderCount++;
        $xoops->events()->triggerEvent('core.theme.render.end', array($this));
        return true;
    }

    /**
     * Load localization information
     * Folder structure for localization:
     * themes/themefolder/english
     *    - main.php - language definitions
     *    - style.css - localization stylesheet
     *    - script.js - localization script
     *
     * @param string $type language domain (unused?)
     *
     * @return array list of 2 arrays, one
     */
    public function getLocalizationAssets($type = "main")
    {
        $cssAssets = array();
        $jsAssets = array();

        $xoops = Xoops::getInstance();

        Xoops_Locale::loadThemeLocale($this);

        $language = XoopsLocale::getLocale();
        // Load global localization stylesheet if available
        if (XoopsLoad::fileExists($xoops->path('locale/' . $language . '/style.css'))) {
            $cssAssets[] = $xoops->path('locale/' . $language . '/style.css');
        }
        //$this->addLanguage($type);
        // Load theme localization stylesheet and scripts if available
        if (XoopsLoad::fileExists($this->path . '/locale/' . $language . '/script.js')) {
            $jsAssets[] = $this->url . '/locale/' . $language . '/script.js';
        }
        if (XoopsLoad::fileExists($this->path . '/locale/' . $language . '/style.css')) {
            $cssAssets[] = $this->path . '/locale/' . $language . '/style.css';
        }
        return array($cssAssets, $jsAssets);
    }

    /**
     * Load theme specific language constants
     *
     * @param string $type     language type, like 'main', 'admin'; Needs to be declared in theme xo-info.php
     * @param string $language specific language
     *
     * @return bool|mixed
     */
    /*
    public function addLanguage($type = "main", $language = null)
    {
        $xoops = Xoops::getInstance();
        $language = is_null($language) ? $xoops->getConfig('locale') : $language;
        if (!XoopsLoad::fileExists($file = $xoops->path($this->resourcePath("/locale/{$language}/{$type}.php")))) {
            if (!XoopsLoad::fileExists($file = $xoops->path($this->resourcePath("/locale/en_US/{$type}.php")))) {
                return false;
            }
        }
        $ret = include_once $file;
        return $ret;
    }*/

    /**
     * *#@+
     * @tasktype 20 Manipulating page meta-information
     */
    /**
     * Adds script code to the document head
     * This methods allows the insertion of an external script file (if $src is provided), or
     * of a script snippet. The file URI is parsed to take benefit of the theme resource
     * overloading system.
     * The $attributes parameter allows you to specify the attributes that will be added to the
     * inserted <script> tag. If unspecified, the <var>type</var> attribute value will default to
     * 'text/javascript'.
     * <code>
     * // Add an external script using a physical path
     * $theme->addScript( 'www/script.js', null, '' );
     * $theme->addScript( 'modules/newbb/script.js', null, '' );
     * // Specify attributes for the <script> tag
     * $theme->addScript( 'mod_xoops_SiteManager#common.js', array( 'type' => 'application/x-javascript' ), '' );
     * // Insert a code snippet
     * $theme->addScript( null, array( 'type' => 'application/x-javascript' ), 'window.open("Hello world");' );
     * </code>
     *
     * @param string $src        path to an external script file
     * @param array  $attributes hash of attributes to add to the <script> tag
     * @param string $content    Code snippet to output within the <script> tag
     *
     * @return void
     */
    public function addScript($src = '', $attributes = array(), $content = '')
    {
        $xoops = Xoops::getInstance();
        if (empty($attributes)) {
            $attributes = array();
        }
        if (!empty($src)) {
            $src = $xoops->url($this->resourcePath($src));
            $attributes['src'] = $src;
        }
        if (!empty($content)) {
            $attributes['_'] = $content;
        }
        if (!isset($attributes['type'])) {
            $attributes['type'] = 'text/javascript';
        }
        $this->addMeta('script', $src, $attributes);
    }

    /**
     * Add StyleSheet or CSS code to the document head
     *
     * @param string|null $src        path to .css file
     * @param array|null  $attributes name => value paired array of attributes such as title
     * @param string      $content    CSS code to output between the <style> tags (in case $src is empty)
     *
     * @return void
     */
    public function addStylesheet($src = '', $attributes = array(), $content = '')
    {
        $xoops = Xoops::getInstance();
        if (empty($attributes)) {
            $attributes = array();
        }
        if (!empty($src)) {
            $src = $xoops->url($this->resourcePath($src));
            $attributes['href'] = $src;
        }
        if (!isset($attributes['type'])) {
            $attributes['type'] = 'text/css';
        }
        if (!empty($content)) {
            $attributes['_'] = $content;
        }
        $this->addMeta('stylesheet', $src, $attributes);
    }

    /**
     * addScriptAssets - add a list of scripts to the page
     *
     * @param array  $assets  list of source files to process
     * @param string $filters comma separated list of filters
     * @param string $target  target path, will default to assets directory
     *
     * @return void
     */
    public function addScriptAssets($assets, $filters = 'default', $target = null)
    {
        $url = $this->assets->getUrlToAssets('js', $assets, $filters, $target);
        $this->addScript($url);
    }

    /**
     * addStylesheetAssets - add a list of stylesheets to the page
     *
     * @param string[]  $assets  list of source files to process
     * @param string $filters comma separated list of filters
     * @param string $target  target path, will default to assets directory
     *
     * @return void
     */
    public function addStylesheetAssets($assets, $filters = 'default', $target = null)
    {
        $url = $this->assets->getUrlToAssets('css', $assets, $filters, $target);
        $this->addStylesheet($url);
    }

    /**
     * addBaseAssets - add a list of assets to the page, these will all
     * be combined into a single asset file at render time
     *
     * @param string $type   type of asset, i.e. 'css' or 'js'
     * @param array  $assets list of source files to process
     *
     * @return void
     */
    public function addBaseAssets($type, $assets)
    {
        if (is_scalar($assets)) {
            $this->baseAssets[$type][]=$assets;
        } elseif (is_array($assets)) {
            $this->baseAssets[$type] = array_merge($this->baseAssets[$type], $assets);
        }
    }

    /**
     * addBaseScriptAssets - add a list of scripts to the page
     *
     * @param array $assets list of source files to process
     *
     * @return void
     */
    public function addBaseScriptAssets($assets)
    {
        $this->addBaseAssets('js', $assets);
    }

    /**
     * addBaseStylesheetAssets - add a list of stylesheets to the page
     *
     * @param array $assets list of source files to process
     *
     * @return void
     */
    public function addBaseStylesheetAssets($assets)
    {
        $this->addBaseAssets('css', $assets);
    }

    /**
     * setNamedAsset - Add an asset reference to the asset manager.
     * The specifed assest will be added to the asset manager with the specified
     * name. As an example:
     *
     *   $theme->setNamedAsset('aacss','module/aa/assets/css/*.css');
     *
     * This will create an asset reference which can be added using other asset
     * functions, such as:
     *
     *   $theme->addBaseStylesheetAssets('@aacss');
     *
     * Additional custom filters can be specified for the named assest if needed.
     *
     * @param string $name    the name of the reference to be added
     * @param mixed  $assets  a string asset path, or an array of asset paths, may include wildcard
     * @param string $filters comma separated list of filters
     *
     * @return boolean true if asset registers, false on error
     */
    public function setNamedAsset($name, $assets, $filters = null)
    {
        return $this->assets->registerAssetReference($name, $assets, $filters);
    }

    /**
     * Add a <link> to the header
     *
     * @param string $rel        Relationship from the current doc to the anchored one
     * @param string $href       URI of the anchored document
     * @param array  $attributes Additional attributes to add to the <link> element
     *
     * @return void
     */
    public function addLink($rel, $href = '', $attributes = array())
    {
        if (empty($attributes)) {
            $attributes = array();
        }
        if (!empty($href)) {
            $attributes['href'] = $href;
        }
        $attributes['rel'] = $rel;
        $this->addMeta('link', '', $attributes);
    }

    /**
     * Set a meta http-equiv value
     *
     * @param string $name  meta tag name
     * @param null   $value meta tag value
     *
     * @return string|false
     */
    public function addHttpMeta($name, $value = null)
    {
        if (isset($value)) {
            return $this->addMeta('http', $name, $value);
        }
        unset($this->metas['http'][$name]);
        return false;
    }

    /**
     * Change output page meta-information
     *
     * @param string $type
     * @param string $name
     * @param string $value
     *
     * @return string
     */
    public function addMeta($type = 'meta', $name = '', $value = '')
    {
        if (!isset($this->metas[$type])) {
            $this->metas[$type] = array();
        }
        if (!empty($name)) {
            $this->metas[$type][$name] = $value;
        } else {
            $this->metas[$type][md5(serialize(array($value)))] = $value;
        }
        return $value;
    }

    /**
     * XoopsTheme::headContent()
     *
     * @param $params
     * @param string $content
     * @param $smarty
     * @param $repeat
     *
     * @return void
     */
    public function headContent($params, $content, &$smarty, &$repeat)
    {
        if (!$repeat) {
            $this->htmlHeadStrings[] = $content;
        }
    }

    /**
     * XoopsTheme::renderMetas()
     *
     * @param bool $return true to return as string, false to echo
     *
     * @return bool|string
     */
    public function renderMetas($return = false)
    {
        $str = '';

        if (!empty($this->baseAssets['js'])) {
            $url = $this->assets->getUrlToAssets('js', $this->baseAssets['js']);
            if (!empty($url)) {
                $str .= '<script src="' . $url . '" type="text/javascript"></script>'."\n";
            }
        }

        if (!empty($this->baseAssets['css'])) {
            $url = $this->assets->getUrlToAssets('css', $this->baseAssets['css']);
            if (!empty($url)) {
                $str .= '<link rel="stylesheet" href="' . $url . '" type="text/css" />'."\n";
            }
        }

        foreach (array_keys($this->metas) as $type) {
            $str .= $this->renderMetasByType($type);
        }
        $str .= implode("\n", $this->htmlHeadStrings);

        if ($return) {
            return $str;
        }
        echo $str;
        return true;
    }

    /**
     * XoopsTheme::renderMetasByType() render the specified metadata type
     *
     * @param string $type type to render
     *
     * @return string
     */
    public function renderMetasByType($type)
    {
        if (!isset($type)) {
            return '';
        }

        $str = '';
        switch ($type) {
            case 'script':
                foreach ($this->metas[$type] as $attrs) {
                    $str .= "<script" . $this->renderAttributes($attrs) . ">";
                    if (@$attrs['_']) {
                        $str .= "\n//<![CDATA[\n" . $attrs['_'] . "\n//]]>";
                    }
                    $str .= "</script>\n";
                }
                break;
            case 'link':
                foreach ($this->metas[$type] as $attrs) {
                    $rel = $attrs['rel'];
                    unset($attrs['rel']);
                    $str .= '<link rel="' . $rel . '"' . $this->renderAttributes($attrs) . " />\n";
                }
                break;
            case 'stylesheet':
                foreach ($this->metas[$type] as $attrs) {
                    if (@$attrs['_']) {
                        $str .= '<style' . $this->renderAttributes($attrs)
                            . ">\n/* <![CDATA[ */\n" . $attrs['_'] . "\n/* //]]> */\n</style>";
                    } else {
                        $str .= '<link rel="stylesheet"' . $this->renderAttributes($attrs) . " />\n";
                    }
                }
                break;
            case 'http':
                foreach ($this->metas[$type] as $name => $content) {
                    $str .= '<meta http-equiv="' . htmlspecialchars($name, ENT_QUOTES) . '" content="' . htmlspecialchars($content, ENT_QUOTES) . "\" />\n";
                }
                break;
            default:
                foreach ($this->metas[$type] as $name => $content) {
                    $str .= '<meta name="' . htmlspecialchars($name, ENT_QUOTES) . '" content="' . htmlspecialchars($content, ENT_QUOTES) . "\" />\n";
                }
                break;
        }

        return $str;
    }

    /**
     * Generates a unique element ID
     *
     * @param string $tagName
     *
     * @return string
     */
    public function genElementId($tagName = 'xos')
    {
        static $cache = array();
        if (!isset($cache[$tagName])) {
            $cache[$tagName] = 1;
        }
        return $tagName . '-' . $cache[$tagName]++;
    }

    /**
     * Transform an attributes collection to an XML string
     *
     * @param array $coll
     *
     * @return string
     */
    public function renderAttributes($coll)
    {
        $str = '';
        foreach ($coll as $name => $val) {
            if ($name != '_') {
                $str .= ' ' . $name . '="' . htmlspecialchars($val, ENT_QUOTES) . '"';
            }
        }
        return $str;
    }

    /**
     * Return a themable file resource path
     *
     * @param string $path
     *
     * @return string
     */
    public function resourcePath($path)
    {
        if (substr($path, 0, 1) == '/') {
            $path = substr($path, 1);
        }
		$xoops_root_path = \XoopsBaseConfig::get('root-path');
//\Xoops::getInstance()->events()->triggerEvent('debug.log', $this);
        if (XoopsLoad::fileExists($xoops_root_path . "/{$this->themesPath}/{$this->folderName}/{$path}")) {
//\Xoops::getInstance()->events()->triggerEvent('debug.log', "custom theme path {$this->themesPath}/{$this->folderName}/{$path}");
            return "{$this->themesPath}/{$this->folderName}/{$path}";
        }

        if (XoopsLoad::fileExists($xoops_root_path . "/themes/{$this->folderName}/{$path}")) {
//\Xoops::getInstance()->events()->triggerEvent('debug.log', "main theme folder themes/{$this->folderName}/{$path}");
            return "themes/{$this->folderName}/{$path}";
        }
//\Xoops::getInstance()->events()->triggerEvent('debug.log', "drop thru {$path}");
        return $path;
    }
}

abstract class XoopsThemePlugin
{
    /**
     * @var XoopsTheme
     */
    public $theme = false;

    abstract function xoInit();
}
