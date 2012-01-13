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
 * Registering rules for phpdocs checking
 *
 * @package    local_moodlecheck
 * @copyright  2012 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

local_moodlecheck_registry::add_rule('filephpdocpresent')->set_callback('local_moodlecheck_filephpdocpresent');
local_moodlecheck_registry::add_rule('classesdocumented')->set_callback('local_moodlecheck_classesdocumented');
local_moodlecheck_registry::add_rule('functionsdocumented')->set_callback('local_moodlecheck_functionsdocumented');
local_moodlecheck_registry::add_rule('variablesdocumented')->set_callback('local_moodlecheck_variablesdocumented');
local_moodlecheck_registry::add_rule('constsdocumented')->set_callback('local_moodlecheck_constsdocumented');
local_moodlecheck_registry::add_rule('definesdocumented')->set_callback('local_moodlecheck_definesdocumented');
local_moodlecheck_registry::add_rule('noinlinephpdocs')->set_callback('local_moodlecheck_noinlinephpdocs');
local_moodlecheck_registry::add_rule('phpdocsfistline')->set_callback('local_moodlecheck_phpdocsfistline');
local_moodlecheck_registry::add_rule('functionarguments')->set_callback('local_moodlecheck_functionarguments');
local_moodlecheck_registry::add_rule('variableshasvar')->set_callback('local_moodlecheck_variableshasvar');
local_moodlecheck_registry::add_rule('definedoccorrect')->set_callback('local_moodlecheck_definedoccorrect');
local_moodlecheck_registry::add_rule('filehascopyright')->set_callback('local_moodlecheck_filehascopyright');
local_moodlecheck_registry::add_rule('classeshavecopyright')->set_callback('local_moodlecheck_classeshavecopyright');

/**
 * Checks if file-level phpdocs block is present
 *
 * @param local_moodlecheck_file $file
 * @return array of found errors
 */
function local_moodlecheck_filephpdocpresent(local_moodlecheck_file $file) {
    if ($file->find_file_phpdocs() === false) {
        return array(true);
    }
    return array();
}

/**
 * Checks if all classes have phpdocs blocks
 *
 * @param local_moodlecheck_file $file
 * @return array of found errors
 */
function local_moodlecheck_classesdocumented(local_moodlecheck_file $file) {
    $errors = array();
    $classes = $file->get_classes();
    foreach ($classes as $class) {
        if ($class->phpdocs === false) {
            $errors[] = array('class' => $class->name, 'line' => $file->get_line_number($class->boundaries[0]));
        }
    }
    return $errors;
}

/**
 * Checks if all functions have phpdocs blocks
 *
 * @param local_moodlecheck_file $file
 * @return array of found errors
 */
function local_moodlecheck_functionsdocumented(local_moodlecheck_file $file) {
    $errors = array();
    $functions = $file->get_functions();
    foreach ($functions as $function) {
        if ($function->phpdocs === false) {
            $errors[] = array('function' => $function->fullname, 'line' => $file->get_line_number($function->boundaries[0]));
        }
    }
    return $errors;
}

/**
 * Checks if all variables have phpdocs blocks
 *
 * @param local_moodlecheck_file $file
 * @return array of found errors
 */
function local_moodlecheck_variablesdocumented(local_moodlecheck_file $file) {
    $errors = array();
    $variables = $file->get_variables();
    foreach ($variables as $variable) {
        if ($variable->phpdocs === false) {
            $errors[] = array('variable' => $variable->fullname, 'line' => $file->get_line_number($variable->tid));
        }
    }
    return $errors;
}

/**
 * Checks if all constants have phpdocs blocks
 *
 * @param local_moodlecheck_file $file
 * @return array of found errors
 */
function local_moodlecheck_constsdocumented(local_moodlecheck_file $file) {
    $errors = array();
    $objects = $file->get_constants();
    foreach ($objects as $object) {
        if ($object->phpdocs === false) {
            $errors[] = array('object' => $object->fullname, 'line' => $file->get_line_number($object->tid));
        }
    }
    return $errors;
}

/**
 * Checks if all variables have phpdocs blocks
 *
 * @param local_moodlecheck_file $file
 * @return array of found errors
 */
function local_moodlecheck_definesdocumented(local_moodlecheck_file $file) {
    $errors = array();
    $objects = $file->get_defines();
    foreach ($objects as $object) {
        if ($object->phpdocs === false) {
            $errors[] = array('object' => $object->fullname, 'line' => $file->get_line_number($object->tid));
        }
    }
    return $errors;
}

/**
 * Checks that no comment starts with three or more slashes
 *
 * @param local_moodlecheck_file $file
 * @return array of found errors
 */
