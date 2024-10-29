<?php $converters = A2WC()->get_converters(); ?>
<h1>Migration Tool</h1>
<p>This migration tool allows you to convert products imported by third-party plugins to Ali2Woo format.  Just choose your past dropshipping tool from the drop-dawn list below and click "Load products data" to start the process. It will check your database and convert all products if any exists.</p>
<p><strong>Before the migration, fulfill your active orders.</strong></p>
<p><strong>We highly suggest you to make a database dump before start the migration. It will allow you to rollback changes if something go wrong.</strong></p>
<table class="form-table" role="presentation">
    <tbody>
        <tr>
            <th scope="row"><label for="a2wc_system"><?php _e('Choose a system', 'ali2woo-converter'); ?></label></th>
            <td>
                <select id="a2wc_system">
                    <option value=''><?php _e('Choose a system', 'ali2woo-converter'); ?></option>
                    <?php foreach($converters as $converter):?>
                        <option value="<?php echo esc_attr($converter->get_id());?>"><?php echo esc_html($converter->get_name());?></option>
                    <?php endforeach;?>
                </select>
            </td>
        </tr>
    </tbody>
</table>
<div id="a2wc_converter" style="display:none">
    <input id="a2wc_get_products" style="display:none" type="button" class="button button-primary" value="<?php _e('Load products data', 'ali2woo-converter'); ?>">
    <input id="a2wc_convert_products" style="display:none" type="button" class="button button-primary" value="<?php _e('Convert products', 'ali2woo-converter'); ?>">
    <div id="a2wc_convert_logs">
        <label>Logs</label> <a class="clear-logs">[Clear]</a>
        <div class="logs"></div>
    </div>
</div>