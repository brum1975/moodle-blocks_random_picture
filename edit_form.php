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
 * Form for editing random_picture block instances.
 *
 * @package   block_random_picture
 * @copyright 2009 Tim Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Form for editing random_picture block instances.
 *
 * @copyright 2009 Tim Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_random_picture_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
        // Fields for editing random_picture block title and contents.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_title', get_string('configtitle', 'block_random_picture'));
        $mform->setType('config_title', PARAM_MULTILANG);
        $mform->addElement('text', 'config_width', get_string('configwidth', 'block_random_picture'));
        $mform->setDefault('config_width', 150);
        $mform->setType('config_width', PARAM_INTEGER);
        $mform->addElement('text', 'config_refresh', get_string('configrefresh', 'block_random_picture'), array('size' => 5));
        $mform->setDefault('config_refresh', 0);
        $mform->setType('config_refresh', PARAM_INTEGER);
        
        $mform->addElement('selectyesno', 'config_lightboxenabled', get_string('lightboxenabled', 'block_random_picture'));
        $mform->setDefault('config_lightboxenabled', 1);
        //$mform->addHelpButton('config_lightboxenabled', 'lightboxenabled', 'block_random_picture');
        
        $options = array('subdirs'=>0, 'maxfiles'=>0, 'accepted_types'=>'images', 'return_types'=>'FILE_INTERNAL');
        $mform->addElement('filemanager', 'attachments', '', null, $options);
       
   //     $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean'=>true, 'context'=>$this->block->context);
   //     $mform->addElement('editor', 'config_text', get_string('configcontent', 'block_random_picture'), null, $editoroptions);
        //$mform->setType('config_text', PARAM_RAW); // XSS is prevented when printing the block contents and serving files
    }
    


   function set_data($defaults) {
        global $CFG;
        $draftid = file_get_submitted_draft_itemid('attachments');
        file_prepare_draft_area($draftid, $this->block->context->id, 'block_random_picture', 'content', $defaults->id, array('subdirs'=>0));
        $defaults->attachments=$draftid;
        $options = array('subdirs' => 0, 'maxbytes' => $CFG->maxbytes, 'maxfiles' => 50, 'accepted_types' => array('web_image') );

        if ($draftitemid = file_get_submitted_draft_itemid('attachments')) {
            file_save_draft_area_files($draftitemid, $this->block->context->id, 'block_random_picture', 'content' ,$defaults->id, array('subdirs' => 0));
        } else {
            //echo "No Drafts";
        }        
        //$defaults->
        // have to delete text here, otherwise parent::set_data will empty content
        // of editor
        unset($this->block->config->text);
        parent::set_data($defaults);
        // restore $text
        //$this->block->config->text = $text;
    }

    
    
}
