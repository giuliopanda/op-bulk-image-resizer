<?php
/**
 * La gestione delle traduzioni.
 * 
 * @since      0.9.0
 *
 * @package    op-bulk-image-resizer
 * @subpackage op-bulk-image-resizer/includes
 */

// Carico i file della lingua
load_plugin_textdomain('op-bir', false, basename(dirname(__FILE__)) . '/languages');

/**
 * Quì traduco i vari file della lingua per il javascript. 
 * Non uso il metodo consigliato da wordpess perché l'ho trovato troppo complicato.
 */
function op_transalte_javascript() {
    ?>
        <script>
            var t9n_pause = "<?php esc_attr(_e("Updates are paused. Click on resume to continue.", "op-bir")); ?>";
            var t9n_confirm_1 = "<?php echo esc_attr(__("Are you sure you want to resize images with a width less than 500px?", "op-bir")); ?>";
            var t9n_confirm_2 = "<?php echo esc_attr(__("Are you sure you want to resize images with a height less than 500px?", "op-bir")); ?>";
            var t9n_confirm_3 = "<?php echo esc_attr(__("Are you sure you want to leave the page?", "op-bir")); ?>";
            var t9n_wait_settings = "<?php echo esc_attr(__("Wait for the end of saving the settings", "op-bir")); ?>";
            var t9n_analisys = "<?php echo esc_attr(__("Analyzing the update in progress ...", "op-bir")); ?>";
            var t9n_start_resize = "<?php echo esc_attr(__("I start resizing the images", "op-bir")); ?>";
            var t9n_warning_resize = "<?php echo esc_attr(__("Do not leave the page while you are resizing your images", "op-bir")); ?>";
            var t9n_we_are_almost_there = "<?php echo esc_attr(__("We are almost there", "op-bir")); ?>";
            var t9n_spared = "<?php echo esc_attr(__("They were spared: <b> %s </b>.", "op-bir")); ?>";
            var t9n_end_1 = "<?php echo esc_attr(__("The update has finished", "op-bir")); ?>";
            var t9n_end_2 = "<?php echo esc_attr(__("Now the used space is: <b>%s</b>", "op-bir")); ?>";
            var t9n_end_3 = "<?php echo esc_attr(__("The elapsed time is: %s", "op-bir")); ?>";
            var t9n_time_remaining = "<?php echo esc_attr(__("Estimated time remaining: %s", "op-bir")); ?>";
            var t9n_time_analisys = "<?php echo esc_attr(__("Estimated time analysis in progress.", "op-bir")); ?>";
            var t9n_img_left = "<?php echo esc_attr(__("Still to be processed: %s", "op-bir")); ?>";
            var t9n_none = "<?php echo esc_attr(__("No images found to update", "op-bir")); ?>";
            var t9n_ops = "<?php echo esc_attr(__("I am having difficulty contacting the server.", "op-bir")); ?>";
            function t9n_sprintf(string) {
                var args = Array.prototype.slice.call(arguments);
                a = args.splice(0,1);
                if (args.length > 0) {
                    for (x in args) {
                        string = string.replace("%s", args[x]);
                    }
                }
                return string;
            }
        </script>
    <?php
}
add_action('admin_head', 'op_transalte_javascript');