function local_moodlecheck_noinlinephpdocs(local_moodlecheck_file $file) {
    $errors = array();
    foreach ($file->get_phpdocs() as $phpdocs) {
        if ($phpdocs->is_inline()) {
            $errors[] = array('line' => $phpdocs->get_line_number($file));
        }
    }
    return $errors;
}

/**
 * Makes sure that file-level phpdocs and all classes have one-line short description
 *
 * @param local_moodlecheck_file $file
 * @return array of found errors
 */
function local_moodlecheck_phpdocsfistline(local_moodlecheck_file $file) {
    $errors = array();
    
    if (($phpdocs = $file->find_file_phpdocs()) && !$file->find_file_phpdocs()->get_shortdescription()) {
        $errors[] = array(
            'line' => $phpdocs->get_line_number($file),
            'object' => 'file'
        );
    }
    foreach ($file->get_classes() as $class) {
        if ($class->phpdocs && !$class->phpdocs->get_shortdescription()) {
            $errors[] = array(
                'line' => $class->phpdocs->get_line_number($file), 
                'object' => 'class '.$class->name
            );
        }
    }
    return $errors;
}

/**
 * Checks that all functions have proper arguments in phpdocs
 *
 * @param local_moodlecheck_file $file 
 * @return array of found errors
 */
function local_moodlecheck_functionarguments(local_moodlecheck_file $file) {
    $errors = array();
    foreach ($file->get_functions() as $function) {
        if ($function->phpdocs !== false) {
            $documentedarguments = $function->phpdocs->get_params();
            $match = (count($documentedarguments) == count($function->arguments));
            for ($i=0; $match && $i<count($documentedarguments); $i++) {
                if (count($documentedarguments[$i]) < 2) {
                    // must be at least type and parameter name
                    $match = false;
                } else if (strlen($function->arguments[$i][0]) && $function->arguments[$i][0] != $documentedarguments[$i][0]) {
                    $match = false;
                } else if ($documentedarguments[$i][0] == 'type') {
                    $match = false;
                } else if ($function->arguments[$i][1] != $documentedarguments[$i][1]) {
                    $match = false;
                }
            }
            $documentedreturns = $function->phpdocs->get_params('return');
            for ($i=0; $match && $i<count($documentedreturns); $i++) {
                if (empty($documentedreturns[$i][0]) || $documentedreturns[$i][0] == 'type') {
                    $match = false;
                }
            }
            if (!$match) {
                $errors[] = array('line' => $function->phpdocs->get_line_number($file, '@param'), 'function' => $function->fullname);
            }
        }
    }
    return $errors;
}

/**
 * Checks that all variables have proper \var token in phpdoc block
 *
 * @param local_moodlecheck_file $file
 * @return array of found errors
 */
function local_moodlecheck_variableshasvar(local_moodlecheck_file $file) {
    $errors = array();
    foreach ($file->get_variables() as $variable) {
        if ($variable->phpdocs !== false) {
            $documentedvars = $variable->phpdocs->get_params('var', 2);
            if (!count($documentedvars) || $documentedvars[0][0] == 'type') {
                $errors[] = array('line' => $variable->phpdocs->get_line_number($file, '@var'), 'variable' => $variable->fullname);
            }
        }
    }
    return $errors;
}

/**
 * Checks that all define statement have constant name in phpdoc block
 *
 * @param local_moodlecheck_file $file
 * @return array of found errors
 */
function local_moodlecheck_definedoccorrect(local_moodlecheck_file $file) {
    $errors = array();
    foreach ($file->get_defines() as $object) {
        if ($object->phpdocs !== false) {
            if (!preg_match('/^\s*'.$object->name.'\s+-\s+(.*)/', $object->phpdocs->get_description(), $matches) || !strlen(trim($matches[1]))) {
                $errors[] = array('line' => $object->phpdocs->get_line_number($file), 'object' => $object->fullname);
            }
        }
    }
    return $errors;
}

/**
 * Makes sure that files have copyright tag
 *
 * @param local_moodlecheck_file $file
 * @return array of found errors
 */
function local_moodlecheck_filehascopyright(local_moodlecheck_file $file) {
    $phpdocs = $file->find_file_phpdocs();
    if ($phpdocs && !count($phpdocs->get_tags('copyright', true))) {
        return array(array('line' => $phpdocs->get_line_number($file, '@copyright')));
    }
    return array();
}

/**
 * Makes sure that all classes have copyright tag
 *
 * @param local_moodlecheck_file $file
 * @return array of found errors
 */
function local_moodlecheck_classeshavecopyright(local_moodlecheck_file $file) {
    $errors = array();
    foreach ($file->get_classes() as $class) {
        if ($class->phpdocs && !count($class->phpdocs->get_tags('copyright', true))) {
            $errors[] = array(
                'line' => $class->phpdocs->get_line_number($file, '@copyright'), 
                'object' => $class->name
            );
        }
    }
    return $errors;
}