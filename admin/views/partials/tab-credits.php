<h2>Credits Settings</h2>
<p>Configure the credit system for logged-in users.</p>

<table class="form-table">
    <tr>
        <th scope="row">
            <label for="cg_generation_credits">Generation Credits</label>
        </th>
        <td>
            <input type="number" name="cg_generation_credits" id="cg_generation_credits" class="small-text" min="0" value="<?php echo esc_attr(get_option('cg_generation_credits', 5)); ?>" />
            <p class="description">How many credits a user earns for generating a batch of curiosities.</p>
        </td>
    </tr>
    <tr>
        <th scope="row">
            <label for="cg_view_credits">View Credits</label>
        </th>
        <td>
            <input type="number" name="cg_view_credits" id="cg_view_credits" class="small-text" min="0" value="<?php echo esc_attr(get_option('cg_view_credits', 1)); ?>" />
            <p class="description">How many credits a user earns when someone views a curiosity they generated.</p>
        </td>
    </tr>
</table>
