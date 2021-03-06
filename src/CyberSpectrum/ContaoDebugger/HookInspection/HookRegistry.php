<?php
/**
 * Contao Debugger
 *
 * Copyright (c) 2014 Christian Schiffler
 *
 * @package     ContaoDebugger
 * @author      Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright   2014 CyberSpectrum
 * @license     LGPL-3+
 * @filesource
 */

namespace CyberSpectrum\ContaoDebugger\HookInspection;

use CyberSpectrum\ContaoDebugger\DebugBar\DataCollector\HookInspectionCollector;
use DebugBar\DataCollector\TimeDataCollector;

/**
 * Registry for HOOKs.
 *
 * @package CyberSpectrum\ContaoDebugger\HookInspection
 */
class HookRegistry
{
    /**
     * The collector to use.
     *
     * @var HookInspectionCollector
     */
    protected static $collector;

    /**
     * The time collector.
     *
     * @var TimeDataCollector
     */
    protected static $timeCollector;

    /**
     * Retrieve all valid hook names.
     *
     * @param bool $includeRegistered Flag determining if the registered hooks shall be also examined.
     *
     * @return string[]
     */
    public static function getHookNames($includeRegistered = false)
    {
        return
            array_unique(array_merge(
                $includeRegistered ? array_keys($GLOBALS['TL_HOOKS']) : array(),
                self::$hookMap['void'],
                array_keys(self::$hookMap['arg']),
                array_keys(self::$hookMap['value'])
            ));
    }

    /**
     * Attach to the collector and HOOKs.
     *
     * @param HookInspectionCollector $collector     The collector.
     *
     * @param TimeDataCollector       $timeCollector The time collector.
     *
     * @return void
     */
    public static function attach($collector, TimeDataCollector $timeCollector = null)
    {
        self::$collector     = $collector;
        self::$timeCollector = $timeCollector;

        $hooks = self::getHookNames();

        foreach ($hooks as $k) {
            if (in_array($k, array('outputBackendTemplate', 'outputFrontendTemplate'))) {
                continue;
            }

            if (!array_key_exists($k, $GLOBALS['TL_HOOKS'])) {
                $GLOBALS['TL_HOOKS'][$k] = array();
            }

            array_insert($GLOBALS['TL_HOOKS'][$k], 0, array(array(__CLASS__, 'in_' . $k)));
        }

        array_insert($GLOBALS['TL_HOOKS']['initializeSystem'], 0, array(array(__CLASS__, 'initializeSystem')));
    }

    /**
     * Late add the post execution method.
     *
     * @return void
     */
    public static function initializeSystem()
    {
        $hooks = self::getHookNames();

        foreach ($hooks as $k) {
            if (in_array($k, array('outputBackendTemplate', 'outputFrontendTemplate'))) {
                continue;
            }

            $GLOBALS['TL_HOOKS'][$k][] = array(__CLASS__, 'out_' . $k);
        }

        foreach (array_diff(self::getHookNames(true), $hooks) as $hook) {
            trigger_error(
                'UNKNOWN HOOK ' . $hook . ' detected. Inspection is not active for this one.',
                E_USER_WARNING
            );
        }
    }

    /**
     * Log a message to the collector.
     *
     * @param mixed  $message  The message.
     *
     * @param string $hookName The debug level.
     *
     * @return void
     */
    protected static function log($message, $hookName)
    {
        self::$collector->addHook($hookName, $message);
    }

    /**
     * Map of all hooks with their return values.
     *
     * @var array
     */
    protected static $hookMap = array(
        'void'                       => array
        (
            'activateAccount',
            'activateRecipient',
            'closeAccount',
            'createNewUser',
            'dispatchAjax',
            'executePostActions',
            'executePreActions',
            'generatePage',
            'generateXmlFiles',
            'getArticle',
            'initializeSystem',
            'loadDataContainer',
            'loadLanguageFile',
            'postDownload',
            'postLogin',
            'postLogout',
            'postUpload',
            // Unsure about this one here as we are not exiting as we can not as we do not create a pdf.
            // we have to check if Contao will call the other hooks after this one "fails".
            'printArticleAsPdf',
            'removeRecipient',
            'reviseTable',

            // 'initializeDependencyContainer',
        ),
        'arg'                        => array
        (
            'generateFrontendUrl'    => 2,
            'getAllEvents'           => 0,
            'getContentElement'      => 1,
            'getFrontendModule'      => 1,
            'getPageIdFromUrl'       => 0,
            'getSearchablePages'     => 0,
            'outputBackendTemplate'  => 0,
            'outputFrontendTemplate' => 0,
            'parseBackendTemplate'   => 0,
            'parseFrontendTemplate'  => 0,
            'parseTemplate'          => 0,
            'sqlCompileCommands'     => 0,
            'validateFormField'      => 0,
        ),
        'value'                      => array
        (
            'addCustomRegexp'        => false,
            'addLogEntry'            => false,
            'checkCredentials'       => false,
            'getSystemMessages'      => '',
            'importUser'             => false,
            'listComments'           => '',
            'loadFormField'          => array(),
            'removeOldFeeds'         => array(),
            'replaceInsertTags'      => false,
            'setNewPassword'         => false,
        )
    );

