<h2>AdSense Settings</h2>
<p>Configure your AdSense ads for the curiosity generator.</p>

<table class="form-table">
    <tr>
        <th scope="row">
            <label for="cg_disable_demo_ads">Disable Demo Ads</label>
        </th>
        <td>
            <input type="checkbox" name="cg_disable_demo_ads" id="cg_disable_demo_ads" value="1" <?php checked(get_option('cg_disable_demo_ads', 0), 1); ?>>
            <p class="description">When enabled, demo ads will not be shown even if AdSense code is not provided.</p>
        </td>
    </tr>
    <tr>
        <th scope="row">
            <label for="cg_adsense_header_code">Header Ad Code</label>
        </th>
        <td>
            <textarea name="cg_adsense_header_code" id="cg_adsense_header_code" rows="5" class="large-text code"><?php echo esc_textarea(get_option('cg_adsense_header_code', '')); ?></textarea>
            <p class="description">Enter your AdSense code for header ads. These will appear at the top of the curiosity generator box.</p>
        </td>
    </tr>
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
            <label for="cg_adsense_footer_code">Footer Ad Code</label>
        </th>
        <td>
            <textarea name="cg_adsense_footer_code" id="cg_adsense_footer_code" rows="5" class="large-text code"><?php echo esc_textarea(get_option('cg_adsense_footer_code', '')); ?></textarea>
            <p class="description">Enter your AdSense code for footer ads. These will appear at the bottom of the curiosity generator box.</p>
        </td>
    </tr>
    <tr>
        <th scope="row">
            <label for="cg_adsense_fullscreen_code">Fullscreen Ad Code</label>
        </th>
        <td>
            <textarea name="cg_adsense_fullscreen_code" id="cg_adsense_fullscreen_code" rows="5" class="large-text code"><?php echo esc_textarea(get_option('cg_adsense_fullscreen_code', '')); ?></textarea>
            <p class="description">Enter your AdSense code for fullscreen ads. These will appear before starting generation, and after generation is completed.</p>
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