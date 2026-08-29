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

    // Obtiene el nombre corto (lieu) del almacén según su ID
    private function _getWarehouseName($warehouse_id) {
        if ($warehouse_id <= 0) return '';
        $sql = "SELECT ref, lieu FROM ".MAIN_DB_PREFIX."entrepot WHERE rowid = ".(int)$warehouse_id;
        $resql = $this->db->query($sql);
        if ($resql && $row = $this->db->fetch_object($resql)) {
            // Retorna el nombre corto, si está vacío usa la referencia como respaldo
            return !empty($row->lieu) ? $row->lieu : $row->ref;
        }
        return '';
    }

    // FUNCIÓN AUXILIAR: Gestiona los valores por defecto (A-Z) y memoria de sesión
    private function _getWhs($context) {
        global $conf;
        
        // Llaves de sesión únicas para recordar filtros al paginar
        $ctx = str_replace(':', '_', $context);
        $sk1 = 'wh1_'.$ctx;
        $sk2 = 'wh2_'.$ctx;
        $sk3 = 'wh3_'.$ctx;
        $sk4 = 'wh4_'.$ctx;
        
        $wh1 = GETPOST('search_warehouse_id_1');
        $wh2 = GETPOST('search_warehouse_id_2');
        $wh3 = GETPOST('search_warehouse_id_3');
        $wh4 = GETPOST('search_warehouse_id_4');
        
        // Detectar si el usuario presionó la papelera para limpiar filtros
        $is_clear = (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter', 'alpha'));
        
        if ($is_clear) {
            $wh1 = $wh2 = $wh3 = $wh4 = '';
            unset($_SESSION[$sk1], $_SESSION[$sk2], $_SESSION[$sk3], $_SESSION[$sk4]);
        } else {
            // Recuperar de la sesión si no se envió por POST (ej. al cambiar de página)
            if ($wh1 === '' && isset($_SESSION[$sk1])) $wh1 = $_SESSION[$sk1];
            if ($wh2 === '' && isset($_SESSION[$sk2])) $wh2 = $_SESSION[$sk2];
            if ($wh3 === '' && isset($_SESSION[$sk3])) $wh3 = $_SESSION[$sk3];
            if ($wh4 === '' && isset($_SESSION[$sk4])) $wh4 = $_SESSION[$sk4];
        }
        
        // Si todo está vacío, es la CARGA INICIAL: Seleccionamos los 4 primeros por REFERENCIA (ref)
        if ($wh1 === '' && $wh2 === '' && $wh3 === '' && $wh4 === '') {
            $defaults = array(-1, -1, -1, -1);
            // ORDEN ALFABÉTICO POR REFERENCIA
            $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."entrepot WHERE entity IN (0, " . (int)$conf->entity . ") ORDER BY ref ASC LIMIT 4";
            $resql = $this->db->query($sql);
            if ($resql) {
                $i = 0;
                while ($obj = $this->db->fetch_object($resql)) {
                    $defaults[$i] = $obj->rowid;
                    $i++;
                }
            }
            $_SESSION[$sk1] = $defaults[0];
            $_SESSION[$sk2] = $defaults[1];
            $_SESSION[$sk3] = $defaults[2];
            $_SESSION[$sk4] = $defaults[3];
            return $defaults;
        }
        
        // Guardar selección actual en sesión para que no se borre al paginar
        if ($wh1 !== '') $_SESSION[$sk1] = $wh1;
        if ($wh2 !== '') $_SESSION[$sk2] = $wh2;
        if ($wh3 !== '') $_SESSION[$sk3] = $wh3;
        if ($wh4 !== '') $_SESSION[$sk4] = $wh4;
        
        return array((int)$wh1, (int)$wh2, (int)$wh3, (int)$wh4);
    }

    // 1. Títulos de las 4 columnas
    public function printFieldListTitle($parameters, &$object, &$action, $hookmanager) {
        if ($this->_checkContext($parameters['context'])) {
            list($wh1, $wh2, $wh3, $wh4) = $this->_getWhs($parameters['context']);

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

            list($wh1, $wh2, $wh3, $wh4) = $this->_getWhs($parameters['context']);

            // Consultamos almacenes y ORDENAMOS POR REFERENCIA (ref ASC)
            $sql = "SELECT rowid, ref, lieu FROM ".MAIN_DB_PREFIX."entrepot WHERE entity IN (0, " . (int)$conf->entity . ") ORDER BY ref ASC";
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
                    
                    // Mostramos SIEMPRE el nombre corto (lieu) en la etiqueta visual
                    $display_name = !empty($obj->lieu) ? $obj->lieu : $obj->ref;

                    $options1 .= '<option value="'.$obj->rowid.'"'.$sel1.'>'.$display_name.'</option>';
                    $options2 .= '<option value="'.$obj->rowid.'"'.$sel2.'>'.$display_name.'</option>';
                    $options3 .= '<option value="'.$obj->rowid.'"'.$sel3.'>'.$display_name.'</option>';
                    $options4 .= '<option value="'.$obj->rowid.'"'.$sel4.'>'.$display_name.'</option>';
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
            $whs = $this->_getWhs($parameters['context']);
            
            $product_id = isset($parameters['obj']->rowid) ? $parameters['obj']->rowid : (isset($parameters['obj']->id) ? $parameters['obj']->id : 0);
            $html = '';
            
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
            $whs = $this->_getWhs($parameters['context']);
            
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
