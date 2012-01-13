<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Usefull classes for package local_moodlecheck
 *
 * @package    local_moodlecheck
 * @copyright  2012 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot. '/local/moodlecheck/file.php');

/**
 * Handles one rule
 * 
 * @package    local_moodlecheck
 * @copyright  2012 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_moodlecheck_rule {
    protected $code;
    protected $callback;
    protected $rulestring;
    protected $errorstring;
    
    public function __construct($code) {
        $this->code = $code;
    }
    
    public function set_callback($callback) {
        $this->callback = $callback;
        return $this;
    }
    
    public function set_rulestring($rulestring) {
        $this->rulestring = $rulestring;
        return $this;
    }
    
    public function set_errorstring($errorstring) {
        $this->errorstring = $errorstring;
        return $this;
    }
    
    public function get_name() {
        if ($this->rulestring !== null && get_string_manager()->string_exists($this->rulestring, 'local_moodlecheck')) {
            return get_string($this->rulestring, 'local_moodlecheck');
        } else if (get_string_manager()->string_exists('rule_'. $this->code, 'local_moodlecheck')) {
            return get_string('rule_'. $this->code, 'local_moodlecheck');
        } else {
            return $this->code;
        }
    }
    
    public function get_error($args) {
        if (strlen($this->errorstring) && get_string_manager()->string_exists($this->errorstring, 'local_moodlecheck')) {
            return get_string($this->errorstring, 'local_moodlecheck', $args);
        } else if (get_string_manager()->string_exists('error_'. $this->code, 'local_moodlecheck')) {
            return get_string('error_'. $this->code, 'local_moodlecheck', $args);
        } else {
            if (is_array($args)) {
                $args = ': '. print_r($args, true);
            } else if ($args !== true && $args !== null) {
                $args = ': '. $args;
            } else {
                $args = '';
            }
            return $this->get_name(). '. Error'. $args;
        }
    }
    
    public function validatefile(local_moodlecheck_file $file) {
        $callback = $this->callback;
        $reterrors = $callback($file);
        $ruleerrors = array();
        foreach ($reterrors as $args) {
            $ruleerrors[] = $this->get_error($args);
        }
        return $ruleerrors;
    }
}

/**
 * Rule registry
 * 
 * @package    local_moodlecheck
 * @copyright  2012 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_moodlecheck_registry {
    protected static $rules = array();
    protected static $enabledrules = array();
    
    public static function add_rule($code) {
        $rule = new local_moodlecheck_rule($code);
        self::$rules[$code] = $rule;
        return $rule;
    }
    
    public static function get_registered_rules() {
        return self::$rules;
    }
    
    public static function enable_rule($code, $enable = true) {
        self::$enabledrules[$code] = $enable;
    }
    
    public static function get_enabled_rules() {
        // TODO cache?
        $rules = array();
        foreach (array_keys(self::$rules) as $code) {
            if (isset(self::$enabledrules[$code])) {
                $rules[$code] = self::$rules[$code];
            }
        }
        return $rules;
    }
    
    public static function enable_all_rules() {
        foreach (array_keys(self::$rules) as $code) {
            self::enable_rule($code);
        }
    }
}

/**
 * Handles one path being validated (file or directory)
 * 
 * @package    local_moodlecheck
 * @copyright  2012 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_moodlecheck_path {
    protected $path = null;
    protected $file = null;
    protected $subpaths = null;
    protected $validated = false;
    
    public function __construct($path) {
        $this->path = $path;
    }
    
    public function get_fullpath() {
        global $CFG;
        return $CFG->dirroot. '/'. $this->path;
    }
    
    public function validate() {
        if ($this->validated) {
            // prevent from second validation
            return;
        }
        if (is_file($this->get_fullpath())) {
            $this->file = new local_moodlecheck_file($this->get_fullpath());
        } else if (is_dir($this->get_fullpath())) {
            $this->subpaths = array();
            if ($dh = opendir($this->get_fullpath())) {
                while (($file = readdir($dh)) !== false) {
                    if ($file != '.' && $file != '..') {
                        $subpath = new local_moodlecheck_path($this->path . '/'. $file);
                        $this->subpaths[] = $subpath;
                    }
                }
                closedir($dh);
            }
        }
        $this->validated = true;
    }
    
    public function is_file() {
        return $this->file !== null;
    }
    
    public function is_dir() {
        return $this->subpaths !== null;
    }
    
    public function get_path() {
        return $this->path;
    }
    
    public function get_file() {
        return $this->file;
    }
    
    public function get_subpaths() {
        return $this->subpaths;
    }
}

/**
 * Form for check options
 * 
 * @package    local_moodlecheck
 * @copyright  2012 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_moodlecheck_form extends moodleform {
    protected function definition() {
        global $path;
        $mform = $this->_form;

        $mform->addElement('header', '');

        $mform->addElement('textarea', 'path', get_string('path', 'local_moodlecheck'), array('rows' => 8, 'cols' => 120));
        $mform->addHelpButton('path', 'path', 'local_moodlecheck');
        
        $mform->addElement('radio', 'checkall', '', get_string('checkallrules', 'local_moodlecheck'), 'all');
        $mform->addElement('radio', 'checkall', '', get_string('checkselectedrules', 'local_moodlecheck'), 'selected');
        $mform->setDefault('checkall', 'all');
        
        foreach (local_moodlecheck_registry::get_registered_rules() as $code => $rule) {
            $mform->addElement('checkbox', "rule[$code]", '', $rule->get_name());
            $mform->setDefault("rule[$code]", 1);
            $mform->setAdvanced("rule[$code]");
        }

        $mform->addElement('submit', 'submitbutton', get_string('check', 'local_moodlecheck'));
    }
}
