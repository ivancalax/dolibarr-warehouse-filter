<?php
class ActionsWarehouseFilter {
    public $db;
    public $resprints; 
    public $resql;     

    public function __construct($db) {
        $this->db = $db;
    }

    private function _checkContext($context) {
        if (empty($context)) return false;
        $contexts = explode(':', $context);
        return (in_array('productlist', $contexts) || in_array('productservicelist', $contexts));
    }

    // NUEVA FUNCIÓN: Obtiene el nombre (ref) del almacén según su ID
    private function _getWarehouseName($warehouse_id) {
        if ($warehouse_id <= 0) return '';
        $sql = "SELECT ref FROM ".MAIN_DB_PREFIX."entrepot WHERE rowid = ".(int)$warehouse_id;
        $resql = $this->db->query($sql);
        if ($resql && $row = $this->db->fetch_object($resql)) {
            return $row->ref;
        }
        return '';
    }

    // 1. Títulos de las 4 columnas (Ahora son dinámicos)
    public function printFieldListTitle($parameters, &$object, &$action, $hookmanager) {
        if ($this->_checkContext($parameters['context'])) {
            $wh1 = GETPOST('search_warehouse_id_1', 'int');
            $wh2 = GETPOST('search_warehouse_id_2', 'int');
            $wh3 = GETPOST('search_warehouse_id_3', 'int');
            $wh4 = GETPOST('search_warehouse_id_4', 'int');

            $title1 = ($wh1 > 0) ? $this->_getWarehouseName($wh1) : 'Almacén 1';
            $title2 = ($wh2 > 0) ? $this->_getWarehouseName($wh2) : 'Almacén 2';
            $title3 = ($wh3 > 0) ? $this->_getWarehouseName($wh3) : 'Almacén 3';
            $title4 = ($wh4 > 0) ? $this->_getWarehouseName($wh4) : 'Almacén 4';

            $this->resprints = '<th class="liste_titre">'.$title1.'</th>';
            $this->resprints .= '<th class="liste_titre">'.$title2.'</th>';
            $this->resprints .= '<th class="liste_titre">'.$title3.'</th>';
            $this->resprints .= '<th class="liste_titre">'.$title4.'</th>';
            
            return 0;
        }
        return 0;
    }

