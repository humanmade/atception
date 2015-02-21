# Atception

Yo dawg I heard you like attachments so I made a plugin that lets you attach
attachments to your attachments

## Wat?

In all seriousness sometimes it can be useful to have a method of connecting
alternative media files to other media files. Take the following examples:

 * Featured image for a video file, can be used to create a placeholder
 * Featured image for audio, such as a cd cover
 * Static fallback for a large gif
 * ogg / webm / mp4 versions of a video file

**Note**: this a developer tool - it only provides the admin UI and a function
for retrieving the alternative media.

## How to use it

**`hm_register_alt_media( $key, $label, $mime_type [, $alt_mime_type = 'image' [, $multiple = false ] ] )`**

 * **`$key`:** an identifier for the connection eg. 'video_thumb'
 * **`$label`:** a human readable name for the connection used in the UI
 * **`$mime_type`:** the file type to attach media to eg. 'video', 'image/gif'
 * **`$alt_mime_type`:** the file type to be attached, defaults to 'image'
 * **`$multiple`:** allow multiple files to be attached

```php
<?php

// add thumbnails to videos
hm_register_alt_media( 'video_thumb', __( 'Video thumbnail' ), 'video' );

?>
```

**`hm_unregister_alt_media( $key )`**

 * **`$key`:** an identifier for the connection to remove eg. 'video_thumb'

```php
<?php

// remove thumbnails from videos
hm_unregister_alt_media( 'video_thumb' );

?>
```

**`hm_get_alt_media( $attachment_id, $key, [, $fields = 'ids' ] )`**

 * **`$attachment_id`:** the attachment to fetch alternative media for
 * **`$key`:** the identifier used to register the connection
 * **`$fields`:** the fields arguments passed to `get_posts()`, can be 'ids' or 'all'

```php
<?php

// get thumbnails to videos
$thumb = hm_get_alt_media( 123, 'video_thumb' );
echo wp_get_attachment_image( $thumb, 'medium' );

?>
```

## Contributing

All suggestions and ideas are welcome, just add an issue!