    /**
     * Lookup map for which parameters are whitelisted.
     *
     * @var array
     */
    protected static $hookParamMap = array
    (
        'activateAccount'           => array(),
        'activateRecipient'         => array(),
        'closeAccount'              => array(),
        'createNewUser'             => array(),
        'dispatchAjax'              => array(),
        'executePostActions'        => array(),
        'executePreActions'         => array(),
        'generatePage'              => array(),
        'generateXmlFiles'          => array(),
        'getArticle'                => array(),
        'initializeSystem'          => array(),
        'loadDataContainer'         => array(0),
        'loadLanguageFile'          => array(0, 1, 2),
        'postDownload'              => array(),
        'postLogin'                 => array(),
        'postLogout'                => array(),
        'postUpload'                => array(),
        'printArticleAsPdf'         => array(),
        'removeRecipient'           => array(),
        'reviseTable'               => array(),

        'generateFrontendUrl'       => array(),
        'getAllEvents'              => array(),
        'getContentElement'         => array(),
        'getFrontendModule'         => array(),
        'getPageIdFromUrl'          => array(),
        'getSearchablePages'        => array(),
        'outputBackendTemplate'     => array(),
        'outputFrontendTemplate'    => array(),
        'parseBackendTemplate'      => array(1),
        'parseFrontendTemplate'     => array(1),
        'parseTemplate'             => array(),
        'sqlCompileCommands'        => array(),
        'validateFormField'         => array(),

        'addCustomRegexp'           => array(),
        'addLogEntry'               => array(),
        'checkCredentials'          => array(0, 1),
        'getSystemMessages'         => array(),
        'importUser'                => array(),
        'listComments'              => array(),
        'loadFormField'             => array(),
        'removeOldFeeds'            => array(),
        'replaceInsertTags'         => array(),
        'setNewPassword'            => array(),

        'initializeDependencyContainer' => array(),
    );

    /**
     * Handle unknown hooks in here.
     *
     * @param string $method The method name.
     *
     * @param array  $params The parameters.
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException When the hook is unknown.
     */
    public function __call($method, $params)
    {
        $starting = true;
        if (substr($method, 0, 3) == 'in_') {
            $method = substr($method, 3);
        } elseif (substr($method, 0, 4) == 'out_') {
            $method   = substr($method, 4);
            $starting = false;
        }

        if (in_array($method, self::$hookMap['void'])) {
            $this->processHook($method, $params, $starting);

            return null;
        } elseif (isset(self::$hookMap['arg'][$method])) {
            $this->processHook($method, $params, $starting);

            return $params[self::$hookMap['arg'][$method]];
        } elseif (isset(self::$hookMap['value'][$method])) {
            $this->processHook($method, $params, $starting);

            return self::$hookMap['value'][$method];
        }

        throw new \InvalidArgumentException('Debugger error: UNKNOWN HOOK called: '.$method);
    }

    /**
     * Shorten a list of HOOKs.
     *
     * @param array $list The list of hooks to be called.
     *
     * @return string[]
     */
    protected function createHookList($list)
    {
        $result = array();

        foreach ($list as $hook) {
            if ($hook[0] == __CLASS__
                || $hook[0] == 'CyberSpectrum\ContaoDebugger\Debugger'
            ) {
                continue;
            }
            $result[] = $hook[0] . '::' . $hook[1];
        }

        return $result;
    }

    /**
     * Mangle the parameters so that only the allowed are preserved.
     *
     * @param string $hookName The name of the hook.
     *
     * @param array  $params   The parameters.
     *
     * @return array
     */
    protected function prepareParams($hookName, $params)
    {
        $allowed = self::$hookParamMap[$hookName];
        $result  = array();
        foreach ($params as $index => $value) {
            if (in_array($index, $allowed)) {
                $result[] = $value;
            } else {
                if (is_object($value)) {
                    $result[] = get_class($value);
                } else {
                    if (is_string($value)) {
                        $result[] = gettype($value) . ' ' . strlen($value);
                    } elseif (is_array($value)) {
                        $result[] = gettype($value) . ' ' . count($value);
                    } else {
                        $result[] = gettype($value);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Generic hook logging facility.
     *
     * @param string $hookName The hook name.
     *
     * @param array  $params   The parameters.
     *
     * @param bool   $starting Flag determining if we are entering the hook or exiting.
     *
     * @return void
     */
    protected function processHook($hookName, $params, $starting)
    {
        if (!$starting) {
            if (self::$timeCollector->hasStartedMeasure($hookName)) {
                self::$timeCollector->stopMeasure($hookName);
            }
            return;
        }

        if (self::$timeCollector && $hookName !== 'initializeSystem') {
            if (!self::$timeCollector->hasStartedMeasure($hookName)) {
                self::$timeCollector->startMeasure($hookName, $hookName);
            }
        }

        $exception = new \ErrorException();
        $stack     = $exception->getTrace();

        $information = array(
            'caller' => str_replace(TL_ROOT, 'TL_ROOT', $stack[1]['file']) . '#' . $stack[1]['line'],
            '$GLOBALS[\'TL_HOOKS\'][\'' . $hookName . '\']' => $this->createHookList($GLOBALS['TL_HOOKS'][$hookName]),
            'parameters'  => $this->prepareParams($hookName, $params)
        );

        $this->log($information, $hookName);
    }
}
