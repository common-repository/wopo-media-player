<?php
/**
 * Plugin Name:       WoPo Media Player
 * Plugin URI:        https://wopoweb.com/contact-us/
 * Description:       Microsoft Winamp 2 for the browser
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.1
 * Author:            WoPo Web
 * Author URI:        https://wopoweb.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wopo-media-player
 * Domain Path:       /languages
 */


add_action('wp_enqueue_scripts', 'wopomp_enqueue_scripts');
function wopomp_enqueue_scripts(){
    global $post;
    $is_shortcode = intval(has_shortcode( $post->post_content, 'wopo-media-player'));
    if ($is_shortcode){
        wp_enqueue_script('webamp', plugins_url( '/assets/js/webamp.bundle.min.js', __FILE__ ));
        wp_enqueue_script('butterchurn', plugins_url( '/assets/js/butterchurn.min.js', __FILE__ ));
        wp_enqueue_script('butterchurnPresets', plugins_url( '/assets/js/butterchurnPresets.min.js', __FILE__ ));        
        do_action('wopo_media_player_enqueue_scripts');
    }
}

add_shortcode('wopo-media-player', 'wopomp_shortcode');
function wopomp_shortcode( $atts = [], $content = null) {
    // default media 
    $media[] = array(
        'artist' => 'DJ Mike Llama',
        'title' => "Llama Whippin' Intro",
        'url' => plugins_url('/assets/mp3/llama-2.91.mp3',__FILE__)
    );
    if (isset($atts['media-id'])){
        $media = array();
        $mediaIds = explode(',',$atts['media-id']);
        foreach($mediaIds as $mid){
            $mediaMeta = wp_get_attachment_metadata($mid);
            if ($mediaMeta == false) continue;
            $media[] = array(
                'artist' => $mediaMeta['artist'],
                'title' => get_the_title($mid),
                'url' => wp_get_attachment_url($mid),
            );
        }        
    }
    $skins = glob(plugin_dir_path( __FILE__ ).'/assets/skins/*.wsz');
    
    ob_start();?>
    <div id="winamp-container"></div>
    <script>
        const Webamp = window.Webamp;
        const webamp = new Webamp({
            initialTracks: [
                {
                    <?php foreach($media as $md):?>
                    metaData: {
                        artist: "<?php echo esc_js($md['artist']) ?>",
                        title: "<?php echo esc_js($md['title']) ?>"
                    },
                    // NOTE: Your audio file must be served from the same domain as your HTML
                    // file, or served with permissive CORS HTTP headers:
                    // https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
                    url: "<?php echo esc_url($md['url']) ?>",
                    duration: 5.322286
                    <?php endforeach; ?>
                },                
            ],
            availableSkins: [
                <?php foreach($skins as $sk){
                    $file_name = wp_basename($sk);
                    echo '{ url: "'.plugins_url( '/assets/skins/'.$file_name, __FILE__ ).'", name: "'.esc_js($file_name).'" },';
                }?>
            ],

            __butterchurnOptions: {
                importButterchurn: () => Promise.resolve(window.butterchurn),
                getPresets: () => {
                    const presets = window.butterchurnPresets.getPresets();
                    return Object.keys(presets).map((name) => {
                        return {
                            name,
                            butterchurnPresetObject: presets[name]
                        };
                    });
                },
                butterchurnOpen: true
            },
            __initialWindowLayout: {
                main: { position: { x: 0, y: 0 } },
                equalizer: { position: { x: 0, y: 116 } },
                playlist: { position: { x: 0, y: 232 }, size: [0, 4] },
                milkdrop: { position: { x: 275, y: 0 }, size: [7, 12] }
            }
        });

        // Returns a promise indicating when it's done loading.
        webamp.renderWhenReady(document.getElementById('winamp-container'));
    </script>    
    <?php
    $content = ob_get_clean();
    return $content;
}