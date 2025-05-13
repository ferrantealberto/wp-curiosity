<h2>AdSense Settings</h2>
<p>Configure your AdSense ads for the curiosity generator.</p>

<table class="form-table">
    <tr>
        <th scope="row">
            <label for="cg_adsense_inline_code">Inline Ad Code</label>
        </th>
        <td>
            <textarea name="cg_adsense_inline_code" id="cg_adsense_inline_code" rows="5" class="large-text code"><?php echo esc_textarea(get_option('cg_adsense_inline_code', '')); ?></textarea>
            <p class="description">Enter your AdSense code for inline ads. These will appear before and after the curiosities.</p>
        </td>
    </tr>
    <tr>
        <th scope="row">
            <label for="cg_adsense_fullscreen_code">Fullscreen Ad Code</label>
        </th>
        <td>
            <textarea name="cg_adsense_fullscreen_code" id="cg_adsense_fullscreen_code" rows="5" class="large-text code"><?php echo esc_textarea(get_option('cg_adsense_fullscreen_code', '')); ?></textarea>
            <p class="description">Enter your AdSense code for fullscreen ads. These will appear after the first generation, after the last curiosity in a batch, and at the frequency set below.</p>
        </td>
    </tr>
    <tr>
        <th scope="row">
            <label for="cg_fullscreen_ad_frequency">Fullscreen Ad Frequency</label>
        </th>
        <td>
            <input type="number" name="cg_fullscreen_ad_frequency" id="cg_fullscreen_ad_frequency" class="small-text" min="1" value="<?php echo esc_attr(get_option('cg_fullscreen_ad_frequency', 5)); ?>" />
            <p class="description">How often to show fullscreen ads (e.g., every 5 batches of curiosities).</p>
        </td>
    </tr>
</table>
