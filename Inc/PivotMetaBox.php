<?php

namespace VisitMarche\ThemeTail\Inc;

class PivotMetaBox
{
    public const PIVOT_REFRUBRIQUE = 'pivot_refrubrique';

    public function __construct()
    {
        add_action(
            'category_edit_form_fields',
            fn($tag) => $this::pivot_metabox_edit($tag),
            10,
            1
        );
    }

    public static function getMetaPivotTypesOffre(int $wpCategoryId): array
    {
        $filtres = get_term_meta($wpCategoryId, PivotMetaBox::PIVOT_REFRUBRIQUE, true);
        if (!is_array($filtres)) {
            return [];
        }

        return $filtres;
    }

    public static function pivot_metabox_edit(\WP_Term $term): void
    {
        wp_enqueue_script(
            'vue-admin-js',
            get_template_directory_uri().'/assets/js/dist/js/appFiltreAdmin-jf.js',
            [],
            wp_get_theme()->get('Version'),
            true
        );
        wp_enqueue_style(
            'vue-admin-css',
            get_template_directory_uri().'/assets/js/dist/css/admin-jf.css',
            [],
            wp_get_theme()->get('Version'),
        );
        $filtres = self::getMetaPivotTypesOffre($term->term_id);
        $update = false;
        foreach ($filtres as $key => $filtre) {
            if (!isset($filtre['urn'])) {
                unset($filtres[$key]);
                $update = true;
            }
        }
        if ($update) {
           // update_term_meta($term->term_id, PivotMetaBox::PIVOT_REFRUBRIQUE, $filtres);
        }
        //  $filtres = delete_term_meta($term->term_id, PivotMetaBox::PIVOT_REFRUBRIQUE);
        ?>
        <table class="form-table">
            <tr class="form-field">
                <th scope="row" valign="top">
                    <label for="bottin_refrubrique">Références pivot</label>
                    <br>
                    <p class="description">
                        <a href="<?php echo admin_url('admin.php?page=pivot_filtres') ?>" target="_blank">
                            Liste des références</a>
                    </p>
                    <br/>
                </th>
                <td>

                    <div id="filtres-box" data-category-id="<?php echo $term->term_id ?>">

                    </div>
                </td>
            </tr>
        </table>
        <?php
    }
}
