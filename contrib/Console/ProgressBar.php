<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/3_0.txt.                                  |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Stefan Walk <et@php.net>                                    |
// +----------------------------------------------------------------------+
//
// $Id: ProgressBar.php,v 1.3 2003/10/26 20:07:40 et Exp $


/**
 * Class to display a progressbar in the console
 *
 * @package Console_ProgressBar
 * @category Console
 * @version 0.2
 * @author Stefan Walk <et@php.net>
 * @license http://www.php.net/license/3_0.txt PHP License
 */

class Console_ProgressBar {

    // properties {{{
    /**
     * Skeleton for use with sprintf
     */
    var $_skeleton;
    /**
     * The bar gets filled with this
     */
    var $_bar;
    /**
     * The width of the bar
     */
    var $_blen;
    /**
     * The total width of the display
     */
    var $_tlen;
    /**
     * The position of the counter when the job is `done'
     */
    var $_target_num;
    /**
     * Options, like the precision used to display the numbers
     */
    var $_options = array();
    // }}}
    
    // constructor() {{{
    /**
     * Constructor, sets format and size
     *
     * <pre>
     * The Constructor expects 5 to 6 arguments:
     * - The first argument is the format string used to display the progress
     *   bar. It may (and should) contain placeholders that the class will
     *   replace with information like the progress bar itself, the progress in
     *   percent, and so on. Current placeholders are:
     *     %bar%         The progress bar
     *     %current%     The current value
     *     %max%         The maximum malue (the "target" value)
     *     %fraction%    The same as %current%/%max%
     *     %percent%     The status in percent
     *   More placeholders will follow. A format string like:
     *   "* stuff.tar %fraction% KB [%bar%] %percent%"
     *   will lead to a bar looking like this:
     *   "* stuff.tar 391/900 KB [=====>---------]  43.44%"
     * - The second argument is the string that is going to fill the progress
     *   bar. In the above example, the string "=>" was used. If the string you
     *   pass is too short (like "=>" in this example), the leftmost character
     *   is used to pad it to the needed size. If the string you pass is too long,
     *   excessive characters are stripped from the left.
     * - The third argument is the string that fills the "empty" space in the
     *   progress bar. In the above example, that would be "-". If the string
     *   you pass is too short (like "-" in this example), the rightmost
     *   character is used to pad it to the needed size. If the string you pass
     *   is too short, excessive characters are stripped from the right.
     * - The fourth argument specifies the width of the display. If the options
     *   are left untouched, it will tell how many characters the display should
     *   use in total. If the "absolute_width" option is set to false, it tells
     *   how many characters the actual bar (that replaces the %bar%
     *   placeholder) should use.
     * - The fifth argument is the target number of the progress bar. For
     *   example, if you wanted to display a progress bar for a download of a
     *   file that is 115 KB big, you would pass 115 here.
     * - The sixth argument optional. If passed, it should contain an array of
     *   options. For example, passing array('absolute_width' => false) would
     *   set the absolute_width option to false. Current options are:
     *
     *     option             | def.  |  meaning
     *     --------------------------------------------------------------------
     *     percent_precision  | 2     |  Number of decimal places to show when
     *                        |       |  displaying the percentage.
     *     fraction_precision | 0     |  Number of decimal places to show when
     *                        |       |  displaying the current or target
     *                        |       |  number.
     *     percent_pad        | ' '   |  Character to use when padding the
     *                        |       |  percentage to a fixed size. Senseful
     *                        |       |  values are ' ' and '0', but any are
     *                        |       |  possible.
     *     fraction_pad       | ' '   |  Character to use when padding max and
     *                        |       |  current number to a fixed size.
     *                        |       |  Senseful values are ' ' and '0', but 
     *                        |       |  any are possible.
     *     width_absolute     | true  |  If the width passed as an argument
     *                        |       |  should mean the total size (true) or
     *                        |       |  the width of the bar alone.
     *     ansi_terminal      | false |  If this option is true, a better
     *                        |       |  (faster) method for erasing the bar is
     *                        |       |  used.
     * </pre>
     *
     * @param string The format string
     * @param string The string filling the progress bar
     * @param string The string filling empty space in the bar
     * @param float  The target number for the bar
     * @param int    The width of the display
     * @param array  Options for the progress bar
     * @see reset
     */ 
    function Console_ProgressBar($formatstring, $bar, $prefill, $width, 
                                  $target_num, $options = array()) 
    {
        $this->reset($formatstring, $bar, $prefill, $width, $target_num, 
                     $options);
    }
    // }}}

