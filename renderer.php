<?php

/**
 * Collapsed Topics Information
 *
 * A topic based format that solves the issue of the 'Scroll of Death' when a course has many topics. All topics
 * except zero have a toggle that displays that topic. One or more topics can be displayed at any given time.
 * Toggles are persistent on a per browser session per course basis but can be made to persist longer by a small
 * code change. Full installation instructions, code adaptions and credits are included in the 'Readme.txt' file.
 *
 * @package    course/format
 * @subpackage topcoll
 * @version    See the value of '$plugin->version' in version.php.
 * @copyright  &copy; 2012-onwards G J Barnard in respect to modifications of standard topics format.
 * @author     G J Barnard - gjbarnard at gmail dot com and {@link http://moodle.org/user/profile.php?id=442195}
 * @author     Based on code originally written by Dan Poltawski.
 * @link       http://docs.moodle.org/en/Collapsed_Topics_course_format
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/format/renderer.php');
require_once($CFG->dirroot . '/course/format/topcoll/lib.php');

class format_topcoll_renderer extends format_section_renderer_base {

    private $tccolumnwidth = 100; /* Default width in percent of the column(s). */
    private $tccolumnpadding = 0; /* Defailt padding in pixels of the column(s). */
    private $mymobiletheme = false; /* As not using the MyMobile theme we can react to the number of columns setting. */

    /**
     * Generate the starting container html for a list of sections
     * @return string HTML to output.
     */
    protected function start_section_list() {
        return html_writer::start_tag('ul', array('class' => 'ctopics'));
    }

    /**
     * Generate the starting container html for a list of sections when showing a toggle.
     * @return string HTML to output.
     */
    protected function start_toggle_section_list() {
        return html_writer::start_tag('ul', array('class' => 'ctopics', 'style' => 'width:' . $this->tccolumnwidth . '%; float:left; padding:' . $this->tccolumnpadding . 'px;'));
    }

    /**
     * Generate the closing container html for a list of sections
     * @return string HTML to output.
     */
    protected function end_section_list() {
        return html_writer::end_tag('ul');
    }

    /**
     * Generate the title for this section page
     * @return string the page title
     */
    protected function page_title() {
        return get_string('sectionname', 'format_topcoll');
    }

    /**
     * Generate the content to displayed on the right part of a section
     * before course modules are included
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return string HTML to output.
     */
    protected function section_right_content($section, $course, $onsectionpage) {
        $o = $this->output->spacer();

        if ($section->section != 0) {
            $controls = $this->section_edit_controls($course, $section, $onsectionpage);
            if (!empty($controls)) {
                $o .= implode('<br />', $controls);
            } else {
                global $tcsetting;
                switch ($tcsetting->layoutelement) {
                    case 1:
                    case 3:
                    case 5:
                        // Get the specific words from the language files.
                        $topictext = null;
                        if (($tcsetting->layoutstructure == 1) || ($tcsetting->layoutstructure == 4)) {
                            $topictext = get_string('setlayoutstructuretopic', 'format_topcoll');
                        } else if (($tcsetting->layoutstructure == 2) || ($tcsetting->layoutstructure == 3)) {
                            $topictext = get_string('setlayoutstructureweek', 'format_topcoll');
                        } else {
                            $topictext = get_string('setlayoutstructureday', 'format_topcoll');
                        }

                        $o .= html_writer::tag('span', $topictext . '<br />' . $section->section, array('class' => 'cps_centre'));
                        break;
                }
            }
        }

        return $o;
    }

    /**
     * Generate the content to displayed on the left part of a section
     * before course modules are included
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return string HTML to output.
     */
    protected function section_left_content($section, $course, $onsectionpage) {
        $o = $this->output->spacer();

        if ($section->section != 0) {
            // Only in the non-general sections.
            if ($this->is_section_current($section, $course)) {
                $o .= get_accesshide(get_string('currentsection', 'format_' . $course->format));
            }
            global $tcsetting;
            switch ($tcsetting->layoutelement) {
                case 1:
                case 2:
                case 5:
                case 6:
                    $o .= html_writer::tag('span', $section->section, array('class' => 'cps_centre'));
                    break;
            }
        }
        return $o;
    }

    /**
     * Generate the edit controls of a section
     *
     * @param stdClass $course The course entry from DB
     * @param stdClass $section The course_section entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return array of links with edit controls
     */
    protected function section_edit_controls($course, $section, $onsectionpage = false) {
        global $PAGE;

        if (!$PAGE->user_is_editing()) {
            return array();
        }

        $coursecontext = context_course::instance($course->id);

        if ($onsectionpage) {
            $url = course_get_url($course, $section->section);
        } else {
            $url = course_get_url($course);
        }
        $url->param('sesskey', sesskey());

        global $tcsetting;
        $controls = array();
        if ((($tcsetting->layoutstructure == 1) || ($tcsetting->layoutstructure == 4)) && has_capability('moodle/course:setcurrentsection', $coursecontext)) {
            if ($course->marker == $section->section) {  // Show the "light globe" on/off.
                $url->param('marker', 0);
                $controls[] = html_writer::link($url,
                                    html_writer::empty_tag('img', array('src' => $this->output->pix_url('i/marked'),
                                        'class' => 'icon ', 'alt' => get_string('markedthissection','format_topcoll'))),
                                    array('title' => get_string('markedthissection','format_topcoll'), 'class' => 'editing_highlight'));
            } else {
                $url->param('marker', $section->section);
                $controls[] = html_writer::link($url,
                                html_writer::empty_tag('img', array('src' => $this->output->pix_url('i/marker'),
                                    'class' => 'icon', 'alt' => get_string('markthissection','format_topcoll'))),
                                array('title' => get_string('markthissection','format_topcoll'), 'class' => 'editing_highlight'));
            }
        }

        return array_merge($controls, parent::section_edit_controls($course, $section, $onsectionpage));
    }

    /**
     * Generate the display of the header part of a section before
     * course modules are included
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @param int $sectionreturn The section to return to after an action
     * @return string HTML to output.
     */
    protected function section_header($section, $course, $onsectionpage, $sectionreturn = 0) {
        $o = '';
        global $PAGE;

        $sectionstyle = '';
        global $tcsetting;
        $toggletext = get_string('topcolltoggle', 'format_topcoll'); // The word 'Toggle'.

        if ($section->section != 0) {
            // Only in the non-general sections.
            if (!$section->visible) {
                $sectionstyle = ' hidden';
            } else if ($this->is_section_current($section, $course)) {
                global $thecurrentsection;
                $thecurrentsection = $section->section;
                $sectionstyle = ' current';
            }
        }

        $o .= html_writer::start_tag('li', array('id' => 'section-' . $section->section,
                    'class' => 'section main clearfix' . $sectionstyle));

        if ($this->mymobiletheme == false) {
            $leftcontent = $this->section_left_content($section, $course, $onsectionpage);
            $o .= html_writer::tag('div', $leftcontent, array('class' => 'left side'));
        }

        $rightcontent = $this->section_right_content($section, $course, $onsectionpage);
        $o .= html_writer::tag('div', $rightcontent, array('class' => 'right side'));
        $o .= html_writer::start_tag('div', array('class' => 'content'));

        $context = context_course::instance($course->id);

        global $tcscreenreader;
        if (($onsectionpage == false) && ($tcscreenreader == false) && ($section->section != 0)) {
            $o .= html_writer::start_tag('div', array('class' => 'sectionhead toggle', 'id' => 'toggle-' . $section->section));

            $title = get_section_name($course, $section);
            $toggleclass = 'toggle_closed';
            if ((string) $section->name == '') { // Name is empty.
                $toggleclass .= ' cps_noname';
            }
            $o .= html_writer::start_tag('a', array('class' => $toggleclass, 'href' => '#', 'onclick' => 'toggle_topic(this,' . $section->section . '); return false;'));
            $o .= $title;
            switch ($tcsetting->layoutelement) {
                case 1:
                case 2:
                case 3:
                case 4:
                    $o .= ' - ' . $toggletext;
                    break;
            }
            if ($PAGE->user_is_editing() && has_capability('moodle/course:update', $context)) {
                $url = new moodle_url('/course/editsection.php', array('id' => $section->id, 'sr' => $sectionreturn));

                $o .= html_writer::link($url, html_writer::empty_tag('img', array('src' => $this->output->pix_url('t/edit'), 'class' => 'iconsmall edit')), array('title' => get_string('editsummary')));
            }
            $o .= html_writer::end_tag('a');
            $o .= html_writer::end_tag('div');
            $o .= html_writer::start_tag('div', array('class' => 'sectionbody toggledsection', 'id' => 'toggledsection-' . $section->section));
            if ($section->section != 0 && $course->coursedisplay == COURSE_DISPLAY_MULTIPAGE) {
                $o .= html_writer::link(course_get_url($course, $section->section), $title);
            }

            $o .= html_writer::start_tag('div', array('class' => 'summary'));
            $o .= $this->format_summary_text($section);

            if ($PAGE->user_is_editing() && has_capability('moodle/course:update', $context)) {
                $url = new moodle_url('/course/editsection.php', array('id' => $section->id, 'sr' => $sectionreturn));
                $o.= html_writer::link($url, html_writer::empty_tag('img', array('src' => $this->output->pix_url('t/edit'), 'class' => 'iconsmall edit')), array('title' => get_string('editsummary')));
            }
            $o .= html_writer::end_tag('div');

            $o .= $this->section_availability_message($section);
        } else {
            // When on a section page, we only display the general section title, if title is not the default one
            $hasnamesecpg = ($section->section == 0 && (string)$section->name !== '');

            if ($hasnamesecpg) {
                $o .= $this->output->heading($this->section_title($section, $course), 3, 'sectionname');
            }
            //$o .= parent::section_header($section, $course, $onsectionpage);
            $o .= html_writer::start_tag('div', array('class' => 'summary'));
            $o .= $this->format_summary_text($section);

            if ($PAGE->user_is_editing() && has_capability('moodle/course:update', $context)) {
                $url = new moodle_url('/course/editsection.php', array('id' => $section->id, 'sr' => $sectionreturn));
                $o .= html_writer::link($url, html_writer::empty_tag('img', array('src' => $this->output->pix_url('t/edit'), 'class' => 'iconsmall edit')), array('title' => get_string('editsummary')));
            }
            $o .= html_writer::end_tag('div');

            $o .= $this->section_availability_message($section);
        }
        return $o;
    }

    /**
     * Generate the display of the footer part of a section
     *
     * @return string HTML to output.
     */
    protected function section_footer() {
        $o = html_writer::end_tag('div');
        $o .= html_writer::end_tag('li');

        return $o;
    }

    /**
     * Generate the html for the 'Jump to' menu on a single section page.
     * Temporary until MDL-34917 in core.
     * @param stdClass $course The course entry from DB
     * @param array $sections The course_sections entries from the DB
     * @param $displaysection the current displayed section number.
     *
     * @return string HTML to output.
     */
    protected function section_nav_selection($course, $sections, $displaysection) {
        $o = '';
        $section = 1;
        $sectionmenu = array();
        $sectionmenu[0] = get_string('maincoursepage','format_topcoll');  // Section 0 is never jumped to and is therefore used to indicate the main page.  And temporary until MDL-34917 in core.
        $context = context_course::instance($course->id);
        while ($section <= $course->numsections) {
            if (!empty($sections[$section])) {
                $thissection = $sections[$section];
            } else {
                // This will create a course section if it doesn't exist..
                $thissection = get_course_section($section, $course->id);

                // The returned section is only a bare database object rather than
                // a section_info object - we will need at least the uservisible
                // field in it.
                $thissection->uservisible = true;
                $thissection->availableinfo = null;
                $thissection->showavailability = 0;
            }
            $showsection = (has_capability('moodle/course:viewhiddensections', $context) or $thissection->visible or !$course->hiddensections);

            if (($showsection) && ($section != $displaysection)) {
                $sectionmenu[$section] = get_section_name($course, $thissection);
            }
            $section++;
        }

        $select = new single_select(new moodle_url('/course/view.php', array('id'=>$course->id)), 'section', $sectionmenu);
        $select->class = 'jumpmenu';
        $select->formid = 'sectionmenu';
        $o .= $this->output->render($select);

        return $o;
    }

    /**
     * Output the html for a single section page.
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections The course_sections entries from the DB
     * @param array $mods used for print_section()
     * @param array $modnames used for print_section()
     * @param array $modnamesused used for print_section()
     * @param int $displaysection The section number in the course which is being displayed
     */
    public function print_single_section_page($course, $sections, $mods, $modnames, $modnamesused, $displaysection) {
        global $PAGE;

        // Can we view the section in question?
        $context = context_course::instance($course->id);
        $canviewhidden = has_capability('moodle/course:viewhiddensections', $context);

        if (!isset($sections[$displaysection])) {
            // This section doesn't exist
            print_error('unknowncoursesection', 'error', null, $course->fullname);
            return;
        }

        if (!$sections[$displaysection]->visible && !$canviewhidden) {
            if (!$course->hiddensections) {
                echo $this->start_section_list();
                echo $this->section_hidden($displaysection);
                echo $this->end_section_list();
            }
            // Can't view this section.
            return;
        }

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course, $displaysection);

        // General section if non-empty.
        $thissection = $sections[0];
        if ($thissection->summary or $thissection->sequence or $PAGE->user_is_editing()) {
            echo $this->start_section_list();
            echo $this->section_header($thissection, $course, true, $displaysection);
            print_section($course, $thissection, $mods, $modnamesused, true, "100%", false, $displaysection);
            if ($PAGE->user_is_editing()) {
                print_section_add_menus($course, 0, $modnames, false, false, $displaysection);
            }
            echo $this->section_footer();
            echo $this->end_section_list();
        }

        // Start single-section div
        echo html_writer::start_tag('div', array('class' => 'single-section'));

        // Title with section navigation links.
        $sectionnavlinks = $this->get_nav_links($course, $sections, $displaysection);
        $sectiontitle = '';
        $sectiontitle .= html_writer::start_tag('div', array('class' => 'section-navigation header headingblock'));
        $sectiontitle .= html_writer::tag('span', $sectionnavlinks['previous'], array('class' => 'mdl-left'));
        $sectiontitle .= html_writer::tag('span', $sectionnavlinks['next'], array('class' => 'mdl-right'));
        // Title attributes
        $titleattr = 'mdl-align title';
        if (!$sections[$displaysection]->visible) {
            $titleattr .= ' dimmed_text';
        }
        $sectiontitle .= html_writer::tag('div', get_section_name($course, $sections[$displaysection]), array('class' => $titleattr));
        $sectiontitle .= html_writer::end_tag('div');
        echo $sectiontitle;

        // Now the list of sections..
        echo $this->start_section_list();

        // The requested section page.
        $thissection = $sections[$displaysection];
        echo $this->section_header($thissection, $course, true, $displaysection);
        // Show completion help icon.
        $completioninfo = new completion_info($course);
        echo $completioninfo->display_help_icon();

        print_section($course, $thissection, $mods, $modnamesused, true, '100%', false, $displaysection);
        if ($PAGE->user_is_editing()) {
            print_section_add_menus($course, $displaysection, $modnames, false, false, $displaysection);
        }
        echo $this->section_footer();
        echo $this->end_section_list();

        // Display section bottom navigation.
        $sectionbottomnav = '';
        $sectionbottomnav .= html_writer::start_tag('div', array('class' => 'section-navigation mdl-bottom'));
        $sectionbottomnav .= html_writer::tag('span', $sectionnavlinks['previous'], array('class' => 'mdl-left'));
        $sectionbottomnav .= html_writer::tag('span', $sectionnavlinks['next'], array('class' => 'mdl-right'));
        $sectionbottomnav .= html_writer::tag('div', $this->section_nav_selection($course, $sections, $displaysection), array('class' => 'mdl-align'));
        $sectionbottomnav .= html_writer::end_tag('div');
        echo $sectionbottomnav;

        // close single-section div.
        echo html_writer::end_tag('div');
    }

    /**
     * Output the html for a multiple section page
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections The course_sections entries from the DB
     * @param array $mods used for print_section()
     * @param array $modnames used for print_section()
     * @param array $modnamesused used for print_section()
     */
    public function print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused) {
        global $PAGE;
        global $tcsetting;

        $this->mymobiletheme = ($PAGE->theme->name == 'mymobile');  // Not brilliant, but will work!

        $userisediting = $PAGE->user_is_editing();

        $context = context_course::instance($course->id);
        // Title with completion help icon.
        $completioninfo = new completion_info($course);
        echo $completioninfo->display_help_icon();
        echo $this->output->heading($this->page_title(), 2, 'accesshide');

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course);

        // Now the list of sections..
        $this->tccolumnwidth = 100; // Reset to default.
        echo $this->start_section_list();

        // Collapsed Topics settings.
        echo $this->settings($course);

        // General section if non-empty.
        $thissection = $sections[0];
        unset($sections[0]);
        if ($thissection->summary or $thissection->sequence or $PAGE->user_is_editing()) {
            echo $this->section_header($thissection, $course, false);
            print_section($course, $thissection, $mods, $modnamesused, true);
            if ($PAGE->user_is_editing()) {
                print_section_add_menus($course, 0, $modnames);
            }
            echo $this->section_footer();
        }

        if ($PAGE->user_is_editing() || $course->coursedisplay != COURSE_DISPLAY_MULTIPAGE) {
            // Collapsed Topics all toggles.
            echo $this->toggle_all();
        }

        $currentsectionfirst = false;
        if ($tcsetting->layoutstructure == 4) {
            $currentsectionfirst = true;
        }

        if (($tcsetting->layoutstructure != 3) || ($userisediting)) {
            $section = 1;
        } else {
            $timenow = time();
            $weekofseconds = 604800;
            $course->enddate = $course->startdate + ($weekofseconds * $course->numsections);
            $section = $course->numsections;
            $weekdate = $course->enddate;      // this should be 0:00 Monday of that week
            $weekdate -= 7200;                 // Subtract two hours to avoid possible DST problems
        }

        $numsections = $course->numsections; // Because we want to manipulate this for column breakpoints.
        if (($tcsetting->layoutstructure == 3) && ($userisediting == false)) {
            $loopsection = 1;
            $numsections = 0;
            while ($loopsection <= $course->numsections) {
                $nextweekdate = $weekdate - ($weekofseconds);
                if (!empty($sections[$section])) {
                    $thissection = $sections[$section];
                } else {
                    // This will create a course section if it doesn't exist..
                    $thissection = get_course_section($section, $course->id);

                    // The returned section is only a bare database object rather than
                    // a section_info object - we will need at least the uservisible
                    // field in it.
                    $thissection->uservisible = true;
                    $thissection->availableinfo = null;
                    $thissection->showavailability = 0;
                }
                if ((($thissection->uservisible ||
                        ($thissection->visible && !$thissection->available && $thissection->showavailability))
                        && ($nextweekdate <= $timenow)) == true) {
                    $numsections++; // Section not shown so do not count in columns calculation.
                }
                $weekdate = $nextweekdate;
                $section--;
                $loopsection++;
            }
            // Reset
            $section = $course->numsections;
            $weekdate = $course->enddate;      // this should be 0:00 Monday of that week
            $weekdate -= 7200;                 // Subtract two hours to avoid possible DST problems
        }

        $columnbreakpoint = 0;
        if ($numsections < $tcsetting->layoutcolumns) {
            $tcsetting->layoutcolumns = $numsections;  // Help to ensure a reasonable display.
        }
        if (($tcsetting->layoutcolumns > 1) && ($this->mymobiletheme == false)) {
            if ($tcsetting->layoutcolumns > 4) {
                // Default in config.php (and reset in database) or database has been changed incorrectly.
                $tcsetting->layoutcolumns = 4;

                // Update DB.
                global $DB;
                $DB->update_record('format_topcoll_settings', $tcsetting);
            }
            $this->tccolumnwidth = 100 / $tcsetting->layoutcolumns;
            $this->tccolumnwidth -= 1; // Allow for the padding in %.
            $this->tccolumnpadding = 2; // px
            //$columnbreakpoint = $numsections / $tcsetting->layoutcolumns + 1;
        } elseif ($tcsetting->layoutcolumns < 1) {
            // Default in config.php (and reset in database) or database has been changed incorrectly.
            $tcsetting->layoutcolumns = 1;

            // Update DB.
            global $DB;
            $DB->update_record('format_topcoll_settings', $tcsetting);
        }
        echo $this->end_section_list();
        echo $this->start_toggle_section_list();

        $loopsection = 1;
        $canbreak = false; // Once the first section is shown we can decide if we break on another column.
        $columncount = 1;
        $shownsectioncount = 0;
        while ($loopsection <= $course->numsections) {
            if (($tcsetting->layoutstructure == 3) && ($userisediting == false)) {
                $nextweekdate = $weekdate - ($weekofseconds);
            }
            if (!empty($sections[$section])) {
                $thissection = $sections[$section];
            } else {
                // This will create a course section if it doesn't exist..
                $thissection = get_course_section($section, $course->id);

                // The returned section is only a bare database object rather than
                // a section_info object - we will need at least the uservisible
                // field in it.
                $thissection->uservisible = true;
                $thissection->availableinfo = null;
                $thissection->showavailability = 0;
            }
            // Show the section if the user is permitted to access it, OR if it's not available
            // but showavailability is turned on
            if (($tcsetting->layoutstructure != 3) || ($userisediting)) {
                $showsection = $thissection->uservisible ||
                        ($thissection->visible && !$thissection->available && $thissection->showavailability);
            } else {
                $showsection = ($thissection->uservisible ||
                        ($thissection->visible && !$thissection->available && $thissection->showavailability))
                        && ($nextweekdate <= $timenow);
            }
            if (($currentsectionfirst == true) && ($showsection == true)) {
                $showsection = ($course->marker == $section);  // Show  the section if we were meant to and it is the current section.
            } else if (($tcsetting->layoutstructure == 4) && ($course->marker == $section)) {
                $showsection = false; // Do not reshow current section.
            }
            if (!$showsection) {
                // Hidden section message is overridden by 'unavailable' control
                // (showavailability option).
                if ($tcsetting->layoutstructure != 4) {
                    if (($tcsetting->layoutstructure != 3) || ($userisediting)) {
                        if (!$course->hiddensections && $thissection->available) {
                            echo $this->section_hidden($section);
                        }
                    }
                }
            } else {
                $shownsectioncount++;
                if (!$PAGE->user_is_editing() && $course->coursedisplay == COURSE_DISPLAY_MULTIPAGE) {
                    // Display section summary only.
                    echo $this->section_summary($thissection, $course, $mods);
                } else {
                    echo $this->section_header($thissection, $course, false);
                    if ($thissection->uservisible) {
                        print_section($course, $thissection, $mods, $modnamesused);

                        if ($PAGE->user_is_editing()) {
                            print_section_add_menus($course, $section, $modnames);
                        }
                    }
                    echo html_writer::end_tag('div');
                    echo $this->section_footer();
                }
            }

            if ($currentsectionfirst == false) {
                unset($sections[$section]); // Only need to do this on the iteration when $currentsectionfirst is not true as this iteration will always happen.  Otherwise you get duplicate entries in course_sections in the DB.
            }
            if (($tcsetting->layoutstructure != 3) || ($userisediting)) {
                $section++;
            } else {
                $section--;
                if (($tcsetting->layoutstructure == 3) && ($userisediting == false)) {
                    $weekdate = $nextweekdate;
                }
            }

            if (($canbreak == false) && ($currentsectionfirst == false) && ($showsection == true)) {
                $canbreak = true;
                $columnbreakpoint = ($shownsectioncount + ($numsections / $tcsetting->layoutcolumns)) - 1;
                if ($tcsetting->layoutstructure == 4) {
                    $columnbreakpoint -= 1;
                }
            }

            if (($currentsectionfirst == false) && ($canbreak == true) && ($shownsectioncount >= $columnbreakpoint) && ($columncount < $tcsetting->layoutcolumns)) {
                echo $this->end_section_list();
                echo $this->start_toggle_section_list();
                $columncount++;
                // Next breakpoint is...
                $columnbreakpoint += $numsections / $tcsetting->layoutcolumns;
            }
            $loopsection++;
            if (($currentsectionfirst == true) && ($loopsection > $course->numsections)) {
                // Now show the rest.
                $currentsectionfirst = false;
                $loopsection = 1;
                $section = 1;
            }
        }

        if ($PAGE->user_is_editing() and has_capability('moodle/course:update', $context)) {
            // Print stealth sections if present.
            $modinfo = get_fast_modinfo($course);
            foreach ($sections as $section => $thissection) {
                if (empty($modinfo->sections[$section])) {
                    continue;
                }
                echo $this->stealth_section_header($section);
                print_section($course, $thissection, $mods, $modnamesused);
                echo $this->stealth_section_footer();
            }

            echo $this->end_section_list();

            echo html_writer::start_tag('div', array('id' => 'changenumsections', 'class' => 'mdl-right'));

            // Increase number of sections.
            $straddsection = get_string('increasesections', 'moodle');
            $url = new moodle_url('/course/changenumsections.php',
                            array('courseid' => $course->id,
                                'increase' => true,
                                'sesskey' => sesskey()));
            $icon = $this->output->pix_icon('t/switch_plus', $straddsection);
            echo html_writer::link($url, $icon . get_accesshide($straddsection), array('class' => 'increase-sections'));

            if ($course->numsections > 0) {
                // Reduce number of sections sections.
                $strremovesection = get_string('reducesections', 'moodle');
                $url = new moodle_url('/course/changenumsections.php',
                                array('courseid' => $course->id,
                                    'increase' => false,
                                    'sesskey' => sesskey()));
                $icon = $this->output->pix_icon('t/switch_minus', $strremovesection);
                echo html_writer::link($url, $icon . get_accesshide($strremovesection), array('class' => 'reduce-sections'));
            }

            echo html_writer::end_tag('div');
        } else {
            echo $this->end_section_list();
        }
    }

    /**
     * Is the section passed in the current section?
     *
     * @param stdClass $section The course_section entry from the DB
     * @param stdClass $course The course entry from DB
     * @return bool true if the section is current
     */
    protected function is_section_current($section, $course) {
        global $tcsetting;
        if (($tcsetting->layoutstructure == 2) || ($tcsetting->layoutstructure == 3)) {
            if ($section->section < 1) {
                return false;
            }

            $timenow = time();
            $dates = format_topcoll_get_section_dates($section, $course);

            return (($timenow >= $dates->start) && ($timenow < $dates->end));
        } else if ($tcsetting->layoutstructure == 5) {
            if ($section->section < 1) {
                return false;
            }

            $timenow = time();
            $day = format_topcoll_get_section_day($section, $course);
            $onedayseconds = 86400;
            return (($timenow >= $day) && ($timenow < ($day + $onedayseconds)));
        } else {
            return parent::is_section_current($section, $course);
        }
    }

    // Collapsed Topics non-overridden additions.
    /**
     * Displays the settings icon for the course if required.
     * @param stdClass $course The course entry from DB
     * @return string HTML to output.
     */
    public function settings($course) {
        global $PAGE;

        $o = '';

        $coursecontext = context_course::instance($course->id);
        if ($PAGE->user_is_editing() && has_capability('moodle/course:update', $coursecontext)) {
            $o .= html_writer::start_tag('li', array('class' => 'tcsection main clearfix'));

            $o .= html_writer::tag('div', $this->output->spacer(), array('class' => 'left side'));

            $o .= html_writer::tag('div', $this->output->spacer(), array('class' => 'right side'));

            $o .= html_writer::start_tag('div', array('class' => 'content'));
            $o .= html_writer::start_tag('div', array('class' => 'sectionbody'));
            $o .= html_writer::start_tag('div', array('class' => 'tcsettingscontainer'));
            $o .= html_writer::tag('a', html_writer::tag('div', '', array('id' => 'tc-set-settings')), array('title' => get_string("settings"), 'href' => 'format/topcoll/forms/settings.php?id=' . $course->id . '&sesskey=' . sesskey()));
            $o .= html_writer::tag('div',get_string('formatsettingsinformation','format_topcoll'));
            $o .= html_writer::end_tag('div');
            $o .= html_writer::end_tag('div');
            $o .= html_writer::end_tag('div');
            $o .= html_writer::end_tag('li');
        }
        return $o;
    }

    /**
     * Displays the toggle all fuctionality.
     * @return string HTML to output.
     */
    public function toggle_all() {
        global $tcscreenreader;
        $o = '';
        if ($tcscreenreader == false) { // No need to show if in screen reader mode.
            $toggletext = get_string('topcolltoggle', 'format_topcoll'); // The word 'Toggle'.
            // Toggle all.

            $o .= html_writer::start_tag('li', array('class' => 'tcsection main clearfix', 'id' => 'toggle-all'));

            if ($this->mymobiletheme == false) {
                $o.= html_writer::tag('div', $this->output->spacer(), array('class' => 'left side'));
            }
            $o .= html_writer::tag('div', $this->output->spacer(), array('class' => 'right side'));

            $o .= html_writer::start_tag('div', array('class' => 'content'));
            $o .= html_writer::start_tag('div', array('class' => 'sectionbody'));
            $o .= html_writer::start_tag('h4', null);
            $o .= html_writer::tag('a', get_string('topcollopened', 'format_topcoll'), array('class' => 'on', 'href' => '#', 'onclick' => 'all_opened(); return false;'));
            $o .= html_writer::tag('a', get_string('topcollclosed', 'format_topcoll'), array('class' => 'off', 'href' => '#', 'onclick' => 'all_closed(); return false;'));
            $o .= html_writer::tag('span', get_string('topcollall', 'format_topcoll'), null);
            $o .= html_writer::end_tag('h4');
            $o .= html_writer::end_tag('div');
            $o .= html_writer::end_tag('div');
            $o .= html_writer::end_tag('li');
        }
        return $o;
    }

}
