<?php

add_action('add_meta_boxes', 'product_fields', 1);

function product_fields() {
	add_meta_box('extra_fields', 'Additional fields', 'extra_fields_box_func', 'product', 'normal', 'high');
}

function extra_fields_box_func($post){
	?>
    <div>
        <?php $img = get_post_meta($post->ID, 'img', true); ?>

        <a href="#" class="add_photo photo_bloсk">
            <?php echo wp_get_attachment_image($img); ?>
        </a>
        
        <input type="hidden" placeholder="Image link" name="extra[img]" value="<?php echo $img; ?>">
        
        <a href="#" class="add_photo add_photo--link" style="<?php if ($img != ''): ?>display: none<?php endif; ?>">Add photo</a>
        <a href="#" class="remove_photo" style="color: #b32d2e; <?php if ($img == ''): ?>display: none<?php endif; ?>">Remove photo</a>
    </div>

    <p>
        <label for="date">Date: </label><br>
        <input id="date" type="date" name="extra[date]" value="<?php echo get_post_meta($post->ID, 'date', 1); ?>" style="width: 100%" />
    </p>

	<p>
        <label for="type">Type:</label><br>
        
        <select name="extra[type]" id="type" style="width: 100%">
			<?php $sel_v = get_post_meta($post->ID, 'type', 1); ?>
			<option value="rare" <?php selected($sel_v, 'rare')?> >rare</option>
			<option value="frequent" <?php selected($sel_v, 'frequent')?> >frequent</option>
			<option value="unusual" <?php selected($sel_v, 'unusual')?> >unusual</option>
		</select>
    </p>

    <p>
        <label for="_title">Name: </label><br>
        <input id="_title" type="text" value="<?php echo $post->post_title; ?>" style="width: 100%" />
    </p>

    <p>
        <label for="_price">Price: </label><br>
        <input id="_price" type="number" style="width: 100%" value="<?php echo get_post_meta($post->ID, '_regular_price', 1); ?>" />
    </p>
    
    <input type="hidden" name="extra_fields_nonce" value="<?php echo wp_create_nonce(__FILE__); ?>" />

    <button class="clear_all">Clear all</button>

    <script>
        jQuery(document).ready(function () {
            jQuery('.add_photo').click(function (e) {
                e.preventDefault();
                
                var image = wp.media({
                        title: 'Upload Image',
                        multiple: false
                    })
                    .open()
                    .on('select', (e) => {
                        var uploaded_image = image.state().get('selection').first(),
                            img = uploaded_image.toJSON();
                        
                        jQuery('input[name="extra[img]"]').val(img.id);
                        jQuery(this).siblings('img').attr('src', img.url).show();
                        jQuery('.add_photo--link').hide();
                        jQuery('.remove_photo').show();
                        jQuery('#_thumbnail_id').val(img.id);

                        jQuery.post(ajaxurl, {
                            action: 'show_image',
                            image_id: img.id
                        }).done(data => {
                            jQuery('.photo_bloсk').html(data);
                            jQuery('#set-post-thumbnail').html(data);
                        });
                    });
            });

            jQuery('.remove_photo').click(function (e) {
                e.preventDefault();

                remove_photo();
            })

            jQuery('.clear_all').click(function (e) {
                e.preventDefault();

                remove_photo();

                jQuery('input[name="extra[date]"').val('');
                jQuery('select[name="extra[type]"').val('');
            })

            jQuery('#_title').on('keyup', function() {
                jQuery('#title').val(jQuery(this).val())
            })

            jQuery('#_price').on('keyup', function() {
                jQuery('#_regular_price').val(jQuery(this).val())
            })

            function remove_photo() {
                jQuery('.photo_bloсk').empty();
                jQuery('input[name="extra[img]"]').val('');
                jQuery('.add_photo--link').show();
                jQuery('.remove_photo').hide();
            }
        });
    </script>
	<?php
}

add_action('save_post', 'product_fields_update', 0);

function product_fields_update($post_id){
	if (
        empty($_POST['extra']) ||
        ! wp_verify_nonce($_POST['extra_fields_nonce'], __FILE__) ||
        wp_is_post_autosave($post_id) ||
        wp_is_post_revision($post_id)
	)
		return false;

	$_POST['extra'] = array_map('sanitize_text_field', $_POST['extra']);
	foreach ($_POST['extra'] as $key => $value){
		if (empty($value)){
			delete_post_meta($post_id, $key);
			continue;
		}

		update_post_meta($post_id, $key, $value);
	}

	return $post_id;
}

add_action('wp_ajax_show_image', 'show_image');
add_action('wp_admin_ajax_show_image', 'show_image');

function show_image(){
	echo wp_get_attachment_image(intval($_POST['image_id']));

	wp_die();
}