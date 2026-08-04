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

    // 1. Títulos de las columnas
    public function printFieldListTitle($parameters, &$object, &$action, $hookmanager) {
        if ($this->_checkContext($parameters['context'])) {
            $this->resprints = '<th class="liste_titre">Almacén Principal</th>';
            $this->resprints .= '<th class="liste_titre">Almacén Secundario</th>';
            return 0;
        }
        return 0;
    }

    // 2. Selectores HTML puros (A prueba de fallos)
    public function printFieldListOption($parameters, &$object, &$action, $hookmanager) {
        if ($this->_checkContext($parameters['context'])) {
            global $conf;

            $wh1 = GETPOST('search_warehouse_id_1', 'int');
            $wh2 = GETPOST('search_warehouse_id_2', 'int');

            // Si limpian los filtros con el botón de la papelera
            if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
                $wh1 = -1;
                $wh2 = -1;
            }

            // Consultamos los almacenes directo a la base de datos
            $sql = "SELECT rowid, ref FROM ".MAIN_DB_PREFIX."entrepot WHERE entity IN (0, " . $conf->entity . ")";
            $resql = $this->db->query($sql);

            $options1 = '<option value="-1">-- Seleccionar --</option>';
            $options2 = '<option value="-1">-- Seleccionar --</option>';

            if ($resql) {
                while ($obj = $this->db->fetch_object($resql)) {
                    $sel1 = ($wh1 == $obj->rowid) ? ' selected="selected"' : '';
                    $sel2 = ($wh2 == $obj->rowid) ? ' selected="selected"' : '';
                    $options1 .= '<option value="'.$obj->rowid.'"'.$sel1.'>'.$obj->ref.'</option>';
                    $options2 .= '<option value="'.$obj->rowid.'"'.$sel2.'>'.$obj->ref.'</option>';
                }
            }

            // Inyectamos el HTML de los selectores directamente
            $html = '<td class="liste_titre">';
            $html .= '<select name="search_warehouse_id_1" class="flat maxwidth100" onchange="this.form.submit();">'.$options1.'</select>';
            $html .= '</td>';
            
            $html .= '<td class="liste_titre">';
            $html .= '<select name="search_warehouse_id_2" class="flat maxwidth100" onchange="this.form.submit();">'.$options2.'</select>';
            $html .= '</td>';

            $this->resprints = $html;
            return 0;
        }
        return 0;
    }

    // 3. Mostramos el stock exacto en cada celda
    public function printFieldListValue($parameters, &$object, &$action, $hookmanager) {
        if ($this->_checkContext($parameters['context'])) {
            $wh1 = GETPOST('search_warehouse_id_1', 'int');
            $wh2 = GETPOST('search_warehouse_id_2', 'int');
            
            $product_id = isset($parameters['obj']->rowid) ? $parameters['obj']->rowid : (isset($parameters['obj']->id) ? $parameters['obj']->id : 0);
            $html = '';
            
            // Valor Columna 1
            if ($wh1 > 0) {
                $stock1 = $this->_getStock($product_id, $wh1);
                $color1 = ($stock1 > 0) ? 'color:green;' : 'color:#999;';
                $html .= '<td><strong style="'.$color1.'">'.$stock1.'</strong></td>';
            } else {
                $html .= '<td>-</td>';
            }

            // Valor Columna 2
            if ($wh2 > 0) {
                $stock2 = $this->_getStock($product_id, $wh2);
                $color2 = ($stock2 > 0) ? 'color:green;' : 'color:#999;';
                $html .= '<td><strong style="'.$color2.'">'.$stock2.'</strong></td>';
            } else {
                $html .= '<td>-</td>';
            }
            
            $this->resprints = $html;
            return 0;
        }
        return 0;
    }

    // 4. Filtramos la consulta base
    public function printFieldListWhere($parameters, &$object, &$action, $hookmanager) {
        if ($this->_checkContext($parameters['context'])) {
            $wh1 = GETPOST('search_warehouse_id_1', 'int');
            $wh2 = GETPOST('search_warehouse_id_2', 'int');
            
            $conditions = array();
            if ($wh1 > 0) $conditions[] = "fk_entrepot = ".$wh1." AND reel > 0";
            if ($wh2 > 0) $conditions[] = "fk_entrepot = ".$wh2." AND reel > 0";
            
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
