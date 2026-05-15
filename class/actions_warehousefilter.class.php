<?php

class ActionsWarehouseFilter 
{
    public $db;
    public $resprints; // Variable obligatoria de Dolibarr para imprimir HTML
    public $resql;     // Variable obligatoria de Dolibarr para inyectar SQL

    // 1. CONSTRUCTOR OBLIGATORIO PARA USAR BASE DE DATOS
    public function __construct($db) 
    {
        $this->db = $db;
    }

    // Validador de contexto
    private function _checkContext($context) 
    {
        if (empty($context)) return false;
        
        $contexts = explode(':', $context);
        return (in_array('productlist', $contexts) || in_array('productservicelist', $contexts));
    }

    // Añadir Título de la columna (Si no lo ponemos, la tabla se descuadra)
    public function printFieldListTitle($parameters, &$object, &$action, $hookmanager) 
    {
        if ($this->_checkContext($parameters['context'])) {
            $this->resprints = '<th class="liste_titre">Almacén</th>';
            return 1; // Modificado a 1 para Dolibarr v23
        }
        
        return 0;
    }

    // Inserta el selector en la fila de filtros
    public function printFieldListOption($parameters, &$object, &$action, $hookmanager) 
    {
        if ($this->_checkContext($parameters['context'])) {
            // CORREGIDO: En Dolibarr v23 se usa FormProduct y selectWarehouses
            require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
            $formproduct = new FormProduct($this->db);
            $selected = GETPOST('search_warehouse_id', 'int');
            
            $html  = '<td class="liste_titre right">';
            $html .= $formproduct->selectWarehouses($selected, 'search_warehouse_id', '', 1, 0, 0, '', 0, 0, 'maxwidth150');
            $html .= '</td>';
            
            $this->resprints = $html; // Se inyecta así
            return 1; // Modificado a 1 para Dolibarr v23
        }
        
        return 0;
    }

    // Muestra el stock del producto por almacén en la lista
    public function printFieldListValue($parameters, &$object, &$action, $hookmanager) 
    {
        if ($this->_checkContext($parameters['context'])) {
            global $conf;
            $warehouse_id = GETPOST('search_warehouse_id', 'int');
            
            // Si hay un almacén seleccionado, buscamos el stock
            if ($warehouse_id > 0) {
                $sql  = "SELECT reel FROM " . MAIN_DB_PREFIX . "product_stock";
                $sql .= " WHERE fk_product = " . (int)$object->id;
                $sql .= " AND fk_entrepot = " . (int)$warehouse_id;
                
                $resql_stock = $this->db->query($sql);
                
                if ($resql_stock) {
                    $obj_stock = $this->db->fetch_object($resql_stock);
                    $stock = ($obj_stock ? $obj_stock->reel : 0);
                    $this->db->free($resql_stock);
                    
                    $this->resprints = '<td class="right">' . $stock . '</td>';
                } else {
                    // Error en consulta
                    $this->resprints = '<td></td>'; 
                }
            } else {
                // Si no hay almacén seleccionado, mostramos celda vacía
                $this->resprints = '<td></td>';
            }
            
            return 1; // Modificado a 1 para Dolibarr v23
        }
        
        return 0;
    }

    // Modifica la consulta SQL para filtrar la lista
    public function printFieldListWhere($parameters, &$object, &$action, $hookmanager) 
    {
        $warehouse_id = GETPOST('search_warehouse_id', 'int');
        
        if ($this->_checkContext($parameters['context']) && $warehouse_id > 0) {
            $this->resql = " AND p.rowid IN (SELECT fk_product FROM " . MAIN_DB_PREFIX . "product_stock WHERE fk_entrepot = " . $warehouse_id . " AND reel > 0)";
            return 1; // 1 significa que inyecta el resql
        }
        
        return 0;
    }
}