    // 2. Selectores HTML puros para los 4 almacenes
    public function printFieldListOption($parameters, &$object, &$action, $hookmanager) {
        if ($this->_checkContext($parameters['context'])) {
            global $conf;

            $wh1 = GETPOST('search_warehouse_id_1', 'int');
            $wh2 = GETPOST('search_warehouse_id_2', 'int');
            $wh3 = GETPOST('search_warehouse_id_3', 'int');
            $wh4 = GETPOST('search_warehouse_id_4', 'int');

            // Si limpian los filtros con el botón de la papelera
            if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
                $wh1 = -1;
                $wh2 = -1;
                $wh3 = -1;
                $wh4 = -1;
            }

            // Consultamos los almacenes directo a la base de datos
            $sql = "SELECT rowid, ref FROM ".MAIN_DB_PREFIX."entrepot WHERE entity IN (0, " . $conf->entity . ")";
            $resql = $this->db->query($sql);

            $options1 = '<option value="-1">-- Todos --</option>';
            $options2 = '<option value="-1">-- Todos --</option>';
            $options3 = '<option value="-1">-- Todos --</option>';
            $options4 = '<option value="-1">-- Todos --</option>';

            if ($resql) {
                while ($obj = $this->db->fetch_object($resql)) {
                    $sel1 = ($wh1 == $obj->rowid) ? ' selected="selected"' : '';
                    $sel2 = ($wh2 == $obj->rowid) ? ' selected="selected"' : '';
                    $sel3 = ($wh3 == $obj->rowid) ? ' selected="selected"' : '';
                    $sel4 = ($wh4 == $obj->rowid) ? ' selected="selected"' : '';
                    
                    $options1 .= '<option value="'.$obj->rowid.'"'.$sel1.'>'.$obj->ref.'</option>';
                    $options2 .= '<option value="'.$obj->rowid.'"'.$sel2.'>'.$obj->ref.'</option>';
                    $options3 .= '<option value="'.$obj->rowid.'"'.$sel3.'>'.$obj->ref.'</option>';
                    $options4 .= '<option value="'.$obj->rowid.'"'.$sel4.'>'.$obj->ref.'</option>';
                }
            }

            // Inyectamos el HTML de los selectores directamente
            $html = '<td class="liste_titre">';
            $html .= '<select name="search_warehouse_id_1" class="flat maxwidth100" onchange="this.form.submit();">'.$options1.'</select></td>';
            
            $html .= '<td class="liste_titre">';
            $html .= '<select name="search_warehouse_id_2" class="flat maxwidth100" onchange="this.form.submit();">'.$options2.'</select></td>';
            
            $html .= '<td class="liste_titre">';
            $html .= '<select name="search_warehouse_id_3" class="flat maxwidth100" onchange="this.form.submit();">'.$options3.'</select></td>';
            
            $html .= '<td class="liste_titre">';
            $html .= '<select name="search_warehouse_id_4" class="flat maxwidth100" onchange="this.form.submit();">'.$options4.'</select></td>';

            $this->resprints = $html;
            return 0;
        }
        return 0;
    }

    // 3. Mostramos el stock exacto en cada celda
    public function printFieldListValue($parameters, &$object, &$action, $hookmanager) {
        if ($this->_checkContext($parameters['context'])) {
            $whs = array(
                GETPOST('search_warehouse_id_1', 'int'),
                GETPOST('search_warehouse_id_2', 'int'),
                GETPOST('search_warehouse_id_3', 'int'),
                GETPOST('search_warehouse_id_4', 'int')
            );
            
            $product_id = isset($parameters['obj']->rowid) ? $parameters['obj']->rowid : (isset($parameters['obj']->id) ? $parameters['obj']->id : 0);
            $html = '';
            
            // Generar las 4 columnas de valores
            foreach ($whs as $wh) {
                if ($wh > 0) {
                    $stock = $this->_getStock($product_id, $wh);
                    $color = ($stock > 0) ? 'color:green;' : 'color:#999;';
                    $html .= '<td><strong style="'.$color.'">'.$stock.'</strong></td>';
                } else {
                    $html .= '<td>-</td>';
                }
            }
            
            $this->resprints = $html;
            return 0;
        }
        return 0;
    }

    // 4. Filtramos la consulta base 
    public function printFieldListWhere($parameters, &$object, &$action, $hookmanager) {
        if ($this->_checkContext($parameters['context'])) {
            $whs = array(
                GETPOST('search_warehouse_id_1', 'int'),
                GETPOST('search_warehouse_id_2', 'int'),
                GETPOST('search_warehouse_id_3', 'int'),
                GETPOST('search_warehouse_id_4', 'int')
            );
            
            $conditions = array();
            foreach ($whs as $wh) {
                if ($wh > 0) {
                    $conditions[] = "fk_entrepot = ".$wh." AND reel > 0";
                }
            }
            
            if (!empty($conditions)) {
                $sql_cond = implode(" OR ", $conditions);
                $this->resql = " AND p.rowid IN (SELECT fk_product FROM ".MAIN_DB_PREFIX."product_stock WHERE ".$sql_cond.")";
            }
        }
        return 0; 
    }

    // Función auxiliar para obtener el stock
    private function _getStock($product_id, $warehouse_id) {
        if ($product_id <= 0 || $warehouse_id <= 0) return 0;
        $sql = "SELECT reel FROM ".MAIN_DB_PREFIX."product_stock WHERE fk_product = ".(int)$product_id." AND fk_entrepot = ".(int)$warehouse_id;
        $resql = $this->db->query($sql);
        if ($resql && $row = $this->db->fetch_object($resql)) {
            return $row->reel;
        }
        return 0;
    }
}