    // {{{ reset($formatstring, $bar, $prefill, $width, $target_num[, $options])
    /**
     * Re-sets format and size.
     *
     * <pre>
     * The reset method expects 5 to 6 arguments:
     * - The first argument is the format string used to display the progress
     *   bar. It may (and should) contain placeholders that the class will
     *   replace with information like the progress bar itself, the progress in
     *   percent, and so on. Current placeholders are:
     *     %bar%         The progress bar
     *     %current%     The current value
     *     %max%         The maximum malue (the "target" value)
     *     %fraction%    The same as %current%/%max%
     *     %percent%     The status in percent
     *   More placeholders will follow. A format string like:
     *   "* stuff.tar %fraction% KB [%bar%] %percent%"
     *   will lead to a bar looking like this:
     *   "* stuff.tar 391/900 KB [=====>---------]  43.44%"
     * - The second argument is the string that is going to fill the progress
     *   bar. In the above example, the string "=>" was used. If the string you
     *   pass is too short (like "=>" in this example), the leftmost character
     *   is used to pad it to the needed size. If the string you pass is too long,
     *   excessive characters are stripped from the left.
     * - The third argument is the string that fills the "empty" space in the
     *   progress bar. In the above example, that would be "-". If the string
     *   you pass is too short (like "-" in this example), the rightmost
     *   character is used to pad it to the needed size. If the string you pass
     *   is too short, excessive characters are stripped from the right.
     * - The fourth argument specifies the width of the display. If the options
     *   are left untouched, it will tell how many characters the display should
     *   use in total. If the "absolute_width" option is set to false, it tells
     *   how many characters the actual bar (that replaces the %bar%
     *   placeholder) should use.
     * - The fifth argument is the target number of the progress bar. For
     *   example, if you wanted to display a progress bar for a download of a
     *   file that is 115 KB big, you would pass 115 here.
     * - The sixth argument optional. If passed, it should contain an array of
     *   options. For example, passing array('absolute_width' => false) would
     *   set the absolute_width option to false. Current options are:
     *
     *     option             | def.  |  meaning
     *     --------------------------------------------------------------------
     *     percent_precision  | 2     |  Number of decimal places to show when
     *                        |       |  displaying the percentage.
     *     fraction_precision | 0     |  Number of decimal places to show when
     *                        |       |  displaying the current or target
     *                        |       |  number.
     *     percent_pad        | ' '   |  Character to use when padding the
     *                        |       |  percentage to a fixed size. Senseful
     *                        |       |  values are ' ' and '0', but any are
     *                        |       |  possible.
     *     fraction_pad       | ' '   |  Character to use when padding max and
     *                        |       |  current number to a fixed size.
     *                        |       |  Senseful values are ' ' and '0', but 
     *                        |       |  any are possible.
     *     width_absolute     | true  |  If the width passed as an argument
     *                        |       |  should mean the total size (true) or
     *                        |       |  the width of the bar alone.
     *     ansi_terminal      | false |  If this option is true, a better
     *                        |       |  (faster) method for erasing the bar is
     *                        |       |  used.
     * </pre>
     *
     * @param string The format string
     * @param string The string filling the progress bar
     * @param string The string filling empty space in the bar
     * @param float  The target number for the bar
     * @param int    The width of the display
     * @param array  Options for the progress bar
     * @return bool
     */
    function reset($formatstring, $bar, $prefill, $width, $target_num, 
                   $options = array()) 
    {
        $this->_target_num = $target_num;
        $default_options = array(
            'percent_precision' => 2,
            'fraction_precision' => 0,
            'percent_pad' => ' ',
            'fraction_pad' => ' ',
            'width_absolute' => true,
            'ansi_terminal' => false,
        );
        foreach ($default_options as $key => $value) {
            if (!isset($options[$key])) {
                $options[$key] = $value;
            } else {
                settype($options[$key], gettype($value));
            }
        }
        $this->_options = $options;
        // placeholder
        $cur = '%2$\''.$options['fraction_pad']{0}.strlen((int)$target_num).'.'
               .$options['fraction_precision'].'f';
        $max = $cur; $max{1} = 3;
        $perc = '%4$\''.$options['percent_pad']{0}.'3.'
                .$options['percent_precision'].'f';
        
        $transitions = array(
            '%%' => '%%',
            '%fraction%' => $cur.'/'.$max,
            '%current%' => $cur,
            '%max%' => $max,
            '%percent%' => $perc.'%%',
            '%bar%' => '%1$s'
        );
        
        $this->_skeleton = strtr($formatstring, $transitions);

        $slen = strlen(sprintf($this->_skeleton, '', 0, 0, 0));

        if ($options['width_absolute']) {
            $blen = $width - $slen;
            $tlen = $width;
        } else {
            $tlen = $width + $slen;
            $blen = $width;
        }

        $lbar = str_pad($bar, $blen, $bar{0}, STR_PAD_LEFT);
        $rbar = str_pad($prefill, $blen, substr($prefill, -1, 1));

        $this->_bar   = substr($lbar,-$blen).substr($rbar,0,$blen);
        $this->_blen  = $blen;
        $this->_tlen  = $tlen;
        $this->_first = true;

        if ($options['ansi_terminal']) {
            print "\x1b[s"; // save cursor position
        }

        return true;
    }
    // }}}
    
    // {{{ update($current)
    /**
     * Updates the bar with new progress information
     *
     * @param int current position of the progress counter
     * @return bool
     */
    function update($current)
    {
        if ($this->_first) {
            $this->_first = false;
            $this->display($current);
            return;
        }
        $this->erase();
        $this->display($current);
    }
    // }}}
    
    // {{{ display($current)
    /**
     * Prints the bar. Usually, you don't need this method, just use update()
     * which handles erasing the previously printed bar also. If you use a
     * custom function (for whatever reason) to erase the bar, use this method.
     *
     * @param int current position of the progress counter
     * @return bool
     */
    function display($current) 
    {
        $percent = $current / $this->_target_num;
        $filled = round($percent * $this->_blen);
        $visbar = substr($this->_bar, $this->_blen - $filled, $this->_blen);
        printf($this->_skeleton, $visbar, $current, 
               $this->_target_num, $percent * 100);
        return true;
    }
    // }}}

    // {{{ erase()
    /**
     * Erases a previously printed bar.
     *
     * @return bool
     */
    function erase() 
    {
        if ($this->_options['ansi_terminal']) {
            print "\x1b[u"; // restore cursor position
        } else {
            print str_repeat(chr(8), $this->_tlen);
        }
    }
    // }}}

}
