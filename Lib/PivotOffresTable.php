<?php

namespace VisitMarche\ThemeTail\Lib;

use AcMarche\Pivot\Entities\Offre\Offre;
use WP_List_Table;

class PivotOffresTable extends WP_List_Table
{
    /**
     * @var Offre[] $data
     */
    public array $data;
    public int $categoryId;

    function get_columns()
    {
        $columns = array(
            'nom' => 'Nom',
            'codeCgt' => 'Code cgt',
            'debug' => 'Debug',
            'dateModification' => 'Modifié le',
        );

        return $columns;
    }

    function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $this->data;
    }

    /**
     * @param Offre $item
     * @param string $column_name
     * @return string|void
     */
    function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'nom':
                $url = RouterPivot::getUrlOffre($item, $this->categoryId);

                return '<a href="'.$url.'">'.$item->nom.'</a>';
            case 'debug':
                return '<a href="'.$this->url($item->codeCgt).'">Debug</a>';
            case 'codeCgt':
                return $item->codeCgt;
            case 'dateModification':
                return $item->dateModification;
            default:
                return '';
        }
    }

    function url(string $codeCgt): string
    {
        return admin_url('admin.php?page=pivot_offre&codeCgt='.$codeCgt);
    }
}