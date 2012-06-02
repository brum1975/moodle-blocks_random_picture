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
 * @copyright 1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_random_picture extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_random_picture');
    }

    function applicable_formats() {
        return array('all' => true);
    }

    function specialization() {
        $this->title = isset($this->config->title) ? format_string($this->config->title) : format_string(get_string('newrandom_pictureblock', 'block_random_picture'));
    }

    function instance_allow_multiple() {
        return true;
    }
    function instance_can_be_docked() {
        return false;
    }
    function get_image_resized($image, $width) {
        global $CFG;
        require_once($CFG->libdir.'/gdlib.php');
            $fullimage = imagecreatefromstring($image->get_content());
            if (!$imageinfo = $image->get_imageinfo()) {
                throw new file_exception('storedfileproblem', 'File is not an image');
            }
            $resizeratio = $width/$imageinfo['width'];
            if ($resizeratio > 1){$resizeratio = 1;$width=$imageinfo['width'];}
            $height = $imageinfo['height']*$resizeratio;
     
            $resized = imagecreatetruecolor($width, $height);
            imagecopybicubic($resized, $fullimage, 0, 0, 0, 0, $width, $height, $imageinfo['width'], $imageinfo['height']);
            return $resized;

        }
   
    function create_thumbnail(&$fs,$file){                        
        //create thumb of current dimension
        $fullimage = $fs->get_file($this->context->id,'block_random_picture','content',$file->get_itemid(),$file->get_filepath(),$file->get_filename());
        if (!$imageinfo = $fullimage->get_imageinfo()) {
            throw new file_exception('storedfileproblem', 'File is not an image');
        }
        $thumbnailwidth=$this->config->width;

        $thumbinfo = array(
            'contextid' => $this->context->id,
            'component' => 'block_random_picture',
            'filearea' => 'thumbnail',
            'itemid' => $file->get_itemid(),
            'filepath' => $file->get_filepath(),
            'filename' => $file->get_filename());
        $fileext = pathinfo($file->get_filename(), PATHINFO_EXTENSION);
        ob_start();
        if ($fileext=='jpg' || $fileext=='jpeg'){
            imagejpeg($this->get_image_resized($fullimage,$thumbnailwidth));
        } elseif ($fileext=='png'){
            imagepng($this->get_image_resized($fullimage,$thumbnailwidth));
        }
        $thumbnail = ob_get_clean();
        $fs = get_file_storage();
        $fs->create_file_from_string($thumbinfo, $thumbnail); 
    
    }

   
    function get_content() {
        global $CFG, $PAGE;
        
        require_once($CFG->libdir . '/filelib.php');

        if ($this->content !== NULL) {
            return $this->content;
        }

        $filteropt = new stdClass;
        $filteropt->overflowdiv = true;
        if ($this->content_is_trusted()) {
            // fancy html allowed only on course, category and system blocks.
            $filteropt->noclean = true;
        }

        $this->content = new stdClass;
        $this->content->footer = '';
        if (isset($this->config->refresh)) {
            if(!isset($this->config->nexttime)){
                $this->config->nexttime = 0;
            }
            if(time() > $this->config->nexttime){
                $fs = get_file_storage();
                $files = $fs->get_area_files($this->context->id, 'block_random_picture', 'content',$this->context->instanceid);
                $numberofentries = count($files)-2;//-1 as count starts at one but index starts at 0 and another -1 as . is counted as a file
                if (!isset($this->config->chosen)){
                    $this->config->chosen = -1;
                }
                $j=$this->config->chosen;
                if ($numberofentries>1){
                    while ($j == $this->config->chosen){
                        $j = (rand(0,$numberofentries));                      
                    }
                }  
                $this->config->cache = '';
                $this->config->chosen = $j;
                $this->config->nexttime = time()+60*$this->config->refresh; 
                //can't reference $files[$j]!!! so cycling through until get to $j-th value
                //is this the best way?
                $tempcount=0;
                foreach ($files as $file){
                    if($file->get_filename()!=='.'){
                        if ($tempcount==$j){
                        break;
                        } else {
                            $tempcount++;
                        }
                    }
 
                }
                //$this->config->cache = '';
                //get full image
                $requiredimage = $CFG->wwwroot.'/pluginfile.php/'.$this->context->id.'/block_random_picture/content/'.$file->get_filename();
                //check if thumb already
                $thumb = $fs->get_file($this->context->id,'block_random_picture','thumbnail',$file->get_itemid(),$file->get_filepath(),$file->get_filename());
                if ($thumb){
                    //Thumbnail Found
                    if (!$imageinfo = $thumb->get_imageinfo()) {
                        throw new file_exception('storedfileproblem', 'File is not an image');
                    }
                    if ($this->config->width == $imageinfo['width']){
                        //do nothing as current image is fine
                    } else {
                        //Create new thumbnail as dimensions changed
                        $thumb->delete();
                        $this->create_thumbnail($fs,$file);
                    }
                } else {//No Thumbnail Found                    
                    //create thumb of current dimension
                    $this->create_thumbnail($fs,$file);                          
                    
                }
                if ($this->config->lightboxenabled){
                    foreach ($files as $f){
                       $tempfile = $f->get_filename();
                       if ($tempfile!=='.'){
                          $allfilenames[] = $tempfile; 
                       }
                    }
                    $numberofentries = count($allfilenames);
                    for ($i=0; $i<$numberofentries;$i++){
                        if ($i===$j){
                            $imgurl=$CFG->wwwroot.'/pluginfile.php/'.$this->context->id.'/block_random_picture/content/'.$allfilenames[$i];
                            $thumburl=$CFG->wwwroot.'/pluginfile.php/'.$this->context->id.'/block_random_picture/thumbnail/'.$allfilenames[$i];
                            $this->config->cache .= '<a href="'.$imgurl.'" rel="lightbox[rand'.$this->context->instanceid.']" ><img width="'.$this->config->width.'px" src="'.$thumburl.'" /></a>';
                        } else {
                            $imgurl=$CFG->wwwroot.'/pluginfile.php/'.$this->context->id.'/block_random_picture/content/'.$allfilenames[$i];
                            $this->config->cache .= '<a class="hiddenlightboxlink" href="'.$imgurl.'" rel="lightbox[rand'.$this->context->instanceid.']">Random Image '.$i.'</a>';
                        }
                    }                    
                } else {
                    $imgurl=$CFG->wwwroot.'/pluginfile.php/'.$this->context->id.'/block_random_picture/content/'.$file->get_filename();
                    $thumburl=$CFG->wwwroot.'/pluginfile.php/'.$this->context->id.'/block_random_picture/thumbnail/'.$file->get_filename();
                    $this->config->cache .= '<a href="'.$imgurl.'" rel="lightbox[rand'.$this->context->instanceid.']" ><img width="'.$this->config->width.'px" src="'.$thumburl.'" /></a>';
                }

                parent::instance_config_save($this->config);                 
            } 
            
            $this->content->text = $this->config->cache;  
        } else {
            $this->content->text = '';
        }

        unset($filteropt); // memory footprint

        $this->page->requires->yui_module('moodle-block_random_picture-lightbox','M.block_random_picture.init');
        return $this->content;
    }


    /**
     * Serialize and store config data
     */
    function instance_config_save($data, $nolongerused = false) {
        global $DB;

        $config = clone($data);
        parent::instance_config_save($config, $nolongerused);
    }

    function instance_delete() {
        global $DB;
        $fs = get_file_storage();
        $fs->delete_area_files($this->context->id, 'block_random_picture');
        return true;
    }

    function content_is_trusted() {
        global $SCRIPT;

        if (!$context = get_context_instance_by_id($this->instance->parentcontextid)) {
            return false;
        }
        //find out if this block is on the profile page
        if ($context->contextlevel == CONTEXT_USER) {
            if ($SCRIPT === '/my/index.php') {
                // this is exception - page is completely private, nobody else may see content there
                // that is why we allow JS here
                return true;
            } else {
                // no JS on public personal pages, it would be a big security issue
                return false;
            }
        }

        return true;
    }
}
